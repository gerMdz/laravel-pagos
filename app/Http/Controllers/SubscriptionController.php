<?php

namespace App\Http\Controllers;

use App\Models\PaymentPlatform;
use App\Models\Plan;
use App\Resolvers\PaymentPlatformResolver;
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
        $this->middleware('auth');

        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }


    public function show()
    {
        $paymentPlatforms = PaymentPlatform:: /* where('subscriptions_enable', true)->get();*/
        get();

        return view('subscribe')->with([
           'plans' => Plan::all(),
           'paymentPlatforms' => $paymentPlatforms
        ]);


    }

    public function store()
    {

    }

    public function approval()
    {

    }

    public function cancelled()
    {

    }
}
