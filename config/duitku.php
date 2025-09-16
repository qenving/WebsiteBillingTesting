<?php

return [
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key' => env('DUITKU_API_KEY'),
    'sandbox' => (bool) env('DUITKU_SANDBOX', true),
];

