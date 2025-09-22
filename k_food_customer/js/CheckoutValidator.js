class CheckoutValidator {
  constructor() {
    this.errors = [];
  }

  validateOrderData(orderData) {
    this.errors = [];

    // Check customer info
    this.validateCustomerInfo(orderData.customerInfo);

    // Check items
    this.validateItems(orderData.items);

    // Check payment
    this.validatePayment(orderData.payment);

    // Check amounts
    this.validateAmounts(orderData.amounts);

    return {
      isValid: this.errors.length === 0,
      errors: this.errors,
    };
  }

  validateCustomerInfo(customerInfo) {
    if (!customerInfo) {
      this.errors.push("Customer information is required");
      return;
    }

    // Validate name
    if (!customerInfo.name || customerInfo.name.trim().length < 2) {
      this.errors.push("Valid customer name is required");
    }

    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!customerInfo.email || !emailRegex.test(customerInfo.email)) {
      this.errors.push("Valid email address is required");
    }

    // Validate phone
    const phoneRegex = /^[0-9+\-\s()]{10,}$/;
    if (!customerInfo.phone || !phoneRegex.test(customerInfo.phone)) {
      this.errors.push("Valid phone number is required");
    }

    // Validate address
    if (!customerInfo.address || customerInfo.address.trim().length < 5) {
      this.errors.push("Valid delivery address is required");
    }
  }

  validateItems(items) {
    if (!Array.isArray(items) || items.length === 0) {
      this.errors.push("Cart items are required");
      return;
    }

    items.forEach((item, index) => {
      if (!item.name || !item.price || !item.quantity) {
        this.errors.push(`Invalid item data at position ${index + 1}`);
      }
      if (item.quantity < 1) {
        this.errors.push(`Invalid quantity for item: ${item.name}`);
      }
      if (item.price < 0) {
        this.errors.push(`Invalid price for item: ${item.name}`);
      }
    });
  }

  validatePayment(payment) {
    if (!payment || !payment.method) {
      this.errors.push("Payment method is required");
      return;
    }

    if (!["cash", "gcash"].includes(payment.method)) {
      this.errors.push("Invalid payment method");
    }

    if (payment.method === "gcash" && !payment.reference) {
      this.errors.push("GCash reference number is required");
    }
  }

  validateAmounts(amounts) {
    if (!amounts) {
      this.errors.push("Order amounts are required");
      return;
    }

    if (typeof amounts.subtotal !== "number" || amounts.subtotal < 0) {
      this.errors.push("Invalid subtotal amount");
    }

    if (typeof amounts.total !== "number" || amounts.total < 0) {
      this.errors.push("Invalid total amount");
    }

    // Verify total calculation
    const expectedTotal = amounts.subtotal - (amounts.discount || 0);
    if (Math.abs(expectedTotal - amounts.total) > 0.01) {
      this.errors.push("Total amount calculation mismatch");
    }
  }
}
