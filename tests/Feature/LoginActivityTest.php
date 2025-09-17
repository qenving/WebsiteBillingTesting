<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_logout_and_failed_events_are_recorded(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        event(new Login('web', $user, false));
        event(new Logout('web', $user));
        event(new Failed('web', $user, ['email' => $user->email]));

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'event' => 'login',
        ]);
        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'event' => 'logout',
        ]);
        $this->assertDatabaseHas('login_activities', [
            'event' => 'failed',
            'identifier' => $user->email,
        ]);
    }
}
