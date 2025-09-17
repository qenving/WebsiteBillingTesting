<?php

namespace Tests\Feature;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\GatewayManager;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PaymentWebhookCsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_webhook_bypasses_csrf_verification(): void
    {
        $manager = new GatewayManager();
        $handled = false;

        $manager->register(new class($handled) implements PaymentGateway {
            private $handledRef;

            public function __construct(& $handled)
            {
                $this->handledRef =& $handled;
            }

            public function key(): string
            {
                return 'mock';
            }

            public function displayName(): string
            {
                return 'Mock Gateway';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function createPayment(Invoice $invoice, array $options = []): Payment
            {
                throw new \LogicException('Not implemented in test double.');
            }

            public function handleWebhook(Request $request): void
            {
                $this->handledRef = true;
            }
        });

        app()->forgetInstance(GatewayManager::class);
        if (method_exists(app(), 'forgetResolvedInstance')) {
            app()->forgetResolvedInstance(GatewayManager::class);
        }
        app()->instance(GatewayManager::class, $manager);

        $response = $this->postJson('/webhook/payments/mock', ['event' => 'test']);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertTrue($handled);
    }
}
