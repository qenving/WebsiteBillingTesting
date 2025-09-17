<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\CreditManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceCreditController extends Controller
{
    public function apply(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }
        if (! $invoice->client || $invoice->client->user_id !== $user->id) {
            abort(403);
        }
        if ($invoice->status === 'paid') {
            return redirect()->back()->with('status', 'Invoice already settled.');
        }

        $data = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        $amount = array_key_exists('amount', $data) ? (float) $data['amount'] : null;
        $applied = CreditManager::applyToInvoice($invoice, $amount, $user->id, 'Client applied credit');

        if ($applied <= 0) {
            return redirect()->back()->withErrors(['amount' => 'Unable to apply credit to this invoice.']);
        }

        return redirect()->back()->with('status', 'Credit of '.number_format($applied, 2).' applied successfully.');
    }
}
