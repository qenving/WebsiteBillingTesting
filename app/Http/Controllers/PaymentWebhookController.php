<?php

namespace App\Http\Controllers;

use App\Domain\Payments\GatewayManager;
use App\Support\Idempotency;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentWebhookController extends Controller
{
    public function handle(string $gateway, Request $request, GatewayManager $gateways)
    {
        $raw = $request->getContent();
        if (! Idempotency::claim($gateway, $raw)) {
            return response()->json(['ok'=>true,'duplicate'=>true]);
        }
        try {
            $gateways->get($gateway)->handleWebhook($request);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => 'Gateway not found'], 404);
        }
        return response()->json(['ok'=>true]);
    }
}

