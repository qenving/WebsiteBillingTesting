<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Jobs\ProvisionServiceJob;
use App\Jobs\UnsuspendServiceJob;
use App\Support\ServiceBilling;

class ProvisionServiceOnInvoicePaid
{
    public function handle(InvoicePaid $event)
    {
        $invoice=$event->invoice->loadMissing('items.service');
        $processed=[];
        foreach($invoice->items as $it){
            if(! $it->service) continue;
            $service=$it->service;
            if(in_array($service->id,$processed,true)) continue;
            $processed[]=$service->id;
            if($service->status==='pending'){
                dispatch(new ProvisionServiceJob($service->id));
                continue;
            }
            ServiceBilling::advanceNextDueDate($service);
            $meta=$service->meta??[];
            $meta['last_payment_invoice_id']=$invoice->id;
            $meta['last_payment_at']=now()->toDateTimeString();
            $service->meta=$meta;
            $service->save();
            if($service->status==='suspended'){
                dispatch(new UnsuspendServiceJob($service->id));
            }
        }
    }
}
