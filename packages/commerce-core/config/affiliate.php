<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Affiliate Defaults
    |--------------------------------------------------------------------------
    |
    | These values keep the first affiliate release operationally simple while
    | allowing a later settings screen to persist overrides without changing the
    | shared affiliate domain.
    |
    */

    'approval_required' => true,

    'referral_parameter' => 'ref',

    'cookie_name' => 'platform_affiliate_referral',

    'cookie_window_days' => 30,

    'default_commission' => [
        'type' => 'percentage',
        'value' => 10.0,
    ],

    'minimum_payout_amount' => 50.0,

    'payout_methods' => [
        'bank_transfer' => 'Bank Transfer',
        'mobile_banking' => 'Mobile Banking',
        'manual' => 'Manual',
    ],

    'terms_required' => true,

    'terms_text' => 'By applying, you agree to promote this store honestly, avoid self-referrals, and request payouts only for eligible approved commissions.',

    'self_referral_prevention' => true,
];
