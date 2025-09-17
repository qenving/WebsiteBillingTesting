<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;

class ManualGateway extends AbstractGateway implements PaymentGateway
{
    public function key(): string { return 'manual'; }
    public function displayName(): string { return 'Bank Transfer (Manual)'; }
    public function isConfigured(): bool
    {
        $enabled = \App\Support\ModuleToggle::gateways();
        if (is_array($enabled) && ! empty($enabled) && ! in_array($this->key(), $enabled, true)) {
            return false;
        }

        $instructions = \App\Support\Settings::get('payments.manual.instructions', '');

        return trim((string) $instructions) !== '';
    }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $payment = $invoice->payments()->create([
            'gateway' => $this->key(),
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'status' => 'pending',
        ]);

        $instr = trim((string) \App\Support\Settings::get('payments.manual.instructions', ''));
        if ($instr === '') {
            throw new \RuntimeException('Manual payment instructions are not configured.');
        }
        $payment->transaction_id = $invoice->number.'-PAY'.$payment->id;
        $payment->meta = array_merge($payment->meta ?? [], [ 'instructions' => $instr ]);
        $payment->save();

        return $payment;
    }
}

