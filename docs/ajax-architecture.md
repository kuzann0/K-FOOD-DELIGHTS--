# AJAX Architecture Documentation

## Component Architecture

### Core Components

#### OrderConfirmationHandler

The `OrderConfirmationHandler` is implemented as a singleton using a static initialization pattern:

```javascript
// Correct initialization
window.orderConfirmationHandler = OrderConfirmationHandler.init();
```

The handler manages order confirmation modals and processes order submissions through AJAX requests. It automatically handles CSRF tokens and error states.

#### Best Practices

1. Always use the static `init()` method to get the singleton instance
2. Never create instances directly with the constructor
3. Handle initialization errors appropriately

## Real-Time Data Reloading Architecture

This section documents the AJAX-based polling system that replaces WebSocket connections.

### Overview

The KfoodDelights system uses optimized AJAX polling for real-time updates:

- Regular status checks for orders, kitchen, and system metrics
- Event-driven updates for immediate actions
- Configurable polling intervals based on module needs

### Key Features

- Reliable data synchronization
- Efficient server resource usage
- Improved error handling
- Simplified client implementation

### Module-Specific Implementations

#### Customer Module

1. **Order Status Updates**

   - Endpoint: `/api/check_order_status.php`
   - Polling Interval: 5 seconds
   - Data: Current order status, estimated time

2. **Cart Updates**
   - Endpoint: `/api/cart_handler.php`
   - Event-driven: Updates on cart modifications

#### Crew Module

1. **Active Orders**

   - Endpoint: `/api/fetch_orders.php`
   - Polling Interval: 3 seconds
   - Data: Active orders list with statuses

2. **Kitchen Updates**
   - Endpoint: `/api/kitchen_status.php`
   - Polling Interval: 10 seconds

#### Admin Module

1. **System Metrics**

   - Endpoint: `/api/system_metrics.php`
   - Polling Interval: 30 seconds
   - Data: Order volumes, revenue stats

2. **Alert Monitoring**
   - Endpoint: `/api/system_alerts.php`
   - Polling Interval: 15 seconds

### Implementation Details

```javascript
class AjaxHandler {
  constructor(config) {
    this.endpoint = config.endpoint;
    this.interval = config.interval;
    this.onSuccess = config.onSuccess;
    this.onError = config.onError;
    this.pollTimer = null;
  }

  startPolling() {
    this.poll();
    this.pollTimer = setInterval(() => this.poll(), this.interval);
  }

  stopPolling() {
    if (this.pollTimer) {
      clearInterval(this.pollTimer);
    }
  }

  async poll() {
    try {
      const response = await fetch(this.endpoint);
      if (!response.ok) throw new Error("Network response error");
      const data = await response.json();
      this.onSuccess(data);
    } catch (error) {
      this.onError(error);
    }
  }
}
```

### Performance Optimizations

- Dynamic polling intervals
- Response compression
- Partial updates
- Request debouncing
- Response caching

### Security Measures

- JWT authentication
- Rate limiting
- Input validation
- Role-based access

### Monitoring

- Response time tracking
- Error rate monitoring
- Server load metrics
- Client-side performance

## Menu Creation Flow

### Request Format

#### Create Menu Item

- **Endpoint**: `/api/create_menu_item.php`
- **Method**: POST
- **Content-Type**: multipart/form-data
- **Fields**:
  - category_id (integer)
  - name (string)
  - description (string)
  - price (float)
  - is_available (boolean)
  - item_image (file)

#### Get Categories

- **Endpoint**: `/api/get_categories.php`
- **Method**: GET
- **Response**: JSON array of categories

#### Get Menu Items

- **Endpoint**: `/api/get_menu_items.php`
- **Method**: GET
- **Response**: JSON array of menu items

### Response Structure

All API responses follow this structure:

```json
{
  "success": boolean,
  "message": string,
  "data": object (optional)
}
```

### Error Handling

Errors are returned with:

- HTTP status code
- JSON response with error details
- Client-side notification display

### Security

- All endpoints require admin authentication
- Permission checks for menu management
- Input validation and sanitization
- Prepared statements for database queries
- Image upload validation and sanitization

### UI Feedback

The system provides real-time feedback through:

- Loading indicators during operations
- Success/error notifications
- Dynamic UI updates after operations
- Form validation messages
