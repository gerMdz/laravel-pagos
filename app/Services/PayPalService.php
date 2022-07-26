<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Link;

class PayPalService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $clientId;
    protected $clientSecret;
    protected $plans;


    public function __construct()
    {
        $this->baseUri = config('services.paypal.base_uri');
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->plans = config('services.paypal.plans');
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

    public function createSubscription($planSlug, $name, $email )
    {

        return $this->makeRequest(
            'POST',
            '/v1/billing/subscriptions',
            [],
            [
                'plan_id' => $this->plans[$planSlug],
                'subscriber' => [
                    'name' => [
                        'given_name' => $name
                    ],
                    'email_address' => $email
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url' => route('subscribe.approval', ['plan' => $planSlug]),
                    'cancel_url' => route('subscribe.cancelled'),
                ]
            ],
            [],
            $isJsonRequest = true,
        );
    }

    public function handleSubscription(Request $request)
    {

        $subscription = $this->createSubscription(
          $request->plan,
          $request->user()->name,
          $request->user()->email,
        );

        $subscriptionLinks = collect($subscription->links);

        $approve = $subscriptionLinks->where('rel', 'approve')->first();

        session()->put('subscriptionId', $subscription->id);

        return redirect($approve->href);

    }

    public function validateSubscription(Request $request): bool
    {
        if(session()->has('subscriptionId')) {
            $subscriptionId = session()->get('subscriptionId');

            session()->forget('subscriptionId');

            return $request->subscription_id == $subscriptionId;
        }
        return false;
    }
}
