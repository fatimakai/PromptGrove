<?php

return [
    'plan' => [
        'name' => 'PromptGrove Pro',
        'amount' => 900,
        'currency' => 'USD',
        'interval' => 'month',
    ],

    // This portfolio integration is intentionally sandbox-only.
    'razorpay' => [
        'base_url' => 'https://api.razorpay.com/v1',
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'plan_id' => env('RAZORPAY_PLAN_ID'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'total_count' => (int) env('RAZORPAY_SUBSCRIPTION_CYCLES', 120),
    ],

    'paypal' => [
        'base_url' => 'https://api-m.sandbox.paypal.com',
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'plan_id' => env('PAYPAL_PLAN_ID'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],
];
