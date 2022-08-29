<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrencyConversionService
{
    use ConsumesExternalServices;

    protected $baseUri;

    /**
     * @var Repository|Application|mixed
     */
    private $apiKey;
    /**
     * @var Repository|Application|mixed
     */
    private $apipath;

    public function __construct()
    {
        $this->baseUri = config('services.currency_conversion.base_uri');
        $this->apiKey = config('services.currency_conversion.api_key');
        $this->apipath = config('services.currency_conversion.api_path');

    }

    public function resolveAuthorization(&$queryParams, &$formsParams, &$headers)
    {
//        $queryParams[$this->resolveAccessToken()];
    }

    public function resolveRequestUrl()
    {
        return $this->makeRequest(
            'GET',
            $this->apiKey.$this->apipath,
            [],
            []
        );

    }

    public function decodeResponse($response)
    {
        return json_decode($response);
    }

    public function resolveAccessToken()
    {
        return $this->apiKey;
    }

    public function convertCurrency($from, $to)
    {
        //
    }




}
