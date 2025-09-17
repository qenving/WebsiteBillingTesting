<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_product_cannot_be_ordered(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::create([
            'name' => 'Legacy Plan',
            'slug' => 'legacy-plan',
            'description' => 'Old plan',
            'base_price' => 120000,
            'currency' => 'IDR',
            'options' => [],
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->postJson('/orders', [
            'product_id' => $product->id,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertStatus(404);
    }
}
