<?php

namespace Tests\Feature;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\GatewayManager;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshGatewayManager();
    }

    protected function refreshGatewayManager(): void
    {
        app()->singleton(GatewayManager::class, function () {
            return new GatewayManager();
        });
    }

    public function test_gateway_errors_are_sanitized(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $client = Client::create(['user_id' => $user->id]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'unpaid',
            'subtotal' => 150000,
            'tax_total' => 0,
            'discount_total' => 0,
            'tax_rate' => 0,
            'total' => 150000,
            'currency' => 'IDR',
            'due_date' => now()->addDay(),
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Hosting',
            'quantity' => 1,
            'unit_price' => 150000,
            'discount' => 0,
            'total' => 150000,
        ]);

        $gateway = new class implements PaymentGateway {
            public function key(): string { return 'faulty'; }
            public function displayName(): string { return 'Faulty Gateway'; }
            public function createPayment(Invoice $invoice, array $options = []): \App\Models\Payment
            {
                throw new RuntimeException('API outage: sensitive details');
            }
            public function handleWebhook(\Illuminate\Http\Request $request): void {}
            public function isConfigured(): bool { return true; }
        };
        app(GatewayManager::class)->register($gateway);

        $response = $this->actingAs($user)->postJson('/pay/faulty/'.$invoice->id, []);
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Unable to create payment, please try again or contact support.']);
    }
}
