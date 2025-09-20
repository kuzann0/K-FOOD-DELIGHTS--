# WebSocket Configuration and Order Flow Documentation

## WebSocket Configuration

The WebSocket system in KfoodDelights uses a centralized configuration through the `WebSocketConfig` class. This ensures consistent connection handling across customer and crew interfaces.

### Configuration Structure

```javascript
{
  url: string,         // Primary WebSocket URL
  fallbackUrls: [],    // Backup URLs if primary fails
  protocols: [],       // WebSocket protocols used
  reconnectDelay: int, // Delay between reconnection attempts
  maxReconnectAttempts: int, // Maximum reconnection tries
  maxQueueSize: int,   // Maximum queued messages
  messageTimeout: int,  // Message acknowledgment timeout
  debug: boolean       // Enable debug logging
}
```

### Fallback Behavior

If the primary WebSocket connection fails, the system will:

1. Retry the primary URL up to 3 times
2. Try fallback URLs in sequence
3. Implement exponential backoff between attempts

## Order Flow

### Customer Side (checkout.php)

1. Customer reviews order in cart
2. Clicks "Place Order" button
3. `OrderConfirmationHandler` shows confirmation modal
4. Upon confirmation:
   - Sends `NEW_ORDER` event via WebSocket
   - Awaits `ORDER_CONFIRMED` response
   - Redirects to order confirmation page

### Crew Side (crew/index.php)

1. Crew dashboard WebSocket client listens for orders
2. New order appears instantly with status indicators
3. Crew can update order status:
   - Confirmed
   - Preparing
   - Ready for delivery
   - Delivered

## Error Handling

### Connection Errors

- Invalid WebSocket URL
- Server unavailable
- Authentication failure

### System Response

1. Automatic reconnection with exponential backoff
2. Message queuing during disconnection
3. Automatic message retry on failure
4. User notification of connection status

### Data Validation

All messages are validated for:

- Required fields
- Data types
- Business logic constraints

## Message Types

### Customer → Server

- `NEW_ORDER`
- `ORDER_CANCELLED`
- `AUTHENTICATE`

### Server → Customer

- `ORDER_CONFIRMED`
- `ORDER_UPDATED`
- `ERROR`

### Server → Crew

- `NEW_ORDER`
- `ORDER_CANCELLED`
- `STATUS_UPDATE`

## Theme Guidelines

### Modal Styling

- Background: White (#FFFFFF)
- Primary buttons: Brand color (#FF6B6B)
- Secondary buttons: Gray (#6C757D)
- Borders: Light gray (#DEE2E6)
- Border radius: 8px
- Shadows: 0 2px 4px rgba(0,0,0,0.1)

### Feedback Messages

- Success: Green (#28A745)
- Error: Red (#DC3545)
- Info: Blue (#17A2B8)
- Warning: Yellow (#FFC107)

### Typography

- Headers: Poppins, 16-24px
- Body text: Roboto, 14-16px
- Buttons: Poppins, 14px

## Initialization Order

1. Load WebSocket configuration
2. Initialize WebSocket connection
3. Set up event handlers
4. Load UI components
5. Enable user interactions

## Security Considerations

- All WebSocket connections use secure protocols (WSS)
- Authentication required for all connections
- CSRF tokens included in requests
- Input sanitization on both ends
- Rate limiting implemented
- Session validation
