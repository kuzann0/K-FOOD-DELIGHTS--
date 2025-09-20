# Order Flow: Customer to Crew Dashboard

This document describes the complete journey of an order in the KfoodDelights system, from customer checkout to crew dashboard notification and fulfillment.

## 1. Customer Checkout (`checkout.php`)

- Customer reviews cart, enters delivery details, and selects payment method.
- On submission, order data is validated client-side and sent via AJAX to `process_order.php`.

## 2. Order Processing (`process_order.php`)

- Validates order data and user session.
- Generates a unique order number.
- Inserts order and items into the `orders` and `order_items` tables in MySQL.
- If payment is GCash, stores payment reference.
- Creates notifications for crew and admin.
- Broadcasts a `new_order` event via WebSocket to all connected crew/admin dashboards.

## 3. Crew Dashboard (WebSocket Listener)

- Crew dashboard receives the `new_order` event in real time.
- Displays new order details and updates order list instantly.
- Crew can update order status (preparing, out for delivery, completed), which is also broadcast to the customer via WebSocket.

## 4. Customer Order Tracking

- Customer receives live status updates for their order via WebSocket.
- Order confirmation and estimated delivery time are shown.

## 5. Admin Monitoring

- Admins can view all orders, notifications, and logs for auditing and support.

---

**Technologies:** PHP, MySQL, WebSocket, JavaScript, HTML/CSS

**See also:** `integrity-check.md` for module verification.
