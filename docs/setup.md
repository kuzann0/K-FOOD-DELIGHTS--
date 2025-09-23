# Setup Guide

## System Architecture Update (September 2025)

The system has been migrated from WebSocket to AJAX-based real-time updates for improved reliability and simpler implementation.

## Real-Time Updates Configuration

1. AJAX Endpoints:

   ```
   api/
   ├── order_status.php
   ├── kitchen_status.php
   ├── system_metrics.php
   └── process_order.php
   ```

2. Polling Configuration:

   - Customer Module: 5-second intervals for order status
   - Crew Module: 3-second intervals for active orders
   - Admin Module: 30-second intervals for system metrics

3. Rate Limiting:

   ```php
   // Configure in config.php
   define('AJAX_RATE_LIMIT', 60); // Requests per minute
   define('AJAX_RATE_WINDOW', 60); // Window in seconds
   ```

4. Error Handling:
   - Enable error logging in PHP
   - Configure notification system
   - Set up monitoring for rate limits

## AJAX-based Menu Creation

The menu creation feature in the admin dashboard uses AJAX to provide a seamless user experience. Here's how to set it up:

1. Database Requirements:

   - Ensure the `menu_items` and `menu_categories` tables exist
   - Required permissions in `admin_permissions` table:
     ```sql
     INSERT INTO admin_permissions (permission_name, description)
     VALUES ('manage_menu', 'Create and manage menu items');
     ```

2. Files Structure:

   ```
   kfood_admin/
   ├── partials/
   │   └── menu_creation.php
   ├── ajax/
   │   ├── create-menu-item.php
   │   └── get-menu-items.php
   └── dashboard.php
   ```

3. Configuration:

   - Ensure CSRF token generation is enabled
   - Verify database connection settings in `config.php`
   - Check file permissions for upload directories

4. Testing:
   - Log in as admin with 'manage_menu' permission
   - Navigate to Dashboard
   - Click "Menu Creation" in the sidebar
   - Test form submission and validation
