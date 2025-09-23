# Order Flow Documentation

## Order Lifecycle

### 1. Order Creation

When a customer places an order:

```javascript
// Customer side - Updated structure
orderConfirmationHandler.showConfirmation({
    customerName: string,   // Required
    phone: string,         // Required, 11 digits
    address: string,       // Required
    instructions: string,  // Optional
    items: Array<{
        name: string,
        quantity: number,
        price: number
    }>,
    amounts: {
        subtotal: number,
        deliveryFee: number,
        total: number,
        discount?: number,
        seniorPwdDiscount?: number
    },
    paymentMethod: "cash" | "gcash"
});
```

### 2. AJAX Communication

The order flows through these steps:

1. **Customer Confirmation**

   - Customer reviews order in modal
   - Clicks "Confirm Order"
   - Order submitted via AJAX POST

2. **Server Processing**

   - Validates order data
   - Generates unique order ID
   - Stores in database
   - Broadcasts to crew dashboard

3. **Crew Dashboard**

   - Receives `NEW_ORDER` event
   - Updates UI in real-time
   - Shows order details

4. **Status Updates**
   - Crew updates order status
   - Status changes broadcast to customer
   - Customer UI updates automatically

### 3. State Management

Orders can have these states:

- `pending`: Initial state
- `confirmed`: Accepted by crew
- `preparing`: In kitchen
- `ready`: Ready for delivery
- `delivering`: Out for delivery
- `delivered`: Completed
- `cancelled`: Cancelled by customer/crew

## Real-Time Updates

### Customer Side

```javascript
// Listen for updates
websocket.on(WebSocketMessageTypes.ORDER_UPDATED, (data) => {
  updateOrderStatus(data.status);
  showNotification(data.message);
});
```

### Crew Side

```javascript
// Send status updates
websocket.send({
  type: WebSocketMessageTypes.ORDER_UPDATED,
  data: {
    orderId: string,
    status: string,
    timestamp: Date,
  },
});
```

## Error Handling

### Common Error Scenarios:

1. **Connection Loss**

   - Queue updates locally
   - Retry on reconnection
   - Show sync status to user

2. **Invalid Data**

   - Validate before sending
   - Show error feedback
   - Allow retry/cancel

3. **Timeout**
   - Retry with backoff
   - Show pending status
   - Allow manual refresh

## UI/UX Guidelines

### Status Indicators

- Use clear color coding:
  - Pending: Yellow
  - Confirmed: Blue
  - Preparing: Orange
  - Ready: Green
  - Delivering: Purple
  - Delivered: Gray
  - Cancelled: Red

### Notifications

- Show toast messages for:
  - Order confirmation
  - Status changes
  - Errors/issues
  - Delivery updates

### Modal Design

- Clean, minimal interface
- Clear call-to-action buttons
- Loading indicators
- Error feedback
- Success confirmation

## Integration Points

### 1. Database

- Orders table
- Status history
- Customer info
- Payment details

### 2. WebSocket Events

- Order creation
- Status updates
- Cancellation
- Delivery tracking

### 3. External Services

- Payment processing
- SMS notifications
- Email confirmations
- Maps/location

## Testing Checklist

- [ ] Order submission works
- [ ] Real-time updates received
- [ ] Status changes reflect
- [ ] Errors handled gracefully
- [ ] UI updates correctly
- [ ] Network issues managed
- [ ] Data validated

## Performance Considerations

- Optimize payload size
- Batch status updates
- Cache order details
- Limit reconnection attempts
- Clean up old listeners
