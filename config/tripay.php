<?php

return [
    'api_key' => env('TRIPAY_API_KEY'),
    'private_key' => env('TRIPAY_PRIVATE_KEY'),
    'merchant_code' => env('TRIPAY_MERCHANT_CODE'),
    'default_method' => env('TRIPAY_DEFAULT_METHOD', 'QRIS'),
    'sandbox' => (bool) env('TRIPAY_SANDBOX', true),
];

