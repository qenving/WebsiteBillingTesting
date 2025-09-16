<?php

namespace App\Http\Controllers;

use App\Domain\Payments\GatewayManager;
use App\Support\Idempotency;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function handle(string $gateway, Request $request, GatewayManager $gateways)
    {
        $raw = $request->getContent();
        if (! Idempotency::claim($gateway, $raw)) {
            return response()->json(['ok'=>true,'duplicate'=>true]);
        }
        $gateways->get($gateway)->handleWebhook($request);
        return response()->json(['ok'=>true]);
    }
}

