// Utility functions for amount calculations and validation
const ORDER_CONSTANTS = {
  MIN_ORDER_AMOUNT: 100.0,
  MAX_ORDER_AMOUNT: 10000.0,
  MAX_DISCOUNT_PERCENTAGE: 25.0,
};

function validateAmounts(amounts, cartTotal) {
  const errors = [];
  let isValid = true;

  // Helper function to check if numbers match within 0.01
  const numbersMatch = (a, b) => Math.abs(a - b) <= 0.01;

  // Validate subtotal
  if (amounts.subtotal < ORDER_CONSTANTS.MIN_ORDER_AMOUNT) {
    errors.push(
      `Minimum order amount is ₱${ORDER_CONSTANTS.MIN_ORDER_AMOUNT.toFixed(2)}`
    );
    isValid = false;
  }
  if (amounts.subtotal > ORDER_CONSTANTS.MAX_ORDER_AMOUNT) {
    errors.push(
      `Maximum order amount is ₱${ORDER_CONSTANTS.MAX_ORDER_AMOUNT.toFixed(2)}`
    );
    isValid = false;
  }
  if (!numbersMatch(amounts.subtotal, cartTotal)) {
    errors.push("Cart total mismatch");
    isValid = false;
  }

  // Delivery fee validation removed

  // Validate discount
  const maxDiscount =
    amounts.subtotal * (ORDER_CONSTANTS.MAX_DISCOUNT_PERCENTAGE / 100);
  if (amounts.discount < 0 || amounts.discount > maxDiscount) {
    errors.push(
      `Invalid discount amount (maximum ${ORDER_CONSTANTS.MAX_DISCOUNT_PERCENTAGE}% of subtotal)`
    );
    isValid = false;
  }

  // Validate total
  const expectedTotal = amounts.subtotal - amounts.discount;
  if (!numbersMatch(amounts.total, expectedTotal)) {
    errors.push("Order total calculation mismatch");
    isValid = false;
  }

  // Check for negative values
  Object.entries(amounts).forEach(([key, value]) => {
    if (value < 0) {
      errors.push(`Invalid negative amount for ${key}`);
      isValid = false;
    }
  });

  return { isValid, errors };
}

// Function to format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
  }).format(amount);
}

// Function to update order summary display
function updateOrderSummary(amounts) {
  const summaryElements = {
    subtotal: document.getElementById("display-subtotal"),
    discount: document.getElementById("display-discount"),
    total: document.getElementById("display-total"),
  };

  Object.entries(amounts).forEach(([key, value]) => {
    if (summaryElements[key]) {
      summaryElements[key].textContent = formatCurrency(value);
    }
  });
}

// Function to recalculate order amounts
function recalculateAmounts(cartItems, promoDiscount = 0) {
  const subtotal = cartItems.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  );
  const existingDiscount = parseFloat(
    document.getElementById("discount")?.value || "0"
  );
  const totalDiscount = existingDiscount + promoDiscount;
  const total = subtotal - totalDiscount;

  return {
    subtotal,
    discount: totalDiscount,
    total,
  };
}
