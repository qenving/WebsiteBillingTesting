<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Idempotency
{
    public static function claim(string $gateway, string $rawBody): bool
    {
        $key = hash('sha256', $gateway.'|'.$rawBody);
        try {
            DB::table('idempotency_keys')->insert(['key' => $key, 'created_at' => now()]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

