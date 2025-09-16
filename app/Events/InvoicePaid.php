<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoicePaid
{
    use Dispatchable, SerializesModels;
    public Invoice $invoice;
    public function __construct(Invoice $invoice){ $this->invoice = $invoice; }
}
