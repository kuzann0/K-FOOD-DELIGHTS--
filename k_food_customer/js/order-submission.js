// Order submission handling functions
async function validateOrderForm(form) {
  const errors = [];
  let isValid = true;

  // Validate each field defined in validationRules
  for (const [fieldId, rules] of Object.entries(validationRules)) {
    const element = form.querySelector(`#${fieldId}`);
    if (element && !validateField(element, rules, form)) {
      isValid = false;
      errors.push(`Invalid ${rules.name.toLowerCase()}`);
    }
  }

  // Validate payment method
  const selectedPayment = form.querySelector(
    'input[name="paymentMethod"]:checked'
  );
  if (!selectedPayment) {
    isValid = false;
    errors.push("Please select a payment method");
  }

  // Validate cart items
  const cartValidation = validateCartItems();
  if (!cartValidation.isValid) {
    isValid = false;
    errors.push(...cartValidation.errors);
  }

  return { isValid, errors };
}

async function prepareOrderData(form) {
  // Get selected payment method
  const selectedPayment = form.querySelector(
    'input[name="paymentMethod"]:checked'
  );
  if (!selectedPayment) {
    throw new Error("Please select a payment method");
  }

  // Get cart items
  const cartItems = JSON.parse(
    document.getElementById("cart-data")?.value || "[]"
  );
  if (!cartItems.length) {
    throw new Error("Your cart is empty");
  }

  // Calculate amounts using the amount handler
  const amounts = recalculateAmounts(cartItems);

  // Get discount information
  const seniorDiscount =
    document.getElementById("seniorDiscount")?.checked || false;
  const pwdDiscount = document.getElementById("pwdDiscount")?.checked || false;

  // Prepare the complete order data structure
  const orderData = {
    customerInfo: {
      name: form.querySelector("#fullName").value,
      email: form.querySelector("#email").value,
      phone: form.querySelector("#phone").value,
      address: form.querySelector("#address").value,
      deliveryInstructions:
        form.querySelector("#deliveryInstructions")?.value || "",
    },
    payment: {
      method: selectedPayment.value,
    },
    items: cartItems.map((item) => ({
      product_id: item.product_id,
      name: item.name,
      price: item.price,
      quantity: item.quantity,
    })),
    amounts: {
      subtotal: amounts.subtotal,
      deliveryFee: amounts.deliveryFee,
      totalDiscount: amounts.discount,
      total: amounts.total,
    },
    discounts: {
      seniorDiscount,
      pwdDiscount,
      seniorId: seniorDiscount
        ? document.getElementById("seniorId")?.value
        : null,
      pwdId: pwdDiscount ? document.getElementById("pwdId")?.value : null,
    },
  };

  // Add GCash details if that's the selected payment method
  if (orderData.payment.method === "gcash") {
    orderData.payment.gcashNumber = form.querySelector("#gcashNumber")?.value;
    orderData.payment.gcashReference =
      form.querySelector("#gcashReference")?.value;

    if (!orderData.payment.gcashNumber || !orderData.payment.gcashReference) {
      throw new Error("Please provide complete GCash payment details");
    }
  }

  return orderData;
}

async function submitOrder(orderData) {
  try {
    // Start loading state
    showLoadingIndicator();

    // Log order data for debugging
    console.log("Submitting order:", JSON.stringify(orderData, null, 2));

    // Submit order to server
    const response = await fetch("process_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token":
          document.querySelector('meta[name="csrf-token"]')?.content || "",
        Accept: "application/json",
      },
      body: JSON.stringify(orderData),
      credentials: "same-origin", // Include session cookies
    });

    // Get response as text first for debugging
    const responseText = await response.text();
    console.log("Server response:", responseText);

    // Try to parse response as JSON
    let data;
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      console.error("JSON parse error:", e);
      throw new Error("Server returned invalid JSON response");
    }

    // Handle error responses
    if (!response.ok) {
      if (data.errorCode === "VALIDATION_ERROR") {
        throw new ValidationError(data.message, data.errors);
      } else if (data.errorCode === "AUTHENTICATION_ERROR") {
        throw new AuthError(data.message);
      } else {
        throw new Error(data.message || `Server error (${response.status})`);
      }
    }

    // Validate successful response
    if (!data || typeof data !== "object") {
      throw new Error("Invalid response format");
    }

    return data;
  } catch (error) {
    // Enhance error with context if needed
    if (error.name === "TypeError" && error.message.includes("fetch")) {
      throw new Error("Network error - please check your connection");
    }
    throw error;
  } finally {
    hideLoadingIndicator();
  }
}

