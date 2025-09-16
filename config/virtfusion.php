<?php

return [
    'base_url' => env('VIRTFUSION_BASE_URL'),
    'api_key'  => env('VIRTFUSION_API_KEY'),
    'endpoints' => [
        'create' => '/api/v1/servers',
        'get' => '/api/v1/servers/{id}',
        'suspend' => '/api/v1/servers/{id}/suspend',
        'unsuspend' => '/api/v1/servers/{id}/unsuspend',
        'terminate' => '/api/v1/servers/{id}',
        'reboot' => '/api/v1/servers/{id}/reboot',
        'power_on' => '/api/v1/servers/{id}/power_on',
        'power_off' => '/api/v1/servers/{id}/power_off',
        'reinstall' => '/api/v1/servers/{id}/reinstall',
        'resize' => '/api/v1/servers/{id}/resize',
        'snapshot' => '/api/v1/servers/{id}/snapshots',
        'reset_password' => '/api/v1/servers/{id}/reset_password',
        'console' => '/api/v1/servers/{id}/console',
    ],
];

