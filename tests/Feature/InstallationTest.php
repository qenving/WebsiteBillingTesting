<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EnvWriter;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.installed' => false]);
    }

    protected function tearDown(): void
    {
        EnvWriter::usePath(null);
        parent::tearDown();
    }

    public function test_installation_wizard_creates_admin_and_marks_app_installed(): void
    {
        $tempEnv = base_path('tests/temp_install.env');
        if (file_exists($tempEnv)) {
            unlink($tempEnv);
        }
        EnvWriter::usePath($tempEnv);

        $this->get('/install')->assertStatus(200)->assertSee('Initial Configuration');

        $response = $this->post('/install', [
            'app_name' => 'My Billing Suite',
            'app_url' => 'https://billing.test',
            'admin_name' => 'Super Admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'secret1234',
            'admin_password_confirmation' => 'secret1234',
            'company_name' => 'Acme Hosting',
            'company_email' => 'finance@acme.test',
            'company_phone' => '+62 8123456789',
            'company_address' => 'Jl. Kebon Jeruk No. 42',
            'company_tax_id' => 'NPWP-123',
            'currency' => 'IDR',
            'tax_rate' => '11',
            'manual_instructions' => 'Transfer ke Bank BCA 123456789 a.n ACME.',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_admin);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('secret1234', $admin->password));

        $this->assertEquals('My Billing Suite', Settings::get('branding.name'));
        $this->assertEquals('Acme Hosting', Settings::get('company.name'));
        $this->assertEquals('IDR', Settings::get('company.currency'));
        $this->assertEquals(['manual'], Settings::get('payments.enabled'));
        $this->assertEquals(11.0, Settings::get('finance.tax_rate'));

        $envContent = file_get_contents($tempEnv);
        $this->assertStringContainsString('APP_INSTALLED=true', $envContent);
        $this->assertStringContainsString('APP_NAME="My Billing Suite"', $envContent);
        if (file_exists($tempEnv)) {
            unlink($tempEnv);
        }

        config(['app.installed' => true]);
    }
}
