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
        'enabled' => (bool) env('RAZORPAY_ENABLED', false),
        'base_url' => 'https://api.razorpay.com/v1',
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'plan_id' => env('RAZORPAY_PLAN_ID'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'total_count' => (int) env('RAZORPAY_SUBSCRIPTION_CYCLES', 120),
    ],

    'paypal' => [
        'enabled' => (bool) env('PAYPAL_ENABLED', false),
        'base_url' => 'https://api-m.sandbox.paypal.com',
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'plan_id' => env('PAYPAL_PLAN_ID'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'stripe' => [
        'enabled' => (bool) env('STRIPE_ENABLED', true),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'price_id' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
