<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Settings;

class AdminSettingsController extends Controller
{
    protected function requireAdmin(): void
    {
        $u = Auth::user(); if (! $u || ! $u->is_admin) abort(403);
    }

    public function show()
    {
        $this->requireAdmin();
        $data = [
            'branding_name' => Settings::get('branding.name', config('app.name')),
            'manual_instructions' => Settings::get('payments.manual.instructions', ""),
            'company_name' => Settings::get('company.name', ''),
            'company_address' => Settings::get('company.address', ''),
            'company_tax_id' => Settings::get('company.tax_id', ''),
            'company_email' => Settings::get('company.email', ''),
            'company_phone' => Settings::get('company.phone', ''),
            'company_logo_url' => Settings::get('company.logo_url', ''),
        ];
        return view('admin.settings', compact('data'));
    }

    public function update(Request $request)
    {
        $this->requireAdmin();
        $request->validate([
            'branding_name' => 'required|string|max:100',
            'manual_instructions' => 'nullable|string|max:5000',
            'company_name' => 'nullable|string|max:150',
            'company_address' => 'nullable|string|max:1000',
            'company_tax_id' => 'nullable|string|max:100',
            'company_email' => 'nullable|email',
            'company_phone' => 'nullable|string|max:50',
            'company_logo_url' => 'nullable|url',
            'finance_tax_rate' => 'nullable|numeric|min:0|max:100',
            'invoice_number_prefix' => 'nullable|string|max:20',
            'invoice_number_date_format' => 'nullable|string|max:20',
            'invoice_sequence_scope' => 'nullable|string|in:daily,monthly,yearly,global',
            'invoice_footer' => 'nullable|string|max:2000',
        ]);
        Settings::set('branding.name', $request->branding_name);
        if ($request->filled('manual_instructions')) {
            Settings::set('payments.manual.instructions', $request->manual_instructions);
        }
        Settings::set('company.name', (string) $request->company_name);
        Settings::set('company.address', (string) $request->company_address);
        Settings::set('company.tax_id', (string) $request->company_tax_id);
        Settings::set('company.email', (string) $request->company_email);
        Settings::set('company.phone', (string) $request->company_phone);
        Settings::set('company.logo_url', (string) $request->company_logo_url);
        if ($request->filled('finance_tax_rate')) Settings::set('finance.tax_rate', (float) $request->finance_tax_rate, 'float');
        if ($request->filled('invoice_number_prefix')) Settings::set('invoice.number_prefix', (string) $request->invoice_number_prefix);
        if ($request->filled('invoice_number_date_format')) Settings::set('invoice.number_date_format', (string) $request->invoice_number_date_format);
        if ($request->filled('invoice_sequence_scope')) Settings::set('invoice.sequence_scope', (string) $request->invoice_sequence_scope);
        Settings::set('invoice.footer', (string) $request->invoice_footer);
        return redirect()->back()->with('status', 'Settings saved');
    }
}
