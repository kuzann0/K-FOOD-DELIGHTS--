# Real-Time Order Synchronization: Customer to Crew

## Overview

This document describes the real-time order sync flow between the customer checkout (`checkout.php`) and the crew dashboard (`kfood_crew/index.php`) in KfoodDelights.

## Flow

1. **Customer places order** via `checkout.php`.
2. **Order is processed** and saved in the database (`process_order.php`).
3. **WebSocket event** `orderPlaced` (type: `new_order`) is emitted with order details.
4. **WebSocket server** (`websocket_server.php`) broadcasts the event to all connected crew clients.
5. **Crew dashboard** (`index.php`) receives the event and updates the order list in real time.
6. **Crew actions** (preparing, ready, completed) are sent back via WebSocket and update the order status for the customer.

## Fallback

- If WebSocket fails, the crew dashboard polls for new orders every 10 seconds via AJAX.

## Security

- Sessions are validated on both ends.
- Only authenticated crew can access the dashboard and receive order events.

---

See also: `websocket.md` for server setup and event structure.
