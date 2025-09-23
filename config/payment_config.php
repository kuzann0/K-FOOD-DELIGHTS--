<?php
/**
 * Payment Gateway Configuration
 */

// GCash Configuration
define('GCASH_API_KEY', 'your_gcash_api_key_here');
define('GCASH_API_SECRET', 'your_gcash_api_secret_here');
define('GCASH_ENVIRONMENT', 'sandbox'); // or 'production'

// PayMaya Configuration
define('PAYMAYA_API_KEY', 'your_paymaya_api_key_here');
define('PAYMAYA_API_SECRET', 'your_paymaya_api_secret_here');
define('PAYMAYA_ENVIRONMENT', 'sandbox'); // or 'production'

// Credit Card Gateway Configuration
define('CC_GATEWAY_KEY', 'your_cc_gateway_key_here');
define('CC_GATEWAY_SECRET', 'your_cc_gateway_secret_here');
define('CC_GATEWAY_ENVIRONMENT', 'sandbox'); // or 'production'

// Payment Gateway URLs
$PAYMENT_GATEWAY_URLS = [
    'sandbox' => [
        'gcash' => [
            'payment' => 'https://api-sandbox.gcash.com/payment',
            'refund' => 'https://api-sandbox.gcash.com/refund',
            'verify' => 'https://api-sandbox.gcash.com/verify'
        ],
        'paymaya' => [
            'payment' => 'https://api-sandbox.paymaya.com/payment',
            'refund' => 'https://api-sandbox.paymaya.com/refund',
            'verify' => 'https://api-sandbox.paymaya.com/verify'
        ],
        'credit_card' => [
            'payment' => 'https://api-sandbox.ccgateway.com/payment',
            'refund' => 'https://api-sandbox.ccgateway.com/refund',
            'verify' => 'https://api-sandbox.ccgateway.com/verify'
        ]
    ],
    'production' => [
        'gcash' => [
            'payment' => 'https://api.gcash.com/payment',
            'refund' => 'https://api.gcash.com/refund',
            'verify' => 'https://api.gcash.com/verify'
        ],
        'paymaya' => [
            'payment' => 'https://api.paymaya.com/payment',
            'refund' => 'https://api.paymaya.com/refund',
            'verify' => 'https://api.paymaya.com/verify'
        ],
        'credit_card' => [
            'payment' => 'https://api.ccgateway.com/payment',
            'refund' => 'https://api.ccgateway.com/refund',
            'verify' => 'https://api.ccgateway.com/verify'
        ]
    ]
];

// Payment Gateway Error Codes
$PAYMENT_ERROR_CODES = [
    'insufficient_funds' => 'E001',
    'invalid_card' => 'E002',
    'expired_card' => 'E003',
    'invalid_otp' => 'E004',
    'payment_failed' => 'E005',
    'gateway_error' => 'E006'
];