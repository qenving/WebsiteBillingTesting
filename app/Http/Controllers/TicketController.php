<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index()
    {
        $user = Auth::user();
        $client = $user?->client;
        if (! $client) {
            abort(403);
        }

        $tickets = Ticket::forClient($client)->latest('updated_at')->paginate(15);

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $client = $user?->client;
        if (! $client) {
            abort(403);
        }

        $data = $request->validate([
            'subject' => 'required|string|max:150',
            'department' => 'nullable|string|max:100',
            'priority' => 'required|string|in:low,normal,high',
            'message' => 'required|string|min:10',
        ]);

        $ticket = Ticket::create([
            'client_id' => $client->id,
            'subject' => $data['subject'],
            'department' => $data['department'] ?? null,
            'priority' => $data['priority'],
            'status' => 'open',
        ]);

        $ticket->addReply($user, $data['message']);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Support ticket created.');
    }

    public function show(Ticket $ticket)
    {
        $user = Auth::user();
        $client = $user?->client;
        if (! $client || $ticket->client_id !== $client->id) {
            abort(403);
        }

        $ticket->load(['replies.user']);

        return view('tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        $client = $user?->client;
        if (! $client || $ticket->client_id !== $client->id) {
            abort(403);
        }

        $data = $request->validate([
            'message' => 'required|string|min:5',
        ]);

        $ticket->addReply($user, $data['message']);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Reply sent to support.');
    }

    public function close(Ticket $ticket)
    {
        $user = Auth::user();
        $client = $user?->client;
        if (! $client || $ticket->client_id !== $client->id) {
            abort(403);
        }

        $ticket->status = 'closed';
        $ticket->save();

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket closed.');
    }
}
