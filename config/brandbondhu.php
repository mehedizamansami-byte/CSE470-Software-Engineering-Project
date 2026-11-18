<?php

/*
|--------------------------------------------------------------------------
| BrandBondhu settings
|--------------------------------------------------------------------------
| Business rules shared by controllers and services.
*/

return [

    // Show OTP codes on screen. Email is only written to storage/logs/laravel.log
    // on a local machine, so this makes the demo usable. Set to false in production.
    'show_demo_codes' => env('SHOW_DEMO_CODES', true),

    'otp' => [
        'expires_minutes' => 10,
        'max_attempts' => 5,
    ],

    // Feature 2: permissions. A seller can grant these to collaborators.
    'workspace_permissions' => [
        'shop' => 'Shop profile',
        'brand_kit' => 'Brand identity kits',
        'style_guide' => 'Brand style guide',
        'content' => 'Content manager',
    ],

    // Feature 2: what each role can do. Collaborators get only what their seller grants.
    'role_permissions' => [
        'seller' => ['shop', 'brand_kit', 'style_guide', 'content', 'team', 'billing'],
        'collaborator' => [],
        'designer' => ['portfolio'],
        'admin' => ['admin'],
    ],

    // Every permission key, with a short label (used by the admin matrix).
    'all_permissions' => [
        'shop' => 'Shop profile',
        'brand_kit' => 'Brand identity kits',
        'style_guide' => 'Brand style guide',
        'content' => 'Content manager',
        'team' => 'Team and audit log',
        'billing' => 'Plans, credits and payments',
        'portfolio' => 'Designer portfolio',
        'admin' => 'Admin panel',
    ],

    // Feature 5: AI credit cost of each action.
    'credit_costs' => [
        'brand_kit' => 5,
        'brand_asset' => 1,
        'style_guide' => 3,
        'caption' => 1,
    ],

    // Feature 5: warn the seller when credits drop below this number.
    'low_credit_threshold' => 10,

    // Feature 5: one-time credit packs (price in BDT).
    'credit_packs' => [
        'small' => ['credits' => 50, 'price' => 199],
        'medium' => ['credits' => 150, 'price' => 499],
        'large' => ['credits' => 500, 'price' => 1499],
    ],

    // Feature 6: simulated payment methods.
    'payment_methods' => [
        'bkash' => ['label' => 'bKash', 'pin_length' => 5],
        'nagad' => ['label' => 'Nagad', 'pin_length' => 4],
        'card' => ['label' => 'Credit / debit card', 'pin_length' => null],
    ],
];
