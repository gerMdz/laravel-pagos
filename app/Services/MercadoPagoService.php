<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MercadoPagoService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $clientSecret;
    protected $clientCurrency;
    protected $clientKey;
    /**
     * @var CurrencyConversionService
     */
    protected $conversionService;


    /**
     * @param CurrencyConversionService $conversionService
     */
    public function __construct(CurrencyConversionService $conversionService)
    {
        $this->baseUri = config('services.mercadopago.base_uri');
        $this->clientKey = config('services.mercadopago.key');
        $this->clientSecret = config('services.mercadopago.secret');
        $this->clientCurrency = config('services.mercadopago.base_currency');
        $this->conversionService = $conversionService;
    }

    public function resolveAuthorization(&$queryParams, &$formsParams, &$headers)
    {
        $queryParams['access_token'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response)
    {
        return json_decode($response);
    }

    public function resolveAccessToken()
    {

        return $this->clientSecret;
    }


    public function handlePayment(Request $request)
    {
        $request->validate([
            'card_network'=>'required',
            'card_token'=>'required',
            'email'=>'required',
        ]);

        $payment = $this->createPayment(
            $request->value,
            $request->currency,
            $request->card_network,
            $request->card_token,
            $request->email,
        );

        if($payment->status === "approved" ){
            $name = $payment->payer->first_name;
            $currency = strtoupper($payment->currency_id);
            $amount = number_format($payment->transaction_amount, 0,',','.');
            $originalAmount = $request->value;
            $originalCurrency = strtoupper($request->currency);
            return redirect()
                ->route('home')
                ->withSuccess(['payment' => "Gracias, {$name}. Hemos recibido tu pago de {$originalAmount}{$originalCurrency}. ({$amount}{$currency})"]);
        }

        return redirect()
            ->route('home')
            ->withErrors('No pudimos confirmar su pago. Por favor, inténtalo de nuevo');
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

    public function handleApproval():void
    {

        //
    }

    public function createPayment($value, $currency, $cardNetwork, $cardToken, $email, $installments = 1)
    {
        return $this->makeRequest(
            'POST',
            '/v1/payments',
            [],
            [
                'payer' => [
                    'email' => $email,
                ],
                'binary_mode' => true,
                'transaction_amount' => round($value * $this->resolveFactor($currency)),
                'payment_method_id' => $cardNetwork,
                'token' => $cardToken,
                'installments' => $installments,
                'statement_descriptor' => config('app.name')
            ],
            [],
            true
        );
    }

    public function resolveFactor($currency)
    {
        return
        $this->conversionService->convertCurrency($currency, $this->clientCurrency);
    }
}
