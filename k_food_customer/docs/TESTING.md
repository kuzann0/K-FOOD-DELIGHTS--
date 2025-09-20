# Testing Documentation

## Test Scenarios

### 1. Order Processing

#### Cart Management

- Add items to cart
- Update quantities
- Remove items
- Calculate totals
- Validate stock

#### Checkout Process

- Form validation
- Address verification
- Payment selection
- Order summary
- Confirmation

### 2. Payment Integration

#### GCash QR

- QR generation
- Payment validation
- Transaction status
- Error handling
- Timeout handling

### 3. Real-time Notifications

#### WebSocket Connection

- Connection establishment
- Message delivery
- Reconnection handling
- Error recovery

#### Notification Types

- Order updates
- Payment status
- System alerts
- Error messages

### 4. User Authentication

#### Registration

- Form validation
- Duplicate checks
- Email verification
- Phone verification

#### Login

- Credentials check
- Session management
- Remember me
- Password reset

## Test Cases

### Order Processing

```php
class OrderProcessingTest extends TestCase
{
    public function testOrderCreation()
    {
        $orderData = [
            'items' => [
                ['id' => 1, 'quantity' => 2],
                ['id' => 2, 'quantity' => 1]
            ],
            'total' => 150.00,
            'delivery_address' => '123 Test St'
        ];

        $order = new Order();
        $result = $order->create($orderData);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['order_id']);
    }

    public function testInvalidOrder()
    {
        $orderData = [
            'items' => [],
            'total' => 0,
            'delivery_address' => ''
        ];

        $order = new Order();
        $result = $order->create($orderData);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
    }
}
```

### Payment Processing

```php
class PaymentTest extends TestCase
{
    public function testQRGeneration()
    {
        $payment = new GCashQRGenerator();
        $result = $payment->generate(100.00, 'TEST123');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['qr_code']);
    }

    public function testPaymentValidation()
    {
        $payment = new PaymentProcessor();
        $result = $payment->validate('REF123');

        $this->assertTrue($result['success']);
        $this->assertEquals('completed', $result['status']);
    }
}
```

### Notification System

```php
class NotificationTest extends TestCase
{
    public function testNotificationDispatch()
    {
        $notification = new WebSocketHandler();
        $result = $notification->dispatch('new_order', [
            'order_id' => 123,
            'status' => 'pending'
        ]);

        $this->assertTrue($result['sent']);
        $this->assertNotNull($result['timestamp']);
    }

    public function testNotificationLogging()
    {
        $logger = new NotificationLogger();
        $result = $logger->log('test_event', 'Test message');

        $this->assertTrue($result);
        $this->assertDatabaseHas('notification_logs', [
            'event' => 'test_event'
        ]);
    }
}
```

## Performance Tests

### Load Testing

```php
class LoadTest extends TestCase
{
    public function testConcurrentOrders()
    {
        $concurrent = 50;
        $results = [];

        for ($i = 0; $i < $concurrent; $i++) {
            $results[] = async(function() {
                return $this->createTestOrder();
            });
        }

        $responses = await($results);
        $successful = array_filter($responses, fn($r) => $r['success']);

        $this->assertCount($concurrent, $successful);
    }
}
```

### Stress Testing

```php
class StressTest extends TestCase
{
    public function testWebSocketConnections()
    {
        $connections = [];
        $maxConnections = 1000;

        for ($i = 0; $i < $maxConnections; $i++) {
            $connections[] = new WebSocket();
        }

        $active = array_filter($connections, fn($c) => $c->isActive());
        $this->assertGreaterThan($maxConnections * 0.95, count($active));
    }
}
```

## Test Environment Setup

### Database

```sql
-- Create test database
CREATE DATABASE kfood_test;

-- Create test tables
SOURCE sql/create_tables.sql;

-- Insert test data
SOURCE sql/test_data.sql;
```

### Configuration

```php
// Test environment config
define('DB_NAME', 'kfood_test');
define('PUSHER_APP_ID', 'test_app_id');
define('PUSHER_KEY', 'test_key');
define('PUSHER_SECRET', 'test_secret');
```

## Test Data

### Sample Orders

```json
{
  "orders": [
    {
      "id": "TEST001",
      "items": [
        { "id": 1, "quantity": 2 },
        { "id": 2, "quantity": 1 }
      ],
      "total": 150.0,
      "status": "pending"
    }
  ]
}
```

### Sample Users

```json
{
  "users": [
    {
      "id": "TEST001",
      "email": "test@example.com",
      "role": "customer"
    }
  ]
}
```

## Continuous Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "7.4"

      - name: Run Tests
        run: |
          composer install
          php vendor/bin/phpunit
```

## Test Coverage

### PHPUnit Configuration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory>includes</directory>
            <directory>api</directory>
        </include>
    </coverage>
</phpunit>
```

## Debugging Tests

### Error Logging

```php
// Test specific logging
function logTestError($message, $context = []) {
    file_put_contents(
        'logs/test_errors.log',
        date('Y-m-d H:i:s') . ": $message\n" .
        json_encode($context) . "\n\n",
        FILE_APPEND
    );
}
```

### Test Helpers

```php
trait TestHelpers
{
    protected function createTestOrder()
    {
        return [
            'items' => [
                ['id' => 1, 'quantity' => 1]
            ],
            'total' => 50.00
        ];
    }

    protected function mockWebSocket()
    {
        return new MockWebSocket();
    }
}
```
