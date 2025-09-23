# WebSocket Integration Testing Guide

## Test Environment Setup

1. Start WebSocket server:

```bash
php websocket_server.php
```

2. Start test instances:

- Customer interface: http://localhost/k_food_customer
- Crew dashboard: http://localhost/k_food_crew
- Admin panel: http://localhost/kfood_admin

## Test Cases

### 1. Connection & Authentication

#### TC1.1: Initial Connection

- **Objective**: Verify successful WebSocket connection for each module
- **Steps**:
  1. Open browser console
  2. Load each module interface
  3. Check connection status indicator
- **Expected**: Status shows "Connected" with appropriate color indicator
- **Validation**: Console shows connection success message

#### TC1.2: Authentication Flow

- **Objective**: Verify role-based authentication
- **Steps**:
  1. Login as customer/crew/admin
  2. Monitor WebSocket authentication message
- **Expected**: Authentication success message, role-specific handlers activated
- **Validation**: Status changes to "Authenticated"

### 2. Order Processing Flow

#### TC2.1: New Order Creation

- **Objective**: Verify order propagation from customer to crew/admin
- **Steps**:
  1. Place new order as customer
  2. Monitor crew dashboard
  3. Check admin panel
- **Expected**:
  - Customer receives order confirmation
  - Crew dashboard shows new order alert
  - Admin panel updates order count
- **Validation**:
  ```javascript
  // Test via Console
  wsManager.send({
    type: "new_order",
    data: {
      orderId: "test_123",
      items: [
        {
          id: 1,
          name: "Test Item",
          quantity: 1,
        },
      ],
      total: 100,
    },
  });
  ```

#### TC2.2: Order Status Updates

- **Objective**: Verify status change propagation
- **Steps**:
  1. Crew updates order status
  2. Check customer interface
  3. Verify admin dashboard
- **Expected**:
  - Customer sees real-time status update
  - Admin panel reflects current status
- **Validation**:
  ```javascript
  // Test via Console
  wsManager.send({
    type: "order_status",
    data: {
      orderId: "test_123",
      status: "preparing",
    },
  });
  ```

### 3. Real-time Updates

#### TC3.1: Concurrent Order Processing

- **Objective**: Test system under multiple simultaneous orders
- **Steps**:
  1. Place multiple orders in quick succession
  2. Monitor crew dashboard response
  3. Check admin metrics update
- **Expected**:
  - All orders appear in correct sequence
  - No missing or duplicate orders
  - System maintains responsiveness

#### TC3.2: Notification Delivery

- **Objective**: Verify notifications across all modules
- **Steps**:
  1. Trigger various notification events
  2. Check notification display
  3. Verify sound alerts (where applicable)
- **Expected**:
  - Browser notifications appear
  - Sound plays for crew new orders
  - Admin receives system alerts

### 4. Error Handling

#### TC4.1: Connection Loss Recovery

- **Objective**: Verify automatic reconnection
- **Steps**:
  1. Simulate network interruption
  2. Monitor reconnection attempts
  3. Verify state recovery
- **Expected**:
  - Shows disconnected status
  - Attempts reconnection
  - Recovers previous state upon reconnection

#### TC4.2: Message Queue Management

- **Objective**: Test message handling during disconnection
- **Steps**:
  1. Disconnect WebSocket
  2. Perform actions
  3. Restore connection
- **Expected**:
  - Actions queue during offline
  - Messages resync on reconnection
  - No data loss

### 5. Performance Testing

#### TC5.1: Load Testing

- **Objective**: Verify system under heavy load
- **Steps**:
  1. Simulate 50+ concurrent connections
  2. Process multiple orders simultaneously
  3. Monitor system resources
- **Expected**:
  - Maintains responsive performance
  - No message loss
  - Stable memory usage

#### TC5.2: Long-term Stability

- **Objective**: Verify system stability over time
- **Steps**:
  1. Run system continuously for 24h
  2. Process regular transactions
  3. Monitor for memory leaks
- **Expected**:
  - Stable performance
  - No resource leaks
  - Consistent message delivery

## Validation Script

```javascript
// Test Suite Helper
class WebSocketTester {
  constructor(userType) {
    this.wsManager = new GlobalWebSocketManager({
      userType: userType,
      debug: true,
    });

    this.testResults = [];
  }

  async runTests() {
    await this.testConnection();
    await this.testAuthentication();
    await this.testMessageHandling();
    await this.testReconnection();
    this.reportResults();
  }

  async testConnection() {
    try {
      await this.wsManager.connect();
      this.logResult("Connection Test", true);
    } catch (error) {
      this.logResult("Connection Test", false, error);
    }
  }

  async testAuthentication() {
    return new Promise((resolve) => {
      this.wsManager.on("authentication", (data) => {
        this.logResult("Authentication Test", data.status === "success");
        resolve();
      });
    });
  }

  async testMessageHandling() {
    return new Promise((resolve) => {
      const testMessage = {
        type: "test",
        data: { test: true },
      };

      this.wsManager.on("test", (data) => {
        this.logResult("Message Handling Test", data.test === true);
        resolve();
      });

      this.wsManager.send(testMessage);
    });
  }

  async testReconnection() {
    this.wsManager.ws.close();
    return new Promise((resolve) => {
      setTimeout(() => {
        const isReconnected = this.wsManager.ws.readyState === WebSocket.OPEN;
        this.logResult("Reconnection Test", isReconnected);
        resolve();
      }, 5000);
    });
  }

  logResult(testName, passed, error = null) {
    this.testResults.push({
      name: testName,
      passed: passed,
      error: error?.message,
    });
  }

  reportResults() {
    console.table(this.testResults);
  }
}

// Usage:
const tester = new WebSocketTester("customer");
tester.runTests();
```

## System Validation Checklist

- [ ] All modules connect successfully
- [ ] Authentication works for each role
- [ ] Real-time updates propagate correctly
- [ ] Notifications appear as expected
- [ ] Error handling works properly
- [ ] Reconnection logic functions
- [ ] System performs under load
- [ ] No memory leaks detected
- [ ] All test cases pass
- [ ] Documentation is complete

## Performance Metrics

- Connection time: < 2 seconds
- Message delivery: < 500ms
- Reconnection time: < 5 seconds
- Max concurrent connections: 1000+
- Memory usage: < 100MB
- CPU usage: < 30%
- Uptime: 99.9%
