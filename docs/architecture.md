# KfoodDelights WebSocket Architecture

## Overview

The KfoodDelights system uses a unified WebSocket architecture to enable real-time communication between three major modules:

- Customer Module (k_food_customer)
- Crew Module (k_food_crew)
- Admin Module (k_food_admin)

### Core Components

#### 1. Global WebSocket Manager (Client-side)

- Handles client-side WebSocket connections
- Manages authentication and session persistence
- Implements role-based message routing
- Provides automatic reconnection logic

#### 2. Central WebSocket Server

- Manages all client connections
- Routes messages based on user roles
- Handles authentication and session management
- Broadcasts updates to relevant subscribers

#### 3. Module-specific Message Handlers

- Customer Handler: Processes order updates, notifications
- Crew Handler: Manages order queue, status updates
- Admin Handler: Monitors system events, metrics

## Connection Lifecycle

1. **Initialization**

   ```javascript
   // Client connects with role-based authentication
   const wsManager = new GlobalWebSocketManager({
     userType: "customer|crew|admin",
     reconnectAttempts: 5,
   });
   ```

2. **Authentication**

   ```javascript
   // Server validates connection
   {
     type: 'authentication',
     userType: 'customer',
     token: 'jwt_token'
   }
   ```

3. **Message Routing**

   ```javascript
   // Example: Order status update flow
   Customer -> Server -> Crew & Admin
   Crew -> Server -> Customer & Admin
   ```

4. **Error Handling**
   - Automatic reconnection attempts
   - Fallback to polling if WebSocket fails
   - Error logging and monitoring

## Message Types

### 1. System Messages

- Authentication
- Heartbeat
- Error notifications

### 2. Order Flow Messages

- New order placement
- Order status updates
- Payment confirmations

### 3. Monitoring Messages

- System alerts
- Performance metrics
- User activity logs

## Security Considerations

1. **Authentication**

   - JWT-based authentication
   - Role-based access control
   - Session validation

2. **Data Protection**

   - Message encryption
   - Input validation
   - Rate limiting

3. **Error Handling**
   - Graceful degradation
   - Automatic reconnection
   - Error logging

## Implementation Details

### Server Configuration

```php
// WebSocket server settings
define('WS_HOST', 'localhost');
define('WS_PORT', '8080');
define('WS_PATH', '/ws');
```

### Client Integration

```javascript
// Initialize WebSocket connection
document.addEventListener("DOMContentLoaded", () => {
  window.wsManager = new GlobalWebSocketManager({
    debug: true,
    reconnectAttempts: 5,
  });
});
```

### Message Format

```javascript
{
    type: 'message_type',
    data: {
        // Message specific data
    },
    timestamp: '2025-09-19T10:00:00Z',
    sender: {
        id: 'user_id',
        type: 'user_type'
    }
}
```
