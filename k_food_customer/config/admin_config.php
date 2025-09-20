<?php
/**
 * Admin configuration settings
 * Contains constants and settings for admin functionality
 */

// Email settings
define('ADMIN_EMAIL', 'admin@kfood-delight.com');

// WebSocket configuration
define('WEBSOCKET_ENABLED', true);
define('WEBSOCKET_HOST', 'localhost');
define('WEBSOCKET_PORT', 8080);

// Notification settings
define('NOTIFICATION_SOUND_ENABLED', true);
define('NOTIFICATION_REFRESH_INTERVAL', 60); // seconds
define('MAX_NOTIFICATIONS_DISPLAY', 50);

// Critical error types
define('CRITICAL_ERROR_TYPES', [
    'DATABASE_ERROR',
    'PAYMENT_PROCESSING_ERROR',
    'ORDER_PROCESSING_ERROR',
    'SECURITY_VIOLATION',
    'SYSTEM_ERROR'
]);

// Notification retention period (in days)
define('NOTIFICATION_RETENTION_DAYS', 30);

// Rate limiting
define('MAX_NOTIFICATIONS_PER_MINUTE', 10);
define('NOTIFICATION_THROTTLE_THRESHOLD', 100);