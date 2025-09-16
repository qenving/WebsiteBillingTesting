<?php

namespace App\Domain\Provisioning;

use App\Domain\Provisioning\Contracts\ProvisioningDriver;
use InvalidArgumentException;

class ProvisioningManager
{
    /** @var array<string,ProvisioningDriver> */
    protected array $drivers = [];
    public function register(ProvisioningDriver $d): void { $this->drivers[$d->key()] = $d; }
    public function get(string $key): ProvisioningDriver { if(! isset($this->drivers[$key])) throw new InvalidArgumentException("Driver [$key] not registered"); return $this->drivers[$key]; }
    public function all(): array { return $this->drivers; }
}

