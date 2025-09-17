<?php

namespace Tests\Feature;

use App\Jobs\SuspendServiceJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BillingRenewalsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_invoice_triggers_suspension_once_and_updates_meta(): void
    {
        Queue::fake();
        Carbon::setTestNow('2025-01-10 00:00:00');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $client = Client::create(['user_id' => $user->id]);
        $product = Product::create([
            'name' => 'Cloud VPS',
            'slug' => 'cloud-vps',
            'base_price' => 150000,
            'currency' => 'IDR',
            'is_active' => true,
        ]);
        $service = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'next_due_date' => Carbon::now()->addMonth(),
            'meta' => [],
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'overdue',
            'subtotal' => 150000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 150000,
            'currency' => 'IDR',
            'due_date' => Carbon::now()->subDays(3),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_id' => $service->id,
            'description' => 'Renewal',
            'quantity' => 1,
            'unit_price' => 150000,
            'discount' => 0,
            'total' => 150000,
        ]);

        Settings::set('finance.suspension_grace_days', 0, 'int');

        Artisan::call('billing:renewals');

        Queue::assertPushed(SuspendServiceJob::class, function (SuspendServiceJob $job) use ($service) {
            return $job->serviceId === $service->id;
        });
        Queue::assertPushed(SuspendServiceJob::class, 1);

        $this->assertNotNull($service->fresh()->meta['suspension_requested_at'] ?? null);
        $this->assertEquals('overdue', $invoice->fresh()->status);

        Carbon::setTestNow('2025-01-10 01:00:00');
        Artisan::call('billing:renewals');
        Queue::assertPushed(SuspendServiceJob::class, 1);
    }
}
