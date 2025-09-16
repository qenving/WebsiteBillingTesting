<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice)
    {
        $user = Auth::user(); if (! $user) abort(401);
        if (! $invoice->client || $invoice->client->user_id !== $user->id) abort(403);

        $invoice->load(['items','client.user']);
        $settings = [
            'brand' => \App\Support\Settings::get('branding.name', config('app.name')),
            'company' => [
                'name' => \App\Support\Settings::get('company.name', ''),
                'address' => \App\Support\Settings::get('company.address', ''),
                'tax_id' => \App\Support\Settings::get('company.tax_id', ''),
                'email' => \App\Support\Settings::get('company.email', ''),
                'phone' => \App\Support\Settings::get('company.phone', ''),
                'logo' => \App\Support\Settings::get('company.logo_url', ''),
            ],
            'manual_instructions' => \App\Support\Settings::get('payments.manual.instructions', ''),
        ];
        return view('invoices.pdf', compact('invoice','settings'));
    }
}
