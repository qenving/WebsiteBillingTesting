<?php

namespace App\Http\Controllers;

use App\Domain\Payments\GatewayManager;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(string $gateway, Invoice $invoice, GatewayManager $gateways, Request $request)
    {
        $user = Auth::user(); if(! $user) abort(401);
        if(! $invoice->client || $invoice->client->user_id !== $user->id) abort(403);
        if($invoice->status==='paid') return response()->json(['message'=>'Invoice already paid'],400);
        try{ $payment=$gateways->get($gateway)->createPayment($invoice); }
        catch(\Throwable $e){ return response()->json(['message'=>'Gateway error: '.$e->getMessage()],400); }
        return [
            'payment_id'=>$payment->id,
            'order_id'=>$payment->transaction_id,
            'redirect_url'=>$payment->meta['redirect_url']??null,
            'instructions'=>$payment->meta['instructions']??null,
            'gateway'=>$payment->gateway,
            'status'=>$payment->status
        ];
    }
}
