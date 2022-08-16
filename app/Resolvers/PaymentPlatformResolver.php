<?php

namespace App\Resolvers;

use App\Models\PaymentPlatform;
use Exception;

class PaymentPlatformResolver
{
    protected $paymentPlatform;

    public function __construct()
    {
        $this->paymentPlatform = PaymentPlatform::all();
    }

    /**
     * @throws Exception
     */
    public function resolveService($paymentPlatformId)
    {
        $name = strtolower($this->paymentPlatform->firstWhere('id', $paymentPlatformId)->name);

        $service = config("services.{$name}.class");

        if($service){
            return resolve($service);
        }

        throw new Exception("Error procesando la petición. Plataforma no configurada", 1);
    }
}
