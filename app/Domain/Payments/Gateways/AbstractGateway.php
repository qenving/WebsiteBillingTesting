<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use RuntimeException;

abstract class AbstractGateway implements PaymentGateway
{
    abstract public function key(): string;
    abstract public function displayName(): string;
    public function createPayment(Invoice $invoice, array $options = []): Payment { throw new RuntimeException('Not implemented'); }
    public function handleWebhook(Request $request): void { throw new RuntimeException('Not implemented'); }
}

