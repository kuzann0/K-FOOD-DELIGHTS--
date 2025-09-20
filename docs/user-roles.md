# User Roles Documentation

## Role Hierarchy

KfoodDelights implements a hierarchical role-based access control system with the following roles:

### 1. Superadmin (role_id: 1)

- Highest level of system access
- Full access to all admin features
- Can manage other admin accounts
- Access to system configuration
- Complete access to all modules

### 2. Admin (role_id: 2)

- Management of daily operations
- User management
- Order management
- Inventory control
- Report generation
- Staff management

### 3. Crew (role_id: 3)

- Order processing
- Kitchen operations
- Inventory updates
- Customer service
- Order status management

### 4. Customer (role_id: 4)

- Place orders
- View menu
- Manage profile
- View order history
- Track active orders

## Access Control Matrix

| Feature              | Superadmin | Admin | Crew | Customer |
| -------------------- | ---------- | ----- | ---- | -------- |
| System Configuration | ✓          | -     | -    | -        |
| Admin Management     | ✓          | -     | -    | -        |
| Staff Management     | ✓          | ✓     | -    | -        |
| Inventory Management | ✓          | ✓     | ✓    | -        |
| Order Processing     | ✓          | ✓     | ✓    | -        |
| View Orders          | ✓          | ✓     | ✓    | ✓        |
| Place Orders         | ✓          | ✓     | -    | ✓        |
| Profile Management   | ✓          | ✓     | ✓    | ✓        |

## Module Access

Each role has access to specific modules in the system:

### Admin Module (`/kfood_admin/`)

- Accessible by: Superadmin, Admin
- Features:
  - Dashboard
  - User Management
  - Order Management
  - Inventory Control
  - Reports
  - System Settings

### Crew Module (`/kfood_crew/`)

- Accessible by: Crew
- Features:
  - Order Queue
  - Kitchen Display
  - Inventory Updates
  - Status Updates

### Customer Module (`/k_food_customer/`)

- Accessible by: All users
- Features:
  - Menu Browsing
  - Order Placement
  - Profile Management
  - Order History

## Role Assignment

- Roles are assigned during account creation
- Role changes require superadmin approval
- Each user can have only one role
- Role changes are logged for audit purposes

## Security Considerations

1. Regular audit of user roles
2. Principle of least privilege
3. Regular validation of role assignments
4. Monitoring of role-based access patterns
5. Documentation of role changes
