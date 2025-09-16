<?php

namespace App\Domain\Provisioning\Drivers;

use App\Domain\Provisioning\Contracts\ProvisioningDriver;
use App\Models\Service;
use RuntimeException;

abstract class AbstractDriver implements ProvisioningDriver
{
    abstract public function key(): string;
    abstract public function displayName(): string;
    public function create(Service $service, array $options = []): void { throw new RuntimeException('Not implemented'); }
    public function suspend(Service $service, array $options = []): void { throw new RuntimeException('Not implemented'); }
    public function unsuspend(Service $service, array $options = []): void { throw new RuntimeException('Not implemented'); }
    public function terminate(Service $service, array $options = []): void { throw new RuntimeException('Not implemented'); }
    public function reboot(Service $service): void { throw new RuntimeException('Not implemented'); }
    public function powerOn(Service $service): void { throw new RuntimeException('Not implemented'); }
    public function powerOff(Service $service): void { throw new RuntimeException('Not implemented'); }
    public function reinstall(Service $service, string $template): void { throw new RuntimeException('Not implemented'); }
    public function resize(Service $service, array $plan): void { throw new RuntimeException('Not implemented'); }
    public function snapshot(Service $service, array $options = []): void { throw new RuntimeException('Not implemented'); }
    public function resetPassword(Service $service): void { throw new RuntimeException('Not implemented'); }
    public function consoleUrl(Service $service): ?string { return null; }
}

