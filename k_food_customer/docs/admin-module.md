# KfoodDelights Admin Module

## Dashboard Session Handling

- Only authenticated admins can access `dashboard.php`.
- If not logged in, user is redirected to `admin_login.php`.
- Session is validated on every dashboard load.

## Logout Flow

- Clicking the Logout button in the dashboard header calls `logout.php`.
- `logout.php` destroys the session, clears cookies, and redirects to `admin_login.php`.
- After logout, browser back navigation to dashboard is prevented (no-cache headers).

## UI Consistency

- Logout button uses KfoodDelights theme: warm palette, rounded corners, clean typography.
- All session and redirect logic is commented inline for maintainability.

---

See also: `auth-flow.md` for authentication and logout details.
