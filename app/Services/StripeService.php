<?php

namespace App\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Traits\ConsumesExternalServices;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class StripeService
{
    use ConsumesExternalServices;

    protected $key;

    protected $secret;

    protected $baseUri;

    protected $plans;

    public function __construct()
    {
        $this->baseUri = config('services.stripe.base_uri');
        $this->key = config('services.stripe.key');
        $this->secret = config('services.stripe.secret');
        $this->plans = config('services.stripe.plans');
    }

    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response)
    {
        return json_decode($response);
    }

    public function resolveAccessToken(): string
    {
        return "Bearer $this->secret";
    }

    /**
     * @throws GuzzleException
     */
    public function handlePayment(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required',
        ]);

        $intent = $this->createIntent($request->value, $request->currency, $request->payment_method);

        session()->put('paymentIntentId', $intent->id);

        return redirect()->route('approval');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws GuzzleException
     * @throws NotFoundExceptionInterface
     */
    public function handleApproval()
    {
        if (session()->has('paymentIntentId')) {
            $paymentIntentId = session()->get('paymentIntentId');

            $confirmation = $this->confirmPayment($paymentIntentId);

            if ($confirmation->status === 'requires_action') {
                $clientSecret = $confirmation->client_secret;

                return view('stripe.3d-secure')->with([
                    'clientSecret' => $clientSecret,
                ]);
            }

            if ($confirmation->status === 'succeeded') {
                $name = $confirmation->charges->data[0]->billing_details->name;
                $currency = strtoupper($confirmation->currency);
                $amount = $confirmation->amount / $this->resolveFactor($currency);

                return redirect()
                    ->route('home')
                    ->withSuccess(['payment' => "Gracias, $name. Hemos recibido tu pago de $amount $currency."]);
            }
        }

        return redirect()
            ->route('home')
            ->withErrors('No pudimos confirmar su pago. Por favor, inténtalo de nuevo');
    }

    /**
     * @throws GuzzleException
     */
    public function handleSubscription(Request $request)
    {
        $customer = $this->createCustomer(
            $request->user()->name,
            $request->user()->email,
            $request->payment_method
        );

        $subscription = $this->createSubscription(
            $customer->id,
            $request->payment_method,
            $this->plans[$request->plan]
        );

        if ($subscription->status == 'active') {
            session()->put('subscriptionId', $subscription->id);

            return redirect()->route(
                'subscribe.approval',
                [
                    'plan' => $request->plan,
                    'subscription_id' => $subscription->id,
                ],
            );
        }

        $paymentIntent = $subscription->latest_invoice->payment_intent;

        if ($paymentIntent->status === 'requires_action') {
            $clientSecret = $paymentIntent->client_secret;

            session()->put('subscriptionId', $subscription->id);

            return view('stripe.3d-secure-subscription')->with([
                'clientSecret' => $clientSecret,
                'plan' => $request->plan,
                'paymentMethod' => $request->payment_method,
                'subscriptionId' => $subscription->id,
            ]);
        }

        return redirect()->route('subscribe.show')
            ->withErrors('We were unable to activate your subscription. Try again, please.');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function validateSubscription(Request $request): bool
    {
        if (session()->has('subscriptionId')) {
            $subscriptionId = session()->get('subscriptionId');

            session()->forget('subscriptionId');

            return $request->subscription_id == $subscriptionId;
        }

        return false;
    }

    /**
     * @throws GuzzleException
     */
    public function createIntent($value, $currency, $paymentMethod)
    {
        return $this->makeRequest(
            'POST',
            '/v1/payment_intents',
            [],
            [
                'amount' => round($value * $this->resolveFactor($currency)),
                'currency' => strtolower($currency),
                'payment_method' => $paymentMethod,
                'confirmation_method' => 'manual',
            ],
        );
    }

    /**
     * @throws GuzzleException
     */
    public function confirmPayment($paymentIntentId)
    {
        return $this->makeRequest(
            'POST',
            "/v1/payment_intents/$paymentIntentId/confirm",
        );
    }

    /**
     * @throws GuzzleException
     */
    public function createCustomer($name, $email, $paymentMethod)
    {
        return $this->makeRequest(
            'POST',
            '/v1/customers',
            [],
            [
                'name' => $name,
                'email' => $email,
                'payment_method' => $paymentMethod,
            ],
        );
    }

    /**
     * @throws GuzzleException
     */
    public function createSubscription($customerId, $paymentMethod, $priceId)
    {
        return $this->makeRequest(
            'POST',
            '/v1/subscriptions',
            [],
            [
                'customer' => $customerId,
                'items' => [
                    ['price' => $priceId],
                ],
                'default_payment_method' => $paymentMethod,
                'expand' => ['latest_invoice.payment_intent']
            ],
        );
    }

    public function resolveFactor($currency): int
    {
        $zeroDecimalCurrencies = ['JPY'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return 1;
        }

        return 100;
    }
}
