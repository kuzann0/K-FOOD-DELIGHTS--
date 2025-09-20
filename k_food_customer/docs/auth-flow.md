# KfoodDelights Authentication Flow

## Login Process

1. **User submits login form** with username and password.
2. **Server validates input** (empty fields, sanitization).
3. **SQL Query**: `SELECT user_id, password, role_id, account_status FROM users WHERE username = ?`
   - If `user_id` does not exist, check schema and update as needed.
4. **Password is verified** using `password_verify()`.
5. **Account status** is checked (`account_status = 'active'`).
6. **On success**:
   - Session variables are set (`user_id`, `role_id`, etc.)
   - Last login timestamp and login attempts are updated.
   - User is redirected based on role.
7. **On failure**:
   - Error message is shown (invalid credentials, inactive account, etc.)

## Logout Process (Admin Module)

1. **Admin clicks Logout** in the dashboard header.
2. **`logout.php` destroys the session** and clears all cookies.
3. **User is redirected to `admin_login.php`**.
4. **No-cache headers** prevent browser back navigation to dashboard after logout.
5. **Session is validated** on every dashboard load; unauthorized access is redirected to login.

## Error Handling

- All SQL queries use prepared statements.
- Errors are caught and displayed in a user-friendly manner.
- Inline comments in `login.php` and `dashboard.php` explain the logic and error handling.

---

See also: `/docs/database-schema.md` for table structure.
