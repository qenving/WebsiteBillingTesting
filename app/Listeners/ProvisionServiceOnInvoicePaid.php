<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Jobs\ProvisionServiceJob;

class ProvisionServiceOnInvoicePaid
{
    public function handle(InvoicePaid $event)
    {
        $invoice=$event->invoice; $items=$invoice->items()->whereNotNull('service_id')->get();
        foreach($items as $it){ dispatch(new ProvisionServiceJob($it->service_id)); }
    }
}
