<?php
// Pusher Configuration
define('PUSHER_APP_KEY', 'your_app_key');
define('PUSHER_APP_SECRET', 'your_app_secret');
define('PUSHER_APP_ID', 'your_app_id');
define('PUSHER_APP_CLUSTER', 'ap1'); // Change this to your cluster

// WebSocket Connection Settings
define('WEBSOCKET_HOST', 'localhost');
define('WEBSOCKET_PORT', 8080);

// Notification Settings
define('MAX_NOTIFICATIONS', 100); // Maximum number of notifications to keep per user
define('NOTIFICATION_EXPIRY_DAYS', 30); // Days before notifications are automatically deleted

// Role-specific notification channels
define('ADMIN_CHANNEL', 'orders-2');
define('CREW_CHANNEL', 'orders-3');
define('CUSTOMER_CHANNEL', 'orders-1');

// Event types
define('EVENT_NEW_ORDER', 'new-order');
define('EVENT_ORDER_STATUS', 'order-status');
define('EVENT_PAYMENT_STATUS', 'payment-status');
?>