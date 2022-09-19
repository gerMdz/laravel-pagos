<?php

namespace App\Http\Controllers;

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
