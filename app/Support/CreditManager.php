<?php

namespace App\Support;

use App\Models\Client;
use App\Models\ClientCreditTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CreditManager
{
    public static function addCredit(Client $client, float $amount, string $description = '', ?int $adminId = null, array $meta = []): ClientCreditTransaction
    {
        return static::adjust($client, abs($amount), 'credit', $description, $adminId, $meta);
    }

    public static function deductCredit(Client $client, float $amount, string $description = '', ?int $adminId = null, array $meta = []): ClientCreditTransaction
    {
        return static::adjust($client, abs($amount), 'debit', $description, $adminId, $meta);
    }

    protected static function adjust(Client $client, float $amount, string $type, string $description = '', ?int $adminId = null, array $meta = []): ClientCreditTransaction
    {
        return DB::transaction(function () use ($client, $amount, $type, $description, $adminId, $meta) {
            $freshClient = Client::whereKey($client->id)->lockForUpdate()->firstOrFail();

            if ($type === 'debit' && $freshClient->credit_balance < $amount) {
                $amount = $freshClient->credit_balance;
            }

            if ($amount <= 0) {
                return new ClientCreditTransaction([
                    'client_id' => $freshClient->id,
                    'type' => $type,
                    'amount' => 0.0,
                    'balance_after' => $freshClient->credit_balance,
                ]);
            }

            if ($type === 'credit') {
                $freshClient->credit_balance = round($freshClient->credit_balance + $amount, 2);
            } else {
                $freshClient->credit_balance = round($freshClient->credit_balance - $amount, 2);
            }
            $freshClient->save();

            $transaction = $freshClient->creditTransactions()->create([
                'type' => $type,
                'amount' => round($amount, 2),
                'balance_after' => $freshClient->credit_balance,
                'description' => $description,
                'meta' => $meta,
                'created_by' => $adminId,
            ]);

            $client->refresh();

            return $transaction;
        });
    }

    public static function applyToInvoice(Invoice $invoice, ?float $maxAmount = null, ?int $adminId = null, string $description = ''): float
    {
        $client = $invoice->client;
        if (! $client) {
            return 0.0;
        }

        return DB::transaction(function () use ($invoice, $client, $maxAmount, $adminId, $description) {
            $freshClient = Client::whereKey($client->id)->lockForUpdate()->firstOrFail();
            $freshInvoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $outstanding = $freshInvoice->balanceDue();
            $available = $freshClient->credit_balance;
            if ($maxAmount !== null) {
                $available = min($available, $maxAmount);
            }
            $apply = round(min($available, $outstanding), 2);

            if ($apply <= 0) {
                return 0.0;
            }

            $freshClient->credit_balance = round($freshClient->credit_balance - $apply, 2);
            $freshClient->save();

            $transaction = $freshClient->creditTransactions()->create([
                'type' => 'debit',
                'amount' => $apply,
                'balance_after' => $freshClient->credit_balance,
                'description' => $description ?: 'Applied to invoice '.$freshInvoice->number,
                'meta' => ['invoice_id' => $freshInvoice->id],
                'created_by' => $adminId,
            ]);

            $payment = Payment::create([
                'invoice_id' => $freshInvoice->id,
                'gateway' => 'credit',
                'amount' => $apply,
                'currency' => $freshInvoice->currency,
                'status' => 'completed',
                'paid_at' => now(),
                'meta' => ['credit_transaction_id' => $transaction->id],
            ]);

            $freshInvoice->refreshPaymentStatus();

            $client->refresh();
            $invoice->refresh();

            return $apply;
        });
    }
}
