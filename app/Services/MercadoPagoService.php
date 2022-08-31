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

    public function __construct()
    {
        $this->baseUri = config('services.mercadopago.base_uri');
        $this->clientKey = config('services.mercadopago.key');
        $this->clientSecret = config('services.mercadopago.secret');
        $this->clientCurrency = config('services.mercadopago.base_currency');
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


    public function handlePayment(Request $request)
    {
      //
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

    public function resolveFactor($currency):void
    {
        //
    }
}
