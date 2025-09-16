<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use InvalidArgumentException;

class GatewayManager
{
    /** @var array<string,PaymentGateway> */
    protected array $gateways = [];

    public function register(PaymentGateway $g): void { $this->gateways[$g->key()] = $g; }
    public function get(string $key): PaymentGateway
    {
        if (! isset($this->gateways[$key])) throw new InvalidArgumentException("Gateway [$key] not registered");
        return $this->gateways[$key];
    }
    public function all(): array { return $this->gateways; }
}

