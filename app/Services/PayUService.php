<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $order = $this->createOrder($request->value, $request->currency);

        $orderLinks = collect($order->links);

        $approve = $orderLinks->where('rel', 'approve')->first();

        session()->put('approvalId', $order->id);

        return redirect($approve->href);
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
                'Content-Type'=> 'application/json',
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
        if(in_array(strtoupper($currency), $zeroDecimalCurrencies)){
            return 1;
        }
        return 100;
    }

    public function generateSignature()
    {
        //
    }
}
