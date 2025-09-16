<?php

namespace App\Domain\Provisioning\Contracts;

use App\Models\Service;

interface ProvisioningDriver
{
    public function key(): string;
    public function displayName(): string;
    public function create(Service $service, array $options = []): void;
    public function suspend(Service $service, array $options = []): void;
    public function unsuspend(Service $service, array $options = []): void;
    public function terminate(Service $service, array $options = []): void;
    public function reboot(Service $service): void;
    public function powerOn(Service $service): void;
    public function powerOff(Service $service): void;
    public function reinstall(Service $service, string $template): void;
    public function resize(Service $service, array $plan): void;
    public function snapshot(Service $service, array $options = []): void;
    public function resetPassword(Service $service): void;
    public function consoleUrl(Service $service): ?string;
}

