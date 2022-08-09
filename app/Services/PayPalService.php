<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;

class PayPalService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUri = config('service.paypal.base_uri');
        $this->clientId = config('service.paypal.client_id');
        $this->clientSecret = config('service.paypal.client_secret');
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
    public function createOrder($value, $currency): string
    {
        return $this->makeRequest(
            'POST',
            '/v2/checkout/orders',
            [],
            [
                'intent' => 'CAPTURE',
                "purchase_units" => [
                    0 => [
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => $value
                        ],
                    ],
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('approval'),
                    'cancel_url' => route('cancelled'),
                ],
            ],
            [],
            true
        );
    }
}
