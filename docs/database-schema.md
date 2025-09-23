# Database Schema

## Orders Table

```sql
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    special_instructions TEXT,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'processing', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    subtotal DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    promo_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

_Note: The delivery_fee field has been removed from the orders table as of September 21, 2025._

## Schema Changes

1. Removed Fields:
   - delivery_fee (DECIMAL(10,2))
2. Affected Calculations:
   - total_amount now equals subtotal minus discounts (no delivery fee)
   - All price calculations updated accordingly

## Data Migration

For existing orders:

1. total_amount values have been updated to exclude delivery fees
2. Historical delivery fee data has been archived for record-keeping
