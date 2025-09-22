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
  if (!form || !(form instanceof HTMLFormElement)) {
    throw new Error("Invalid form element provided");
  }

  // Get selected payment method with robust validation
  const paymentInputs = form.querySelectorAll('input[name="paymentMethod"]');
  if (paymentInputs.length === 0) {
    throw new Error("Payment method options not found");
  }

  const selectedPayment = Array.from(paymentInputs).find(
    (input) => input.checked
  );
  if (!selectedPayment) {
    throw new Error("Please select a payment method");
  }

  // Get cart items with error handling
  let cartItems = [];
  const cartDataElement = document.getElementById("cart-data");
  if (!cartDataElement?.value) {
    throw new Error("Cart data is missing");
  }

  try {
    cartItems = JSON.parse(cartDataElement.value);
    if (!Array.isArray(cartItems)) {
      throw new Error("Invalid cart data format");
    }
  } catch (error) {
    console.error("Cart parse error:", error);
    throw new Error("Unable to process cart data");
  }

  if (!cartItems.length) {
    throw new Error("Your cart is empty");
  }

  // Validate cart items structure
  if (
    !cartItems.every(
      (item) =>
        item &&
        typeof item === "object" &&
        typeof item.product_id !== "undefined" &&
        typeof item.quantity === "number" &&
        item.quantity > 0
    )
  ) {
    throw new Error("Invalid items in cart");
  }

  // Calculate amounts using the amount handler
  const amounts = recalculateAmounts(cartItems);

  // Get discount information
  const seniorDiscount =
    document.getElementById("seniorDiscount")?.checked || false;
  const pwdDiscount = document.getElementById("pwdDiscount")?.checked || false;

  // Safely get form field value with validation
  const getFieldValue = (selector, required = true) => {
    const element = form.querySelector(selector);
    if (!element) {
      if (required) {
        throw new Error(`Required field not found: ${selector}`);
      }
      return "";
    }
    const value = element.value.trim();
    if (required && !value) {
      throw new Error(`${selector.replace("#", "")} is required`);
    }
    return value;
  };

  // Prepare the complete order data structure with validation
  const orderData = {
    customerInfo: {
      name: getFieldValue("#fullName"),
      email: getFieldValue("#email"),
      phone: getFieldValue("#phone"),
      address: getFieldValue("#address"),
      deliveryInstructions: getFieldValue("#deliveryInstructions", false),
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

  // Add GCash details if that's the selected payment method with robust validation
  if (orderData.payment.method === "gcash") {
    const gcashNumber = getFieldValue("#gcashNumber");
    const gcashReference = getFieldValue("#gcashReference");

    // Validate GCash number format (11 digits)
    if (!/^\d{11}$/.test(gcashNumber)) {
      throw new Error("GCash number must be 11 digits");
    }

    // Validate reference number format
    if (!/^[A-Za-z0-9]{6,}$/.test(gcashReference)) {
      throw new Error("Invalid GCash reference number format");
    }

    orderData.payment.gcashNumber = gcashNumber;
    orderData.payment.gcashReference = gcashReference;
  }

  // Final validation of the complete order data
  const requiredFields = ["name", "email", "phone", "address"];
  for (const field of requiredFields) {
    if (!orderData.customerInfo[field]) {
      throw new Error(
        `${field.charAt(0).toUpperCase() + field.slice(1)} is required`
      );
    }
  }

  // Validate email format
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(orderData.customerInfo.email)) {
    throw new Error("Please enter a valid email address");
  }

  // Validate phone format (must be numbers, optionally starting with +)
  if (!/^\+?\d{10,}$/.test(orderData.customerInfo.phone)) {
    throw new Error("Please enter a valid phone number");
  }

  // Make sure we have a valid total amount
  if (
    typeof orderData.amounts.total !== "number" ||
    orderData.amounts.total <= 0
  ) {
    throw new Error("Invalid order total");
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
