<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayUService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $clientId;
    protected $clientSecret;
    /**
     * @var CurrencyConversionService
     */
    protected $converter;
    /**
     * @var Repository|Application|mixed
     */
    protected $secret;
    /**
     * @var Repository|Application|mixed
     */
    protected $key;
    /**
     * @var Repository|Application|mixed
     */
    protected $base_currency;
    /**
     * @var Repository|Application|mixed
     */
    protected $merchant_id;
    /**
     * @var Repository|Application|mixed
     */
    protected $account_id;

    /**
     * @param CurrencyConversionService $converter
     */
    public function __construct(CurrencyConversionService $converter)
    {
        $this->baseUri = config('services.payu.base_uri');
        $this->secret = config('services.payu.secret');
        $this->key = config('services.payu.key');
        $this->base_currency = strtoupper(config('services.payu.base_currency'));
        $this->merchant_id = config('services.payu.merchant_id');
        $this->account_id = config('services.payu.account_id');

        $this->converter = $converter;
    }

    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        $formParams['merchant']['apiKey'] = $this->key;
        $formParams['merchant']['apiLogin'] = $this->secret;
    }

    public function decodeResponse($response)
    {
        return json_decode($response);
    }

    public function resolveAccessToken(): string
    {
        $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");
        return "Basic {$credentials}";
    }


    /**
     * @throws GuzzleException
     */
    public function handlePayment(Request $request)
    {

        $request->validate([
           'card' => 'required',
           'cvc' => 'required',
           'year' => 'required',
           'month' => 'required',
           'network' => 'required',
           'name' => 'required',
           'email' => 'required',
        ]);

        $payment = $this->createPayment(
            $request->value,
            $request->currency,
            $request->name,
            $request->email,
            $request->card,
            $request->cvc,
            $request->year,
            $request->month,
            $request->network,
        );



        if($payment->transactionResponse->responseCode == 'APPROVED')
        {
            $name = $request->name;
            $amount = $request->value;
            $currency = strtoupper($request->currency);
            return redirect()
                ->route('home')
                ->withSuccess(['payment' => "Gracias, {$name}. Hemos recibido tu pago de ({$amount}{$currency})"]);
        }
        return redirect()
            ->route('home')
            ->withErrors('No pudimos procesar su pago. Por favor, inténtalo de nuevo');

    }

    /**
     * @param $value
     * @param $currency
     * @return mixed|string
     * @throws GuzzleException
     */
    public function createOrder($value, $currency)
    {
        $factor = $this->resolveFactor($currency);

        return $this->makeRequest(
            'POST',
            '/v2/checkout/orders',
            [],
            [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    0 => [
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => round($value * $factor) / $factor,
                        ]
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('approval'),
                    'cancel_url' => route('cancelled'),
                ]
            ],
            [],
            $isJsonRequest = true,
        );
    }

    public function capturePayment(string $approvalId)
    {
        return $this->makeRequest(
            'POST',
            "v2/checkout/orders/{$approvalId}/capture",
            [],
            [],
            [
                'Content-Type' => 'application/json',
            ]
        );
    }

    public function handleApproval()
    {
        //
    }

    public function resolveFactor($currency): int
    {
        $zeroDecimalCurrencies = ['JPY'];
        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return 1;
        }
        return 100;
    }

    public function generateSignature($referenceCode, $value): string
    {
        return md5("{$this->key}~{$this->merchant_id}~{$referenceCode}~{$value}~{$this->base_currency}");
    }


    /**
     * @param $value
     * @param $currency
     * @param $name
     * @param $email
     * @param $card
     * @param $cvc
     * @param $year
     * @param $month
     * @param $network
     * @param $installments
     * @param $paymentCountry
     * @return mixed|string
     * @throws GuzzleException
     */
    public function createPayment($value, $currency, $name, $email, $card, $cvc, $year, $month, $network, $installments = 1, $paymentCountry = 'AR')
    {
        return $this->makeRequest(
            'POST',
            '/payments-api/4.0/service.cgi',
            [],
            [
                'language' => $language = config('app.locale'),
                'command' => 'SUBMIT_TRANSACTION',
                'test' => false,
                'transaction' => [
                    'type' => 'AUTHORIZATION_AND_CAPTURE',
                    'paymentMethod' => strtoupper($network),
                    'paymentCountry' => strtoupper($paymentCountry),
                    'deviceSessionId' => session()->getId(),
                    'ipAddress' => request()->ip(),
                    'userAgent' => request()->header('user-Agent'),
                    'creditCard' => [
                        'number' => $card,
                        'securityCode' => $cvc,
                        'expirationDate' => "{$year}/{$month}",
                        'name' => "APPROVED",
                    ],
                    'extraParameters' => [
                        'INSTALLMENTS_NUMBER' => $installments
                    ],
                    'payer' => [
                        'fullName' => $name,
                        'emailAddress' => $email,
                    ],
                    'order' => [
                        'accountId' => $this->account_id,
                        'referenceCode' => $reference = Str::random(12),
                        'description' => 'Compra con PayU',
                        'language' => $language,
                        'signature' => $this->generateSignature($reference, $value = round($value * $this->resolveFactor($currency))),
                        'additionalValues' => [
                            'TX_VALUE' => [
                                'value' => $value,
                                'currency' => $this->base_currency
                            ],
                        ],
                        'buyer' => [
                            'fullName' => $name,
                            'emailAddress' => $email,
                            'shippingAddress' => [
                                'street1' => '',
                                'city' => '',
                            ]
                        ]
                    ]
                ],
            ],
            [
                'Accept' => 'application/json'
            ],
            true
        );
    }
}
