<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

trait ConsumesExternalServices
{
    /**
     * @throws GuzzleException
     */
    public function makeRequest(string $method, string $requestUrl, array $queryParams = [], array $formsParams = [], array $headers = [], bool $isJsonRequest = false): string
    {
        $client = new Client([
            'base_uri' => $this->baseUri
        ]);

        if (method_exists($this, 'resolveAuthorization')) {
            $this->resolveAuthorization($queryParams, $formsParams, $headers);
        }
        $response = $client->request($method, $requestUrl, [
            $isJsonRequest ? 'json' : 'formsParams' => $formsParams,
            'headers' => $headers,
            'query' => $queryParams
        ]);

        $response = $response->getBody()->getContents();

        if (method_exists($this, 'decodeResponse')) {
            $response = $this->decodeResponse($response);
        }

        return $response;
    }


}
