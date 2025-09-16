<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\Invoice;
use App\Models\Payment;

class ManualGateway extends AbstractGateway implements PaymentGateway
{
    public function key(): string { return 'manual'; }
    public function displayName(): string { return 'Bank Transfer (Manual)'; }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $payment = $invoice->payments()->create([
            'gateway' => $this->key(),
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'status' => 'pending',
        ]);

        $instr = \App\Support\Settings::get('payments.manual.instructions');
        if (! $instr) {
            $instr = "Silakan transfer ke rekening berikut lalu kirim bukti pembayaran melalui tiket/WA:\n"
                ."Bank: BCA\nNo: 0000000000\nNama: PT Contoh\nBerita: INV-".$invoice->number;
        }
        $payment->transaction_id = $invoice->number.'-PAY'.$payment->id;
        $payment->meta = array_merge($payment->meta ?? [], [ 'instructions' => $instr ]);
        $payment->save();

        return $payment;
    }
}

