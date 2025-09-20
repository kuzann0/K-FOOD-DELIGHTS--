# WebSocket Integration for KfoodDelights

## Overview

KfoodDelights uses a PHP Ratchet-based WebSocket server (`websocket_server.php`) to enable real-time communication between the customer and crew modules.

## Server Setup

- Located at `k_food_customer/websocket_server.php`.
- Listens on port 8080 (or 3000 for SSL).
- Handles authentication, subscriptions, and event broadcasting.

## Event Structure

- **orderPlaced** (type: `new_order`): Sent when a customer places an order. Contains order ID, items, customer info, timestamp, delivery instructions, and payment method.
- **order_update**: Sent when crew updates order status (preparing, ready, completed).
- **authentication**: Sent on successful authentication.
- **error**: Sent on error or invalid action.

## Fallback Logic

- If WebSocket connection fails, the crew dashboard falls back to AJAX polling every 10 seconds.

## Security

- Only authenticated users (customer/crew/admin) can subscribe to events.
- All data is sanitized and validated before transmission.

---

See also: `order-sync.md` for the real-time order flow.
