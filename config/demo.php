<?php

return [
    // Enable only on the public portfolio deployment; local password auth stays available.
    'oauth_only' => (bool) env('DEMO_OAUTH_ONLY', false),
    'staff' => [
        'admin' => [
            'email' => env('DEMO_ADMIN_EMAIL'),
            'password' => env('DEMO_ADMIN_PASSWORD'),
        ],
        'moderator' => [
            'email' => env('DEMO_MODERATOR_EMAIL'),
            'password' => env('DEMO_MODERATOR_PASSWORD'),
        ],
    ],
];
