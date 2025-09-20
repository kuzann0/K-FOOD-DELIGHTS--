# Authentication System Documentation

## Overview

The KfoodDelights authentication system provides role-based access control (RBAC) with a unified login interface through the customer portal.

## Login Flow

1. User enters credentials on customer login page (`k_food_customer/login.php`)
2. System validates credentials against database
3. On successful authentication:
   - User's role is retrieved from database
   - Session is securely initialized
   - User is redirected to appropriate module based on role_id

## Role-Based Redirection

Based on the user's `role_id`, they are automatically redirected to their appropriate module:

| role_id | Role       | Redirect Path              |
| ------- | ---------- | -------------------------- |
| 1       | Superadmin | /kfood_admin/dashboard.php |
| 2       | Admin      | /kfood_admin/dashboard.php |
| 3       | Crew       | /kfood_crew/index.php      |
| 4       | Customer   | /k_food_customer/index.php |

## Security Features

- Password hashing using PHP's `password_hash()` function
- Session security with `session_regenerate_id()`
- SQL injection prevention using prepared statements
- Account status validation
- Login attempt tracking
- Automatic session timeout
- XSS prevention through input sanitization

## Error Handling

- Invalid credentials display generic error message
- Account status checks prevent inactive accounts from logging in
- System errors are logged while showing user-friendly messages
- Invalid role IDs are logged and reported to support

## Session Management

- Session variables set on successful login:
  - user_id: Unique identifier for the user
  - role_id: User's role level
  - last_activity: Timestamp for session timeout
  - Role-specific flags (is_superadmin, is_admin, is_crew, is_customer)

## Security Best Practices

1. Use HTTPS for all authentication requests
2. Implement rate limiting for failed login attempts
3. Regular security audits of authentication system
4. Monitoring of failed login attempts
5. Regular rotation of session secrets
