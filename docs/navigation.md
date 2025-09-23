# Navigation Documentation

## Global Navigation

### Header Components

1. Logo (links to home)
2. Main Navigation
   - Home
   - Menu
   - Orders (authenticated)
   - Profile (authenticated)
3. Action Icons
   - Shopping Cart (authenticated)
   - Login/Logout

### Login Icon Integration

The login icon appears in the header's action area:

```html
<div class="nav-actions">
  <?php if (isset($_SESSION['user_id'])): ?>
  <!-- Authenticated user actions -->
  <a href="cart.php" class="cart-icon">
    <i class="fas fa-shopping-cart"></i>
  </a>
  <a href="logout.php" class="auth-icon">
    <i class="fas fa-sign-out-alt"></i>
  </a>
  <?php else: ?>
  <!-- Guest user actions -->
  <a href="login.php" class="auth-icon">
    <i class="fas fa-sign-in-alt"></i>
  </a>
  <?php endif; ?>
</div>
```

### Responsive Behavior

- Desktop: Full navigation with icons
- Tablet: Condensed menu with icons
- Mobile: Hamburger menu with icons

### State Management

1. Guest State

   - Show login icon
   - Hide cart
   - Limited navigation

2. Authenticated State
   - Show logout icon
   - Show cart icon
   - Full navigation

### Path Management

All navigation paths are relative to the module:

- Customer module: /k_food_customer/
- Admin module: /kfood_admin/
- Crew module: /kfood_crew/

### Integration Points

1. Header.php - Main navigation structure
2. Auth.php - Session management
3. Role-based access control
4. Dynamic menu items
