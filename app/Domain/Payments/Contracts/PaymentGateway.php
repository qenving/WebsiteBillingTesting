<?php

namespace App\Domain\Payments\Contracts;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function key(): string;
    public function displayName(): string;
    public function createPayment(Invoice $invoice, array $options = []): Payment;
    public function handleWebhook(Request $request): void;
}

