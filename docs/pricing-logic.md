# Price Calculations

## Order Total Calculation

The total order amount is calculated using the following formula:

```
Total = Subtotal - Discounts
```

Where:

- Subtotal: Sum of (item price × quantity) for all items in the order
- Discounts:
  - Senior Citizen: 20% of subtotal (if applicable)
  - PWD: 15% of subtotal (if applicable)
  - Promotional discounts (if any)

_Note: As of September 21, 2025, delivery fees have been removed from all price calculations._

## Example Price Calculation

```javascript
// Basic price calculation
const orderTotal = subtotal - totalDiscount;

// With senior citizen discount (20%)
const seniorDiscount = subtotal * 0.2;
const orderTotalWithSenior = subtotal - seniorDiscount;

// With PWD discount (15%)
const pwdDiscount = subtotal * 0.15;
const orderTotalWithPWD = subtotal - pwdDiscount;
```

## Important Notes

1. Delivery fees are no longer included in any calculations
2. Discounts are applied to the subtotal before calculating the final amount
3. Multiple discounts (e.g., promotions) can be stacked as per business rules
4. All monetary values are stored with 2 decimal places for accuracy
