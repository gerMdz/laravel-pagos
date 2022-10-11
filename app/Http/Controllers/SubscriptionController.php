<?php

namespace App\Http\Controllers;

use App\Models\PaymentPlatform;
use App\Models\Plan;
use App\Models\Subscription;
use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * @var PaymentPlatformResolver
     */
    private $paymentPlatformResolver;

    /**
     * @param PaymentPlatformResolver $paymentPlatformResolver
     */
    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware(['auth', 'unsubscribed']);

        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }


    public function show()
    {
        $paymentPlatforms = PaymentPlatform::where('subscriptions_enabled', true)->get();

        return view('subscribe')->with([
            'plans' => Plan::all(),
            'paymentPlatforms' => $paymentPlatforms
        ]);

    }

    public function store(Request $request)
    {
        $rules = [
            'plan' => ['required', 'exists:plans,slug'],
            'payment_platform' => ['required', 'exists:payment_platforms,id'],
        ];

        $request->validate($rules);

        $paymentPlatform = $this->paymentPlatformResolver
            ->resolveService($request->payment_platform);

        session()->put('subscriptionPlatformId', $request->payment_platform);

        return $paymentPlatform->handleSubscription($request);
    }

    public function approval(Request $request)
    {
        $rules = [
            'plan' => ['required', 'exists:plans, slug']
        ];
        $request->validate($rules);

        if (session()->has('subscriptionPlatformId')) {

            $paymentPlatform = $this->paymentPlatformResolver
                ->resolveService(session()->get('subscriptionPlatformId'));
            if ($paymentPlatform->validateSubscription($request)) {


                $plan = Plan::where('slug', $request->plan)->firstOrFail();
                $user = $request->user();


                $subscription = Subscription::create([
                    'active_until' => now()->addDays($plan->duracion_in_days),
                    'user_id' => $user->id,
                    'plan_id' => $plan->id
                ]);

                return redirect()
                    ->route('home')
                    ->withSuccess(['payment' => "Gracias {$user->name} por subscribirte. Tu plan {$plan->slug} ya está disponible. Disfrútalo "]);
            }
        }

        return redirect()
            ->route('subscribe.show')
            ->withErrors('No pudimos verificar tu subscripción ');


    }

    public function cancelled(): RedirectResponse
    {
        return redirect()
            ->route('subscribe.show')
            ->withErrors('Proceso cancelado. Vuelve pronto :)');
    }
}
