<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class GatewayFactory
{
    private static array $map = [
        'manual'  => ManualGateway::class,
        'vnpay'   => VNPayGateway::class,
        'momo'    => MoMoGateway::class,
        'zalopay' => ZaloPayGateway::class,
    ];

    public static function make(string $provider): PaymentGatewayInterface
    {
        $class = self::$map[$provider] ?? null;

        if (!$class) {
            throw new InvalidArgumentException("Payment provider '{$provider}' không được hỗ trợ.");
        }

        return new $class();
    }

    public static function supportedProviders(): array
    {
        return array_keys(self::$map);
    }
}
