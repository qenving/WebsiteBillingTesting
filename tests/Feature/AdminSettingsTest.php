<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_instructions_can_be_cleared(): void
    {
        Settings::set('payments.manual.instructions', 'Transfer to account');

        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $payload = [
            'branding_name' => 'Test Billing',
            'manual_instructions' => '',
            'company_name' => 'Test Company',
            'company_address' => '123 Road',
            'company_tax_id' => '123',
            'company_email' => 'billing@example.com',
            'company_phone' => '080000',
            'company_logo_url' => 'https://example.com/logo.png',
            'finance_tax_rate' => '',
            'invoice_number_prefix' => 'INV',
            'invoice_number_date_format' => 'Ymd',
            'invoice_sequence_scope' => 'global',
            'invoice_footer' => 'Thanks',
        ];

        $this->actingAs($admin)->post('/admin/settings', $payload)->assertRedirect();

        $this->assertSame('', Settings::get('payments.manual.instructions'));
        $this->assertSame(0.0, Settings::get('finance.tax_rate'));
    }
}
