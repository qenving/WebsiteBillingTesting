<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'admin']);
    }

    public function index(Request $request)
    {
        $status = $request->input('status');

        $tickets = Ticket::with(['client.user'])
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->appends(['status' => $status]);

        return view('admin.tickets.index', compact('tickets', 'status'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['client.user', 'replies.user']);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'message' => 'required|string|min:3',
            'is_internal' => 'nullable|boolean',
        ]);

        $isInternal = (bool) ($data['is_internal'] ?? false);

        $ticket->addReply(Auth::user(), $data['message'], $isInternal);

        return redirect()->route('admin.tickets.show', $ticket)->with('status', $isInternal ? 'Internal note added.' : 'Reply sent to client.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|string|in:open,closed',
        ]);

        $ticket->status = $data['status'];
        if ($data['status'] === 'open') {
            $ticket->last_reply_at = $ticket->last_reply_at ?? now();
        }
        $ticket->save();

        return redirect()->route('admin.tickets.show', $ticket)->with('status', 'Ticket status updated.');
    }
}
