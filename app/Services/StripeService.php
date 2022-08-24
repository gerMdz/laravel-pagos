<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StripeService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $key;
    protected $secret;

    public function __construct()
    {
        $this->key = config('services.stripe.key');
        $this->secret = config('services.stripe.secret');
        $this->baseUri = config('services.stripe.base_uri');
    }

    public function resolveAuthorization(&$queryParams, &$formsParams, &$headers)
    {
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response)
    {
        return json_decode($response);
    }

    public function resolveAccessToken(): string
    {
        return "Bearer {$this->secret}";
    }

    /**
     * @throws GuzzleException
     */
    public function handlePayment(Request $request)
    {
//        $order = $this->createOrder($request->value, $request->currency);
//
//        $orderLinks = collect($order->links);
//
//        $approve = $orderLinks->where('rel', 'approve')->first();
//
//        session()->put('approvalId', $order->id);
//
//        return redirect($approve->href);
    }







    public function handleApproval()
    {
//
    }

    public function createIntent($value, $currency, $paymentMethod)
    {
       return $this->makeRequest(
            'POST',
            '/v1/payment_intents',
            [],
            [
                'amount' => round($value * $this->resolveFactor($currency)),
                'currency' => strtolower($currency) ,
                'payment_method' => $paymentMethod,
                'confirmation_method' => 'manual'
            ]
        );
    }

    public function confirmPayment($paymentIntentId)
    {
        return $this->makeRequest(
            'POST',
            "/v1/payment_intents/{$paymentIntentId}/confirm",
            [],
            []
        );
    }



    public function resolveFactor($currency): int
    {
        $zeroDecimalCurrencies = ['JPY'];
        if(in_array(strtoupper($currency), $zeroDecimalCurrencies)){
            return 100;
        }
        return 1;
    }
}
