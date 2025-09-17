<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_and_admin_ticket_flow(): void
    {
        $clientUser = User::factory()->create(['email_verified_at' => now()]);
        $client = Client::create(['user_id' => $clientUser->id]);
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        $this->actingAs($clientUser)->post(route('tickets.store'), [
            'subject' => 'Server down',
            'department' => 'Support',
            'priority' => 'high',
            'message' => 'My server is down, please help.',
        ])->assertRedirect();

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals('open', $ticket->status);
        $this->assertCount(1, $ticket->replies);

        $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), [
            'message' => 'We are investigating.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('answered', $ticket->status);
        $this->assertCount(2, $ticket->replies);
        $this->assertEquals($admin->id, $ticket->last_reply_by);

        $this->actingAs($clientUser)->post(route('tickets.reply', $ticket), [
            'message' => 'Thank you for the update.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('open', $ticket->status);
        $this->assertCount(3, $ticket->replies);

        $this->actingAs($admin)->post(route('admin.tickets.status', $ticket), [
            'status' => 'closed',
        ])->assertRedirect();

        $this->assertEquals('closed', $ticket->fresh()->status);
    }
}
