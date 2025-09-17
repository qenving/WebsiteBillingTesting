<?php

namespace App\Http\Controllers;

use App\Events\InvoicePaid;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPaymentsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'admin']);
    }

    public function index()
    {
        $payments = Payment::with(['invoice.client.user'])
            ->where('gateway', 'manual')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(15);
        return view('admin.payments.index', compact('payments'));
    }

    public function confirm(Payment $payment, Request $request)
    {
        if ($payment->gateway !== 'manual') {
            abort(400, 'Only manual payments can be confirmed');
        }
        if ($payment->status === 'completed') {
            return redirect()->back()->with('status', 'Payment already confirmed');
        }

        $payment->status = 'completed';
        $payment->paid_at = now();
        $payment->meta = array_merge($payment->meta ?? [], [
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now()->toDateTimeString(),
        ]);
        $payment->save();

        $invoice = $payment->invoice;
        if ($invoice && $invoice->status !== 'paid') {
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $invoice->save();
            event(new InvoicePaid($invoice));
        }

        PaymentLog::create([
            'payment_id' => $payment->id,
            'gateway' => $payment->gateway,
            'event' => 'manual_confirmed',
            'payload' => ['admin_id' => Auth::id()],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('status', 'Payment confirmed and invoice marked as paid');
    }
}
