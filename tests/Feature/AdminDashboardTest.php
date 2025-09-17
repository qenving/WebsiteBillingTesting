<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_with_metrics(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $clientUser = User::factory()->create(['email_verified_at' => now()]);
        $client = Client::create(['user_id' => $clientUser->id]);
        $product = Product::create([
            'name' => 'VPS Basic',
            'slug' => 'vps-basic',
            'description' => 'Starter plan',
            'base_price' => 150000,
            'currency' => 'IDR',
            'options' => ['cpu' => 1],
            'is_active' => true,
        ]);
        Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'next_due_date' => Carbon::now()->addDays(5),
            'meta' => ['driver' => 'virtfusion'],
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'status' => 'paid',
            'subtotal' => 150000,
            'tax_total' => 0,
            'discount_total' => 0,
            'total' => 150000,
            'tax_rate' => 0,
            'currency' => 'IDR',
            'due_date' => Carbon::now()->addDays(5),
            'paid_at' => Carbon::now(),
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'gateway' => 'manual',
            'amount' => 150000,
            'currency' => 'IDR',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSee('Admin Dashboard')
            ->assertSee('Active Services')
            ->assertSee('Revenue (last 6 months)')
            ->assertSee('Upcoming Renewals');
    }

    public function test_non_admin_is_denied(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'is_admin' => false]);

        $this->actingAs($user)->get('/admin/dashboard')->assertStatus(403);
    }
}
