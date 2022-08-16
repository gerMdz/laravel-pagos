<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayPalService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUri = config('services.paypal.base_uri');
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
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

    public function handleApproval(): RedirectResponse
    {
        if(session()->has('approvalId')){
            $approvalId = session()->get('approvalId');
            $payment = $this->capturePayment($approvalId);

            $name = $payment->payer->name->given_name;

            $pago = $payment->purchase_units[0]->payments->captures[0]->amount;

            $amount = $pago->value;
            $currency = $pago->currency_code;

            return redirect()
                ->route('home')
                ->withSuccess(['payment' => "Gracias, {$name}. Hemos recibido tu pago de {$amount}{$currency}"]);
        }

        return redirect()->route('home')->withErrors('No fue posible obtener el pago');
    }

    public function resolveFactor($currency): int
    {
        $zeroDecimalCurrencies = ['JPY'];
        if(in_array(strtoupper($currency), $zeroDecimalCurrencies)){
            return 1;
        }
        return 100;
    }
}
