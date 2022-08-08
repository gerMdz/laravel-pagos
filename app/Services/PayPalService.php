<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;

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

    }

    public function decodeResponse($response)
    {

    }
}
