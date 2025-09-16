<?php

namespace App\Http\Controllers;

use App\Domain\Payments\GatewayManager;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class InvoicesController extends Controller
{
    public function index()
    {
        $user = Auth::user(); if (! $user) abort(401);
        $client = $user->client;
        $invoices = $client ? $client->invoices()->latest()->paginate(15) : collect();
        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice, GatewayManager $gateways)
    {
        $user = Auth::user(); if (! $user) abort(401);
        if (! $invoice->client || $invoice->client->user_id !== $user->id) abort(403);
        $available = [];
        foreach ($gateways->all() as $k => $g) { $available[] = ['key'=>$k,'name'=>$g->displayName()]; }
        return view('invoices.show', [ 'invoice'=>$invoice->load(['items','client.user']), 'gateways'=>$available ]);
    }
}

