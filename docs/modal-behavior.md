# Modal Behavior Documentation

## OrderConfirmationHandler Modal

### Overview

The OrderConfirmationHandler manages the order confirmation modal with the following features:

- Real-time order preview
- WebSocket integration
- Error handling
- Theme consistency
- Accessibility support

### HTML Structure

```html
<div id="orderConfirmationModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Confirm Your Order</h2>
      <button type="button" class="close-btn">&times;</button>
    </div>
    <div class="modal-body">
      <div class="order-details">
        <div class="order-items"></div>
        <div class="order-total"></div>
      </div>
      <div class="delivery-info">
        <p class="delivery-address"></p>
        <p class="delivery-time"></p>
      </div>
      <div class="alert-container"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary cancel-order-btn">
        Cancel
      </button>
      <button type="button" class="btn btn-primary confirm-order-btn">
        Confirm Order
      </button>
    </div>
  </div>
</div>
```

### CSS Styling

```css
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
}

.modal.show {
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  width: 90%;
  max-width: 500px;
  margin: 20px;
  animation: modalOpen 0.3s ease-out;
}

/* Theme colors */
.btn-primary {
  background: #ff6b6b;
  border-color: #ff6b6b;
}

.btn-secondary {
  background: #6c757d;
  border-color: #6c757d;
}

/* Alert styles */
.alert {
  padding: 12px;
  margin: 10px 0;
  border-radius: 4px;
}

.alert-success {
  background: #d4edda;
  border-color: #c3e6cb;
  color: #155724;
}

.alert-danger {
  background: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
}
```

### JavaScript Integration

#### Initialization

```javascript
OrderConfirmationHandler.init()
  .then((handler) => {
    window.orderConfirmationHandler = handler;
  })
  .catch((error) => {
    console.error("Modal initialization failed:", error);
  });
```

#### Show Confirmation

```javascript
orderConfirmationHandler.showConfirmation({
  items: [...],
  total: 1234.56,
  address: "123 Main St",
  customerId: "user123"
});
```

### WebSocket Integration

The modal communicates with the WebSocket server for:

1. Sending new orders
2. Receiving confirmations
3. Handling errors
4. Managing state

### Error Handling

The modal handles these error scenarios:

- WebSocket connection failures
- Invalid data
- Server errors
- Timeout issues

### Accessibility

The modal implements:

- Keyboard navigation
- ARIA labels
- Focus management
- Screen reader support

### Theme Consistency

Colors follow the KfoodDelights theme:

- Primary: #FF6B6B
- Secondary: #6C757D
- Success: #28A745
- Danger: #DC3545
- Info: #17A2B8

### Animation

Uses CSS transitions for:

- Modal open/close
- Button states
- Alerts
- Loading indicators

### State Management

Tracks these states:

- Idle
- Loading
- Success
- Error
- Cancelled

### Event Flow

1. User clicks "Place Order"
2. Modal shows with order details
3. User confirms or cancels
4. WebSocket sends event
5. Server processes order
6. Modal shows result
7. Redirects on success

### Performance

- Lazy loads resources
- Optimizes animations
- Manages memory
- Cleans up listeners
