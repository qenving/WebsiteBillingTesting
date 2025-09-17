<?php

namespace Tests\Feature;

use App\Domain\Payments\GatewayManager;
use App\Events\InvoicePaid;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentGatewayVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function resetGateways(): void
    {
        app()->forgetInstance(GatewayManager::class);
        if (method_exists(app(), 'forgetResolvedInstance')) {
            app()->forgetResolvedInstance(GatewayManager::class);
        }
    }

    protected function createUser(bool $admin = false): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => $admin,
        ]);
    }

    public function test_invoice_page_shows_only_configured_gateways(): void
    {
        Settings::set('payments.manual.instructions', 'Transfer to account 123456 an. PT Contoh.');
        Settings::set('payments.enabled', ['manual'], 'json');
        config([
            'midtrans.server_key' => null,
            'midtrans.client_key' => null,
            'xendit.api_key' => null,
            'xendit.callback_token' => null,
            'tripay.api_key' => null,
            'tripay.private_key' => null,
            'tripay.merchant_code' => null,
            'duitku.merchant_code' => null,
            'duitku.api_key' => null,
            'stripe.secret' => null,
            'stripe.public' => null,
        ]);
        $this->resetGateways();

        $user = $this->createUser();
        $client = Client::create(['user_id' => $user->id]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => 100000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 100000,
            'currency' => 'IDR',
            'due_date' => now()->addDay(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount' => 0,
            'total' => 100000,
        ]);

        $response = $this->actingAs($user)->get('/invoices/'.$invoice->id);
        $response->assertStatus(200);
        $response->assertSee('Bank Transfer (Manual)');
        $response->assertDontSee('Midtrans');
        $response->assertDontSee('Xendit');
        $response->assertDontSee('Tripay');
        $response->assertDontSee('Duitku');
        $response->assertDontSee('Stripe');
    }

    public function test_manual_gateway_hidden_when_instructions_missing(): void
    {
        Settings::set('payments.manual.instructions', '');
        Settings::set('payments.enabled', ['manual'], 'json');
        $this->resetGateways();

        $user = $this->createUser();
        $client = Client::create(['user_id' => $user->id]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => 50000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 50000,
            'currency' => 'IDR',
            'due_date' => now()->addDay(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Manual Test',
            'quantity' => 1,
            'unit_price' => 50000,
            'discount' => 0,
            'total' => 50000,
        ]);

        $response = $this->actingAs($user)->get('/invoices/'.$invoice->id);
        $response->assertStatus(200);
        $response->assertDontSee('Bank Transfer (Manual)');
    }

    public function test_manual_gateway_hidden_when_disabled_via_settings(): void
    {
        Settings::set('payments.manual.instructions', 'Transfer instructions available.');
        Settings::set('payments.enabled', ['midtrans'], 'json');
        $this->resetGateways();

        $user = $this->createUser();
        $client = Client::create(['user_id' => $user->id]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => 75000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 75000,
            'currency' => 'IDR',
            'due_date' => now()->addDay(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Manual Disabled Test',
            'quantity' => 1,
            'unit_price' => 75000,
            'discount' => 0,
            'total' => 75000,
        ]);

        $response = $this->actingAs($user)->get('/invoices/'.$invoice->id);
        $response->assertStatus(200);
        $response->assertDontSee('Bank Transfer (Manual)');
    }

    public function test_unconfigured_gateway_cannot_be_used(): void
    {
        config(['xendit.api_key' => null, 'xendit.callback_token' => null]);
        $this->resetGateways();

        $user = $this->createUser();
        $client = Client::create(['user_id' => $user->id]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => 100000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 100000,
            'currency' => 'IDR',
            'due_date' => now()->addDay(),
        ]);

        $response = $this->from('/invoices/'.$invoice->id)->actingAs($user)->postJson('/pay/xendit/'.$invoice->id, []);
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Payment gateway unavailable']);
    }

    public function test_renewal_invoice_is_generated_and_service_due_date_moves_after_payment(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');
        $user = $this->createUser();
        $client = Client::create(['user_id' => $user->id]);
        $product = Product::create([
            'name' => 'VPS Small',
            'slug' => 'vps-small',
            'description' => 'Test plan',
            'base_price' => 150000,
            'currency' => 'IDR',
            'options' => ['cpu' => 1],
            'is_active' => true,
        ]);
        $service = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'next_due_date' => Carbon::now()->addDays(2),
            'meta' => ['driver' => 'virtfusion'],
        ]);

        $this->artisan('billing:renewals');
        $invoice = Invoice::where('client_id', $client->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals($service->next_due_date->format('Y-m-d'), optional($invoice->due_date)->format('Y-m-d'));
        $this->assertCount(1, $invoice->items);
        $this->assertEquals($service->id, $invoice->items->first()->service_id);

        $this->artisan('billing:renewals');
        $this->assertEquals(1, Invoice::where('client_id', $client->id)->count());

        $invoice->refresh();
        $invoice->status = 'paid';
        $invoice->paid_at = Carbon::now();
        $invoice->save();
        event(new InvoicePaid($invoice));
        $service->refresh();
        $this->assertEquals('2025-02-03', optional($service->next_due_date)->format('Y-m-d'));
        Carbon::setTestNow();
    }

    public function test_overdue_invoices_dispatch_suspend_job(): void
    {
        Carbon::setTestNow('2025-01-10 00:00:00');
        Queue::fake();
        $user = $this->createUser();
        $client = Client::create(['user_id' => $user->id]);
        $product = Product::create([
            'name' => 'VPS Medium',
            'slug' => 'vps-medium',
            'description' => 'Plan',
            'base_price' => 200000,
            'currency' => 'IDR',
            'options' => ['cpu' => 2],
            'is_active' => true,
        ]);
        $service = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'next_due_date' => Carbon::now()->addMonth(),
            'meta' => ['driver' => 'virtfusion'],
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => 200000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 200000,
            'currency' => 'IDR',
            'due_date' => Carbon::now()->subDays(5),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_id' => $service->id,
            'description' => 'Renewal',
            'quantity' => 1,
            'unit_price' => 200000,
            'discount' => 0,
            'total' => 200000,
        ]);

        $this->artisan('billing:renewals');
        $invoice->refresh();
        $this->assertEquals('overdue', $invoice->status);
        Queue::assertPushed(\App\Jobs\SuspendServiceJob::class, function ($job) use ($service) {
            return $job->serviceId === $service->id;
        });
        Carbon::setTestNow();
    }

    public function test_manual_payment_confirmation_marks_invoice_paid(): void
    {
        Event::fake([InvoicePaid::class]);
        $admin = $this->createUser(true);
        $client = Client::create(['user_id' => $admin->id]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => 50000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 50000,
            'currency' => 'IDR',
            'due_date' => now()->addDay(),
        ]);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'gateway' => 'manual',
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->from('/admin/payments')->post('/admin/payments/'.$payment->id.'/confirm');
        $response->assertRedirect('/admin/payments');
        $response->assertSessionHas('status');

        $this->assertEquals('completed', $payment->fresh()->status);
        $this->assertEquals('paid', $invoice->fresh()->status);
        Event::assertDispatched(InvoicePaid::class);
        $this->assertDatabaseHas('payment_logs', [
            'payment_id' => $payment->id,
            'event' => 'manual_confirmed',
        ]);
    }
}
