# UI Components

## Order Summary Components

The order summary displays order details in a clear, organized manner:

```
Order Summary
------------
- Item list with quantities and prices
- Subtotal
- Applied discounts (if any)
- Total amount

Note: Delivery fees have been removed from all order summaries.
```

## Checkout Flow Components

1. Cart Review

   - Item list
   - Quantity adjusters
   - Individual item totals
   - Cart subtotal

2. Payment Selection

   - Payment method options
   - Discount/promo code input
   - Final amount summary

3. Order Confirmation
   - Order details
   - Payment confirmation
   - Order tracking information

## Layout Updates

- Order summary spacing has been adjusted to maintain proper visual hierarchy
- Total amounts now appear with increased padding for better visibility
- Discount sections are properly aligned without the delivery fee row
- All monetary values are right-aligned for better readability

## Responsive Design

All components maintain proper spacing and alignment across:

- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## Accessibility

- All monetary values have proper ARIA labels
- Discount toggles maintain proper focus states
- Color contrast ratios meet WCAG guidelines
