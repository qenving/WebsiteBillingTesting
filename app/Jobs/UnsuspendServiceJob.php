<?php

namespace App\Jobs;

use App\Domain\Provisioning\ProvisioningManager;
use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UnsuspendServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $serviceId;
    public $tries = 3;

    public function __construct(int $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(ProvisioningManager $manager): void
    {
        $service = Service::find($this->serviceId);
        if (! $service) {
            return;
        }
        if ($service->status !== 'suspended') {
            return;
        }
        $driverKey = $service->meta['driver'] ?? 'virtfusion';
        $driver = $manager->get($driverKey);
        $driver->unsuspend($service);
        $service->status = 'active';
        $service->suspended_at = null;
        $service->save();
    }
}
