<?php

namespace Tests\Feature;

use App\Domain\Provisioning\Contracts\ProvisioningDriver;
use App\Domain\Provisioning\ProvisioningManager;
use App\Models\Client;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function createServiceForUser(User $user, array $attributes = []): Service
    {
        $client = Client::create(['user_id' => $user->id]);
        $product = Product::create([
            'name' => 'Compute Plan',
            'slug' => 'compute-plan',
            'description' => 'Test service',
            'base_price' => 100000,
            'currency' => 'IDR',
            'options' => ['cpu' => 1],
            'is_active' => true,
        ]);
        return Service::create(array_merge([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'next_due_date' => now()->addMonth(),
            'meta' => ['driver' => 'virtfusion', 'ip' => '192.0.2.10', 'password' => 'initial'],
        ], $attributes));
    }

    protected function createVerifiedUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
        ], $attributes));
    }

    public function test_owner_can_trigger_service_actions(): void
    {
        $user = $this->createVerifiedUser();
        $service = $this->createServiceForUser($user);

        $this->from('/services')->actingAs($user)->post('/services/'.$service->id.'/actions/reboot')
            ->assertRedirect('/services')
            ->assertSessionHas('status', 'Reboot command sent');

        $this->from('/services')->actingAs($user)->post('/services/'.$service->id.'/actions/reset-password')
            ->assertRedirect('/services')
            ->assertSessionHas('status');

        $service->refresh();
        $this->assertNotEquals('initial', $service->meta['password']);
    }

    public function test_non_owner_cannot_invoke_actions(): void
    {
        $owner = $this->createVerifiedUser();
        $service = $this->createServiceForUser($owner);
        $other = $this->createVerifiedUser();

        $this->actingAs($other)->post('/services/'.$service->id.'/actions/reboot')->assertStatus(403);
    }

    public function test_console_redirects_to_driver_console_url(): void
    {
        $user = $this->createVerifiedUser();
        $service = $this->createServiceForUser($user);

        $this->actingAs($user)->get('/services/'.$service->id.'/actions/console')
            ->assertRedirect('about:blank');
    }

    public function test_service_action_failure_returns_generic_error(): void
    {
        $user = $this->createVerifiedUser();
        $service = $this->createServiceForUser($user, ['meta' => ['driver' => 'failing']]);

        app()->forgetInstance(ProvisioningManager::class);
        if (method_exists(app(), 'forgetResolvedInstance')) {
            app()->forgetResolvedInstance(ProvisioningManager::class);
        }

        $manager = new ProvisioningManager();
        $manager->register(new class implements ProvisioningDriver {
            public function key(): string { return 'failing'; }
            public function displayName(): string { return 'Failing Driver'; }
            public function create(Service $service, array $options = []): void {}
            public function suspend(Service $service, array $options = []): void {}
            public function unsuspend(Service $service, array $options = []): void {}
            public function terminate(Service $service, array $options = []): void {}
            public function reboot(Service $service): void { throw new \RuntimeException('driver exploded'); }
            public function powerOn(Service $service): void {}
            public function powerOff(Service $service): void {}
            public function reinstall(Service $service, string $template): void {}
            public function resize(Service $service, array $plan): void {}
            public function snapshot(Service $service, array $options = []): void {}
            public function resetPassword(Service $service): void {}
            public function consoleUrl(Service $service): ?string { return 'about:blank'; }
        });
        app()->instance(ProvisioningManager::class, $manager);

        $this->from('/services')->actingAs($user)->post('/services/'.$service->id.'/actions/reboot')
            ->assertRedirect('/services')
            ->assertSessionHas('error', 'Unable to complete the requested action. Please try again or contact support.');
    }

    public function test_console_failure_is_sanitized(): void
    {
        $user = $this->createVerifiedUser();
        $service = $this->createServiceForUser($user, ['meta' => ['driver' => 'failing']]);

        app()->forgetInstance(ProvisioningManager::class);
        if (method_exists(app(), 'forgetResolvedInstance')) {
            app()->forgetResolvedInstance(ProvisioningManager::class);
        }

        $manager = new ProvisioningManager();
        $manager->register(new class implements ProvisioningDriver {
            public function key(): string { return 'failing'; }
            public function displayName(): string { return 'Failing Driver'; }
            public function create(Service $service, array $options = []): void {}
            public function suspend(Service $service, array $options = []): void {}
            public function unsuspend(Service $service, array $options = []): void {}
            public function terminate(Service $service, array $options = []): void {}
            public function reboot(Service $service): void {}
            public function powerOn(Service $service): void {}
            public function powerOff(Service $service): void {}
            public function reinstall(Service $service, string $template): void {}
            public function resize(Service $service, array $plan): void {}
            public function snapshot(Service $service, array $options = []): void {}
            public function resetPassword(Service $service): void {}
            public function consoleUrl(Service $service): ?string { throw new \RuntimeException('console offline'); }
        });
        app()->instance(ProvisioningManager::class, $manager);

        $this->from('/services')->actingAs($user)->get('/services/'.$service->id.'/actions/console')
            ->assertRedirect('/services')
            ->assertSessionHas('error', 'Unable to open the console right now. Please try again or contact support.');
    }
}
