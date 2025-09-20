# Crew Module: KfoodDelights

## Overview

The crew dashboard (`kfood_crew/index.php`) allows crew members to view, prepare, and update orders in real time.

## Real-Time Order Handling

- Connects to the WebSocket server on page load.
- Listens for `orderPlaced` (type: `new_order`) events and updates the order list instantly.
- Crew can mark orders as preparing, ready, or completed. These actions are sent back to the server and broadcast to customers/admins.

## UI/UX

- Clean, food-centric design with warm palette and rounded elements.
- Orders are displayed in a grid with status filters (pending, preparing, ready, delivered).
- Notifications and sounds alert crew to new orders and status changes.

## Security

- Session is validated on every page load.
- Only authenticated crew can access the dashboard and receive order events.

## Fallback

- If WebSocket fails, the dashboard polls for new orders every 10 seconds.

## Inline Comments

- All major logic in `index.php` and JS handlers is documented inline for maintainability.

---

See also: `order-sync.md` and `websocket.md` for technical details.
