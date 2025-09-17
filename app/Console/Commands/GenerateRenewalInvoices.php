<?php

namespace App\Console\Commands;

use App\Jobs\SuspendServiceJob;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRenewalInvoices extends Command
{
    protected $signature = 'billing:renewals';
    protected $description = 'Generate renewal invoices and handle overdue services';

    public function handle(): int
    {
        $now = Carbon::now();
        $leadDays = (int) Settings::get('finance.renewal_lead_days', 5);
        $graceDays = (int) Settings::get('finance.suspension_grace_days', 3);

        $this->generateUpcomingInvoices($leadDays, $now);
        $this->markOverdueInvoices($graceDays, $now);

        return 0;
    }

    protected function generateUpcomingInvoices(int $leadDays, Carbon $now): void
    {
        $services = Service::with(['product', 'client'])
            ->where('status', 'active')
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $now->copy()->addDays($leadDays))
            ->get();

        foreach ($services as $service) {
            if (! $service->product || ! $service->client) {
                continue;
            }
            $alreadyExists = InvoiceItem::where('service_id', $service->id)
                ->whereHas('invoice', function ($query) use ($service) {
                    $query->whereIn('status', ['unpaid', 'overdue'])
                        ->whereDate('due_date', $service->next_due_date);
                })
                ->exists();
            if ($alreadyExists) {
                continue;
            }

            DB::transaction(function () use ($service) {
                $product = $service->product;
                $subtotal = round((float) $product->base_price, 2);
                $taxRate = (float) Settings::get('finance.tax_rate', 0);
                $taxTotal = $taxRate > 0 ? round($subtotal * ($taxRate / 100), 2) : 0.0;
                $discount = 0.0;
                $total = round($subtotal - $discount + $taxTotal, 2);

                $invoice = Invoice::create([
                    'client_id' => $service->client_id,
                    'status' => 'unpaid',
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'discount_total' => $discount,
                    'tax_rate' => $taxRate,
                    'total' => $total,
                    'currency' => $product->currency,
                    'due_date' => $service->next_due_date,
                    'notes' => 'Renewal for service #'.$service->id,
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'service_id' => $service->id,
                    'description' => $product->name.' renewal ('.$service->billing_cycle.')',
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'discount' => $discount,
                    'total' => $subtotal - $discount,
                    'meta' => ['type' => 'renewal'],
                ]);

                $meta = $service->meta ?? [];
                $meta['last_invoice_generated_at'] = Carbon::now()->toDateTimeString();
                $meta['last_invoice_id'] = $invoice->id;
                $service->meta = $meta;
                $service->save();
            });
        }
    }

    protected function markOverdueInvoices(int $graceDays, Carbon $now): void
    {
        $invoices = Invoice::with(['items.service'])
            ->whereIn('status', ['unpaid', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $now->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            $wasOverdue = $invoice->status === 'overdue';
            if (! $wasOverdue) {
                $invoice->status = 'overdue';
                $invoice->save();
            }
            foreach ($invoice->items as $item) {
                if (! $item->service) {
                    continue;
                }
                $service = $item->service;
                if (in_array($service->status, ['terminated', 'suspended'], true)) {
                    continue;
                }
                $meta = $service->meta ?? [];
                if (isset($meta['suspension_requested_at'])) {
                    try {
                        $requestedAt = Carbon::parse($meta['suspension_requested_at']);
                        if ($requestedAt && $requestedAt->greaterThan($now->copy()->subHours(6))) {
                            continue;
                        }
                    } catch (\Throwable $e) {
                        // ignore parse errors and requeue suspension
                    }
                }
                if ($graceDays <= 0) {
                    SuspendServiceJob::dispatch($service->id);
                    $meta['suspension_requested_at'] = $now->toDateTimeString();
                    $service->meta = $meta;
                    $service->save();
                    continue;
                }
                $duePlusGrace = $invoice->due_date ? $invoice->due_date->copy()->addDays($graceDays) : null;
                if ($duePlusGrace && $duePlusGrace->lte($now)) {
                    SuspendServiceJob::dispatch($service->id);
                    $meta['suspension_requested_at'] = $now->toDateTimeString();
                    $service->meta = $meta;
                    $service->save();
                }
            }
        }
    }
}
