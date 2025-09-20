# KfoodDelights: Food Ordering Platform

## System Overview

KfoodDelights is a warm, modern, and food-centric web platform for seamless food ordering, real-time crew updates, and delightful customer experiences. The system features robust order management, live notifications, and a clean, inviting UI inspired by the colors and vibrancy of Korean cuisine.

## Key Features

- **Order Placement & Cart**: Customers add items to cart and place orders via a guided checkout (`checkout.php`).
- **Order Lifecycle**: Orders are validated, saved to MySQL, and broadcast in real time to the crew dashboard via WebSocket.
- **Real-time Crew Dashboard**: Crew staff receive instant notifications of new orders and status updates.
- **Role-based Access**: Admin, crew, and customer roles with tailored dashboards and permissions.
- **Profile & Preferences**: Users manage delivery info, payment preferences, and account details.
- **Security**: Input validation, session management, and error handling throughout.

## Technologies Used

- **PHP** (backend logic, order processing)
- **MySQL** (order, user, and notification storage)
- **WebSocket** (real-time order/notification updates)
- **HTML/CSS/JS** (modern, responsive UI)
- **Composer** (dependency management)

## Setup & Installation

1. **Clone the repository**
2. **Install dependencies**: `composer install`
3. **Configure database**: Update `config.php` with your MySQL credentials
4. **Run setup scripts**: Execute provided SQL scripts in `/sql` to create tables (`users`, `orders`, `order_items`, `notifications`, etc.)
5. **Start WebSocket server**: `php includes/websocket_server.php` (or use the provided batch/shell scripts)
6. **Configure environment**: (Optional) Set up SSL and environment variables for production

## System Requirements

- PHP 7.4+
- MySQL 5.7+
- Composer
- WebSocket support (port 8080 by default)
- SSL certificate (recommended for production)

## Project Structure

```
k_food_customer/
├── api/                 # API endpoints (cart, user, payment, etc.)
├── includes/            # Core PHP logic (WebSocket, notifications, validation)
├── js/                  # Client-side scripts (checkout, cart, websocket)
├── css/                 # Stylesheets (theme, cart, checkout, notifications)
├── docs/                # Documentation
├── process_order.php    # Handles order DB insertion & WebSocket broadcast
├── checkout.php         # Customer checkout UI & logic
└── ...
```

## Order Lifecycle: Customer to Crew Dashboard

1. **Customer places order** via `checkout.php` (cart review, delivery info, payment method)
2. **Order is validated** and sent to `process_order.php` (AJAX, JSON)
3. **Order is saved** to the database (`orders`, `order_items` tables)
4. **WebSocket event** `new_order` is broadcast to crew/admin dashboards in real time
5. **Crew dashboard** receives and displays the new order instantly
6. **Order status updates** (preparing, out for delivery, completed) are pushed live to the customer

## UI & Design

- **Theme**: Clean, modern, food-inspired (warm reds, soft whites, rounded corners)
- **Fonts**: Poppins, Roboto
- **Layout**: Responsive, mobile-friendly, intuitive navigation
- **Order summary**: Clear, visually appealing cart and checkout modals
- **Notifications**: Toast-style, real-time updates for both customers and crew

## Monitoring & Maintenance

- **Notification logs**: All order/notification events are logged and can be reviewed by admins
- **Database cleanup**: Automated scripts for old notifications and logs
- **WebSocket server**: Monitored for uptime, auto-restart on failure

## Documentation

- `docs/order-flow.md`: Full order journey from customer to crew (see file for details)
- `docs/integrity-check.md`: List of verified modules and their operational status

---

For a full technical breakdown, see the documentation in `/docs`.

KfoodDelights – Bringing the taste of Korea to your doorstep, with a smile!
