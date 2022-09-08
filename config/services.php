<?php

use App\Services\MercadoPagoService;
use App\Services\PayPalService;
use App\Services\PayUService;
use App\Services\StripeService;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'currency_conversion' => [
        'base_uri' => env('CURRENCY_CONVERSION_BASE_URI'),
        'api_key' => env('CURRENCY_CONVERSION_API_KEY'),
        'api_client' => env('CURRENCY_CONVERSION_API_CLIENT'),
        'api_path' => env('CURRENCY_CONVERSION_PATH'),
        'class' => PayPalService::class,

    ],
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],
    'mercadopago' => [
        'base_uri' => env('MERCADO_PAGO_BASE_URI'),
        'key' => env('MERCADO_PAGO_KEY'),
        'secret' => env('MERCADO_PAGO_CLIENT_SECRET'),
        'class' => MercadoPagoService::class,
        'base_currency' => env('MERCADO_PAGO_CURRENCY'),
    ],
    'paypal' => [
        'base_uri' => env('PAYPAL_BASE_URI'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'class' => PayPalService::class,
    ],
    'payu' => [
        'base_uri' => env('PAYU_BASE_URI'),
        'account_id' => env('PAYU_ACCOUNT_ID'),
        'merchant_id' => env('PAYU_MERCHANT_ID'),
        'class' => PayUService::class,
        'base_currency' => env('PAYU_CURRENCY'),
        'key' => env('PAYU_API_KEY'),
        'secret' => env('PAYU_API_LOGIN'),
    ],
    'stripe' => [
        'base_uri' => env('STRIPE_BASE_URI'),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'class' => StripeService::class,

    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
