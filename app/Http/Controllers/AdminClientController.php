<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Support\CreditManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'admin']);
    }

    public function index(Request $request)
    {
        $search = $request->input('q');

        $clients = Client::with('user')->withCount('services')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('company', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->appends(['q' => $search]);

        return view('admin.clients.index', compact('clients', 'search'));
    }

    public function show(Client $client)
    {
        $client->load([
            'user',
            'services.product',
            'invoices' => function ($query) {
                $query->orderByDesc('created_at')->limit(10);
            },
            'creditTransactions' => function ($query) {
                $query->orderByDesc('created_at')->limit(10);
            },
        ]);

        return view('admin.clients.show', [
            'client' => $client,
            'services' => $client->services,
            'invoices' => $client->invoices,
            'creditTransactions' => $client->creditTransactions,
        ]);
    }

    public function adjustCredit(Request $request, Client $client)
    {
        $data = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if ($data['type'] === 'credit') {
            CreditManager::addCredit($client, (float) $data['amount'], $data['description'] ?: 'Manual credit', Auth::id());
        } else {
            CreditManager::deductCredit($client, (float) $data['amount'], $data['description'] ?: 'Manual debit', Auth::id());
        }

        return redirect()->route('admin.clients.show', $client)->with('status', 'Client credit updated.');
    }
}
