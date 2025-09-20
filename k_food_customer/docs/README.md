# K-Food Delight System Documentation

## System Overview

K-Food Delight is a comprehensive food ordering and delivery system with real-time order tracking, notifications, and role-based access control.

## Core Components

1. **Order Processing System**

   - Cart Management
   - Checkout Process
   - Payment Integration
   - Order Validation

2. **Real-time Notifications**

   - WebSocket Integration
   - Role-based Notifications
   - Notification Persistence
   - Automatic Cleanup

3. **User Management**

   - Role-based Access Control
   - Profile Management
   - Delivery Information
   - Payment Preferences

4. **Security Features**
   - Input Validation
   - Data Sanitization
   - Session Management
   - Error Handling

## Configuration

### Required Environment Variables

```php
// Pusher Configuration
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_ID=your_app_id
PUSHER_APP_CLUSTER=ap1
```

### Database Setup

- Required Tables: users, orders, order_items, notifications, notification_logs
- Automatic table creation scripts included
- Daily cleanup procedures for old notifications

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Configure environment variables
4. Run database setup scripts
5. Start WebSocket server: `php includes/websocket_server.php`

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- WebSocket support
- SSL certificate for production

## Architecture

### Directory Structure

```
k_food_customer/
├── api/            # API endpoints
├── includes/       # Core functionality
├── js/            # Client-side scripts
├── css/           # Stylesheets
└── docs/          # Documentation
```

### Key Files

- `process_order.php`: Main order processing
- `WebSocketHandler.php`: Real-time notifications
- `checkout.js`: Client-side checkout logic
- `notification_config.php`: System configuration

## Security Considerations

1. Input Validation

   - All user inputs are validated
   - Type checking and sanitization
   - Maximum value limits

2. Error Handling

   - Structured error responses
   - Detailed logging
   - User-friendly messages

3. Transaction Management
   - Database transactions
   - Rollback on failure
   - Data consistency

## Monitoring

1. Notification Logging

   - All notifications are logged
   - Delivery status tracking
   - Automatic cleanup

2. Error Tracking
   - Failed orders logging
   - Notification failures
   - System errors

## Maintenance

1. Database Maintenance

   - Daily notification cleanup
   - Log rotation
   - Performance optimization

2. WebSocket Server
   - Automatic restart
   - Connection monitoring
   - Error recovery
