<?php
namespace KFoodDelights\Payment;

class PaymentConfig {
    // GCash Configuration
    const GCASH_API_BASE_URL = 'https://api.gcash.com';
    const GCASH_API_KEY = 'your_gcash_api_key';
    
    // PayMaya Configuration
    const PAYMAYA_API_BASE_URL = 'https://api.paymaya.com';
    const PAYMAYA_API_KEY = 'your_paymaya_api_key';
    
    // Credit Card Gateway Configuration
    const CC_GATEWAY_BASE_URL = 'https://api.ccgateway.com';
    const CC_GATEWAY_KEY = 'your_cc_gateway_key';
    
    // Payment Gateway Timeouts (in seconds)
    const API_TIMEOUT = 30;
    const API_CONNECT_TIMEOUT = 10;
    
    // Maximum retry attempts for failed API calls
    const MAX_RETRY_ATTEMPTS = 3;
    
    // Currency configuration
    const DEFAULT_CURRENCY = 'PHP';
    const SUPPORTED_CURRENCIES = ['PHP'];
    
    // Webhook Configuration
    const GCASH_WEBHOOK_SECRET = 'your_gcash_webhook_secret';
    const WEBHOOK_TIMEOUT = 10;
    
    // Rate Limiting
    const MAX_PAYMENT_ATTEMPTS_PER_MINUTE = 5;
    const MAX_PAYMENT_ATTEMPTS_PER_DAY = 50;
    
    // Security
    const MAX_PAYMENT_AMOUNT = 50000; // 50,000 PHP
    const MIN_PAYMENT_AMOUNT = 1; // 1 PHP
}