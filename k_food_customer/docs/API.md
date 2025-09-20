# API Documentation

## Order Management

### Create Order

```
POST /api/process_order.php
```

**Request Body:**

```json
{
  "items": [
    {
      "id": "integer",
      "quantity": "integer",
      "notes": "string"
    }
  ],
  "delivery_address": "string",
  "payment_method": "string"
}
```

**Response:**

```json
{
  "success": true,
  "order_id": "integer",
  "message": "string"
}
```

### Get Order Status

```
GET /api/fetch_orders.php?order_id={id}
```

**Response:**

```json
{
  "order_id": "integer",
  "status": "string",
  "items": [],
  "total": "float",
  "created_at": "datetime"
}
```

## Cart Operations

### Add to Cart

```
POST /api/cart_operations.php
```

**Request Body:**

```json
{
  "action": "add",
  "item_id": "integer",
  "quantity": "integer"
}
```

**Response:**

```json
{
  "success": true,
  "cart_count": "integer",
  "message": "string"
}
```

### Remove from Cart

```
POST /api/cart_operations.php
```

**Request Body:**

```json
{
  "action": "remove",
  "item_id": "integer"
}
```

## User Management

### Update Profile

```
POST /api/update_profile.php
```

**Request Body:**

```json
{
  "name": "string",
  "email": "string",
  "phone": "string",
  "address": "string"
}
```

### Update Password

```
POST /api/update_password.php
```

**Request Body:**

```json
{
  "current_password": "string",
  "new_password": "string",
  "confirm_password": "string"
}
```

## Payment Processing

### Generate QR Code

```
POST /api/generate_qr.php
```

**Request Body:**

```json
{
  "amount": "float",
  "order_id": "integer"
}
```

**Response:**

```json
{
  "success": true,
  "qr_code": "string",
  "expiry": "datetime"
}
```

### Process Payment

```
POST /api/process_qr.php
```

**Request Body:**

```json
{
  "order_id": "integer",
  "payment_reference": "string"
}
```

## Notifications

### Get User Notifications

```
GET /api/get_notifications.php
```

**Response:**

```json
{
  "notifications": [
    {
      "id": "integer",
      "type": "string",
      "message": "string",
      "created_at": "datetime",
      "is_read": "boolean"
    }
  ]
}
```

### Mark Notification as Read

```
POST /api/update_notification.php
```

**Request Body:**

```json
{
  "notification_id": "integer",
  "action": "mark_read"
}
```

## Error Responses

All API endpoints may return the following error responses:

### 400 Bad Request

```json
{
  "success": false,
  "error": "string",
  "message": "string"
}
```

### 401 Unauthorized

```json
{
  "success": false,
  "message": "Authentication required"
}
```

### 403 Forbidden

```json
{
  "success": false,
  "message": "Access denied"
}
```

### 500 Server Error

```json
{
  "success": false,
  "message": "Internal server error"
}
```

## WebSocket Events

### Order Updates

```javascript
// Subscribe to order updates
const channel = pusher.subscribe("orders");
channel.bind("order-update", function (data) {
  // Handle order update
});
```

### New Notifications

```javascript
// Subscribe to notifications
const channel = pusher.subscribe("notifications");
channel.bind("new-notification", function (data) {
  // Handle new notification
});
```
