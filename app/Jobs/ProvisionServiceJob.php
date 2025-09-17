<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Provisioning\ProvisioningManager;
use App\Models\Service;
use App\Support\ServiceBilling;

class ProvisionServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $serviceId; public $tries=3; public function backoff(){ return [10,30,60]; }
    public function __construct(int $serviceId){ $this->serviceId=$serviceId; }
    public function handle(ProvisioningManager $mgr)
    {
        $service=Service::find($this->serviceId); if(! $service || $service->status!=='pending') return;
        $driverKey=$service->meta['driver']??'virtfusion'; $driver=$mgr->get($driverKey); $driver->create($service);
        $service->status='active';
        if(! $service->next_due_date){ $service->next_due_date=ServiceBilling::initialDueDate($service); }
        $meta=$service->meta??[]; $meta['last_activated_at']=now()->toDateTimeString(); $meta['driver']=$driverKey; $service->meta=$meta;
        $service->save();
    }
}
