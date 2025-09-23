# KfoodDelights System Setup Guide

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Module Configuration](#module-configuration)
- [AJ### 2. Testing

### 1. Module Access Tests

Test login redirection for each role:

1. Superadmin → Admin Dashboard
2. Admin → Admin Dashboard
3. Crew → Crew Interface
4. Customer → Customer Portal

### 2. AJAX Communication Test

1. Test order submission and status updates
2. Verify polling intervals and data refresh
3. Check error handling and retries
4. Monitor rate limiting effectiveness

### 3. Security Test#ajax-configuration)

- [Final Configuration](#final-configuration)
- [Testing](#testing)
- [Launching the System](#launching-the-system)
- [Additional Resources](#additional-resources)

## Prerequisites

### Required Software

- XAMPP 8.0+ (includes Apache, MySQL, PHP)
- Node.js 14.0+
- Composer (PHP package manager)
- Git (optional, for version control)

### System Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache 2.4 or higher
- Minimum 4GB RAM
- 1GB free disk space

## Installation

### 1. XAMPP Setup

1. Download and install XAMPP from [apache friends](https://www.apachefriends.org/)
2. Start Apache and MySQL services
3. Verify installation by accessing:
   - `http://localhost` (Apache)
   - `http://localhost/phpmyadmin` (MySQL)

### 2. Project Files

1. Clone or download the project to XAMPP's htdocs:

   ```powershell
   cd C:\xampp\htdocs
   git clone https://github.com/yourusername/K-FOOD-DELIGHTS--.git capstone
   ```

   or extract downloaded zip to `C:\xampp\htdocs\capstone`

2. Verify folder structure:
   ```
   capstone/
   ├── k_food_customer/
   ├── k_food_admin/
   ├── k_food_crew/
   ├── docs/
   └── resources/
   ```

## Database Setup

### 1. Create Database

1. Access phpMyAdmin: `http://localhost/phpmyadmin`
2. Create new database:
   - Name: `kfooddelights`
   - Collation: `utf8mb4_unicode_ci`

### 2. Import Schema

1. Navigate to SQL tab in phpMyAdmin
2. Import initial schema:
   ```sql
   mysql -u root -p kfooddelights < sql/initial_setup.sql
   ```

### 3. Configure Database Connection

1. Update database credentials in config files:

   - `k_food_customer/config.php`
   - `kfood_admin/config.php`
   - `kfood_crew/config.php`

   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'kfooddelights');
   ```

### 4. Run Setup Scripts

Execute in order:

1. `http://localhost/capstone/k_food_customer/setup_database.php`
2. `http://localhost/capstone/k_food_admin/setup_admin_tables.php`
3. `http://localhost/capstone/k_food_customer/setup_roles.php`

## Module Configuration

### 1. Customer Module

1. Install dependencies:

   ```bash
   cd k_food_customer
   composer install
   ```

2. Configure paths in `config.php`:
   ```php
   define('BASE_URL', 'http://localhost/capstone/k_food_customer');
   define('UPLOADS_DIR', __DIR__ . '/uploads');
   ```

### 2. Admin Module

1. Install admin dependencies:

   ```bash
   cd ../kfood_admin
   composer install
   ```

2. Set admin configuration:
   ```php
   define('ADMIN_BASE_URL', 'http://localhost/capstone/kfood_admin');
   ```

### 3. Crew Module

1. Install crew dependencies:
   ```bash
   cd ../kfood_crew
   composer install
   ```

## AJAX Configuration

### 1. Configure Rate Limiting

1. Update rate limiting settings in `config.php`:
   ```php
   define('AJAX_RATE_LIMIT', 60);  // Requests per minute
   define('AJAX_RATE_WINDOW', 60); // Time window in seconds
   ```

### 2. Configure Polling Intervals

1. Set polling intervals in `js/ajax-handler.js`:
   ```javascript
   const POLLING_INTERVALS = {
     ORDER_STATUS: 5000, // 5 seconds
     KITCHEN_STATUS: 3000, // 3 seconds
     SYSTEM_METRICS: 30000, // 30 seconds
   };
   ```

### 3. API Endpoint Configuration

Ensure all AJAX endpoints are properly configured:

1. Order Status: `/api/order_status.php`
2. Kitchen Status: `/api/kitchen_status.php`
3. System Metrics: `/api/system_metrics.php`

### 4. Error Handling

Configure error handling in `includes/ajax-handler.php`:

```php
// Maximum retries for failed requests
define('MAX_RETRIES', 3);

// Retry delay (milliseconds)
define('RETRY_DELAY', 1000);

// Request timeout (milliseconds)
define('REQUEST_TIMEOUT', 10000);
```

## Final Configuration

### 1. Role Setup

Verify roles in database:
| role_id | Role | Access Module |
|---------|------------|-------------------|
| 1 | Superadmin | Admin Dashboard |
| 2 | Admin | Admin Dashboard |
| 3 | Crew | Crew Interface |
| 4 | Customer | Customer Portal |

### 2. File Permissions

Set appropriate permissions:

- Windows: Ensure IIS_IUSRS has read/write access
- Linux: `chmod -R 755` for directories, `644` for files

### 3. Environment Variables

Create `.env` file in project root:

```env
APP_ENV=production
DEBUG_MODE=false
AJAX_RATE_LIMIT=60
AJAX_RATE_WINDOW=60
```

## Testing

### 1. Module Access Tests

Test login redirection for each role:

1. Superadmin → Admin Dashboard
2. Admin → Admin Dashboard
3. Crew → Crew Interface
4. Customer → Customer Portal

### 2. Order System Test

1. Place test order
2. Verify AJAX polling updates in crew dashboard
3. Check order status updates

### 3. Security Test

- Test invalid login attempts
- Verify session timeout
- Check role-based access restrictions

## Launching the System

### 1. Start Services

1. Start XAMPP (Apache + MySQL)
2. Verify all services running:
   - Apache: `http://localhost`
   - MySQL: Check phpMyAdmin
   - Test AJAX endpoints

### 2. Access Points

- Customer Portal: `http://localhost/capstone/k_food_customer`
- Admin Dashboard: `http://localhost/capstone/kfood_admin`
- Crew Interface: `http://localhost/capstone/kfood_crew`

### 3. Default Credentials

```
Superadmin:
- Username: superadmin
- Password: admin123!

Admin:
- Username: admin
- Password: admin123!

Crew:
- Username: crew
- Password: crew123!

Test Customer:
- Username: customer
- Password: customer123!
```

## Additional Resources

### Documentation

- [Authentication Flow](docs/authentication.md)
- [Order Processing](docs/order-flow.md)
- [AJAX Architecture](docs/ajax-architecture.md)
- [User Roles](docs/user-roles.md)
- [Error Logging](docs/error-log.md)

### Support

For technical support or issues:

1. Check error logs in `logs/` directory
2. Consult relevant documentation
3. Contact system administrator

### Maintenance

Regular maintenance tasks:

1. Check error logs weekly
2. Update database backups
3. Monitor AJAX polling status
4. Review security settings

---

For detailed information about specific components, please refer to the respective documentation files in the `docs/` directory.