async function handleOrderResponse(response) {
  if (!response.success) {
    throw new Error(response.message || "Order processing failed");
  }

  // Show success message with order details
  showNotification(
    "success",
    `Order #${response.orderNumber} placed successfully! ` +
      `Total: ₱${response.total}. ` +
      "Redirecting to confirmation page..."
  );

  // Clear cart data
  localStorage.removeItem("cart");

  // Trigger cart update event
  document.dispatchEvent(
    new CustomEvent("cartUpdated", {
      detail: { items: [], total: 0 },
    })
  );

  // Save order details for confirmation page
  sessionStorage.setItem(
    "lastOrder",
    JSON.stringify({
      orderId: response.orderId,
      orderNumber: response.orderNumber,
      total: response.total,
      timestamp: new Date().toISOString(),
    })
  );

  // Redirect to confirmation page
  setTimeout(() => {
    window.location.href =
      response.routing.redirectUrl ||
      `order_confirmation.php?order_id=${response.orderId}`;
  }, 1500);
}

function handleOrderError(error) {
  console.error("Order processing error:", error);

  // Remove loading state
  hideLoadingIndicator();

  // Handle specific error types
  if (error instanceof ValidationError) {
    // Show validation errors in relevant form fields
    error.errors.forEach((err) => {
      const field = document.getElementById(err.field);
      if (field) {
        highlightError(field, err.message);
      }
    });
    showNotification("error", "Please correct the highlighted fields");
  } else if (error instanceof AuthError) {
    // Redirect to login if session expired
    showNotification("error", "Please log in to continue");
    setTimeout(() => {
      window.location.href =
        "login.php?redirect=" + encodeURIComponent(window.location.href);
    }, 1500);
  } else {
    // Show general error message
    showNotification("error", error.message || "Failed to process your order");
  }
}

// Helper functions for loading state
function showLoadingIndicator() {
  const submitBtn = document.querySelector(".place-order-btn");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Processing...';
  }
}

function hideLoadingIndicator() {
  const submitBtn = document.querySelector(".place-order-btn");
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.innerHTML = "Place Order";
  }
}

// Custom error classes
class ValidationError extends Error {
  constructor(message, errors = []) {
    super(message);
    this.name = "ValidationError";
    this.errors = errors;
  }
}

class AuthError extends Error {
  constructor(message) {
    super(message);
    this.name = "AuthError";
  }
}

function validateCartItems() {
  const cartItems = JSON.parse(
    document.getElementById("cart-data")?.value || "[]"
  );
  const errors = [];
  let isValid = true;

  if (!Array.isArray(cartItems) || !cartItems.length) {
    return {
      isValid: false,
      errors: ["Your cart is empty"],
    };
  }

  // Calculate cart total for validation
  let cartTotal = 0;
  const maxQuantityPerItem = 10;
  const maxItemPrice = 10000;

  // Validate each cart item
  cartItems.forEach((item, index) => {
    // Required fields check
    const requiredFields = ["product_id", "name", "price", "quantity"];
    const missingFields = requiredFields.filter((field) => !item[field]);

    if (missingFields.length) {
      isValid = false;
      errors.push(
        `Missing required fields (${missingFields.join(", ")}) for item ${
          index + 1
        }`
      );
      return;
    }

    // Name validation
    if (typeof item.name !== "string" || !item.name.trim()) {
      isValid = false;
      errors.push(`Invalid name for item ${index + 1}`);
    }

    // Price validation
    const price = parseFloat(item.price);
    if (isNaN(price) || price <= 0 || price > maxItemPrice) {
      isValid = false;
      errors.push(`Invalid price (₱${item.price}) for ${item.name}`);
    }

    // Quantity validation
    const quantity = parseInt(item.quantity);
    if (
      !Number.isInteger(quantity) ||
      quantity < 1 ||
      quantity > maxQuantityPerItem
    ) {
      isValid = false;
      errors.push(
        `Invalid quantity (${item.quantity}) for ${item.name}. Maximum allowed: ${maxQuantityPerItem}`
      );
    }

    // Add to cart total
    if (price && quantity) {
      cartTotal += price * quantity;
    }
  });

  // Validate cart total against limits
  if (cartTotal < 100) {
    isValid = false;
    errors.push("Minimum order amount is ₱100");
  } else if (cartTotal > 50000) {
    isValid = false;
    errors.push("Maximum order amount is ₱50,000");
  }

  return { isValid, errors };
}
