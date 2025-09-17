<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Support\CreditManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function createInvoice(User $user, float $total): Invoice
    {
        $client = Client::create(['user_id' => $user->id, 'credit_balance' => 0]);
        $product = Product::create([
            'name' => 'Dedicated Server',
            'slug' => 'dedicated',
            'base_price' => $total,
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => $total,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => $total,
            'currency' => 'IDR',
            'due_date' => now()->addDay(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'service_id' => null,
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => $total,
            'discount' => 0,
            'total' => $total,
        ]);

        return $invoice->fresh(['client']);
    }

    public function test_credit_can_fully_pay_invoice(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $invoice = $this->createInvoice($user, 200000);
        CreditManager::addCredit($invoice->client, 250000, 'Initial credit', $user->id);

        $response = $this->actingAs($user)->post(route('invoices.apply-credit', $invoice));
        $response->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(50000.00, $invoice->client->fresh()->credit_balance);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'gateway' => 'credit',
            'status' => 'completed',
            'amount' => 200000.00,
        ]);
    }

    public function test_partial_credit_reduces_balance_due(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $invoice = $this->createInvoice($user, 150000);
        CreditManager::addCredit($invoice->client, 50000, 'Partial credit', $user->id);

        $this->actingAs($user)->post(route('invoices.apply-credit', $invoice))->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertEquals(0.0, $invoice->client->fresh()->credit_balance);
        $this->assertEquals(100000.00, $invoice->balanceDue());
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'gateway' => 'credit',
            'amount' => 50000.00,
        ]);
    }
}
