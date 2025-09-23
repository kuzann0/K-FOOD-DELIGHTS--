# Authentication Flow Documentation

## Login Process

### 1. Entry Points

- Clicking login icon (`fas fa-sign-in-alt`) in header
- Direct navigation to login.php
- Redirects from protected pages

### 2. Form Validation

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    // Required field validation
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    }
}
```

### 3. Authentication Process

1. Validate user credentials against database
2. Check account status (active/inactive/suspended)
3. Verify password using secure hashing
4. Update login timestamp and reset attempt counter
5. Set session variables for user state

### 4. Session Management

```php
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['last_activity'] = time();
session_regenerate_id(true);
```

### 5. Role-Based Access Control

| Role ID | Role Type  | Login Path                | Access            |
| ------- | ---------- | ------------------------- | ----------------- |
| 1       | Superadmin | kfood_admin/login.php     | Admin access only |
| 2       | Admin      | kfood_admin/login.php     | Admin access only |
| 3       | Crew       | kfood_admin/login.php     | Admin access only |
| 4       | Customer   | k_food_customer/login.php | Customer access   |

The system enforces strict role-based access:

- Customer login page (k_food_customer/login.php) only accepts role_id = 4
- Admin/Crew users attempting to use customer login are redirected to admin login
- Invalid roles or credentials receive secure error messages
- Each role has a dedicated login flow and interface

### 6. Security Measures

- Role validation before credential check
- CSRF protection with unique tokens
- Password hashing using bcrypt
- Session security with encryption
- Login attempt tracking and lockout
- Secure cookie settings
- XSS prevention
- SQL injection protection

### 7. Error Handling

- Invalid credentials
- Inactive accounts
- Rate limiting
- Database errors
- Session errors

## Dependencies

1. config.php - Database connection
2. auth.php - Authentication functions
3. Font Awesome 5 - UI icons
4. session handling

## Security Considerations

1. Use HTTPS for all authentication
2. Implement rate limiting
3. Secure session configuration
4. Proper password hashing
5. Input sanitization
