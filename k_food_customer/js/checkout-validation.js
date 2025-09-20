// Custom error for validation failures
class OrderValidationError extends Error {
  constructor(message) {
    super(message);
    this.name = "OrderValidationError";
  }
}

// Input sanitization utilities
const Sanitizer = {
  // Remove potentially dangerous characters and standardize input
  sanitizeGeneral(value, options = {}) {
    const {
      allowNumbers = true,
      allowLetters = true,
      allowSpaces = true,
      allowPunctuation = false,
      maxLength = null,
    } = options;

    let sanitized = String(value || "").trim();

    // Remove invisible characters and standardize spaces
    sanitized = sanitized
      .replace(/[\u200B-\u200D\uFEFF]/g, "") // Remove zero-width spaces
      .replace(/[\u00A0\u1680\u180e\u2000-\u200a\u202f\u205f\u3000]/g, " ") // Replace special spaces
      .replace(/\s+/g, " "); // Standardize spaces

    // Apply character filters
    let allowedChars = "";
    if (allowLetters) allowedChars += "a-zA-ZÀ-ÿ";
    if (allowNumbers) allowedChars += "0-9";
    if (allowSpaces) allowedChars += "\\s";
    if (allowPunctuation) allowedChars += ".,!?'\"-_()[]{}";

    const regex = new RegExp(`[^${allowedChars}]`, "g");
    sanitized = sanitized.replace(regex, "");

    // Enforce maximum length if specified
    if (maxLength && sanitized.length > maxLength) {
      sanitized = sanitized.substring(0, maxLength);
    }

    return sanitized;
  },

  // Sanitize and format a name
  sanitizeName(name) {
    let sanitized = this.sanitizeGeneral(name, {
      allowLetters: true,
      allowSpaces: true,
      allowNumbers: false,
      maxLength: 100,
    });

    // Proper case conversion with special character handling
    return sanitized.replace(
      /\w\S*/g,
      (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
    );
  },

  // Sanitize and format a phone number
  sanitizePhone(phone) {
    let digits = String(phone || "").replace(/[^\d+]/g, "");

    // Handle international format
    if (digits.startsWith("+63")) {
      digits = "0" + digits.substring(3);
    }

    // Enforce exactly 11 digits for PH numbers
    if (digits.length === 11 && digits.startsWith("09")) {
      return digits;
    }

    return "";
  },

  // Sanitize and format an email address
  sanitizeEmail(email) {
    let sanitized = String(email || "")
      .trim()
      .toLowerCase()
      .replace(/\s+/g, "");

    // Basic email format validation
    if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(sanitized)) {
      return "";
    }

    return sanitized;
  },

  // Sanitize and format an address
  sanitizeAddress(address) {
    let sanitized = this.sanitizeGeneral(address, {
      allowLetters: true,
      allowNumbers: true,
      allowSpaces: true,
      allowPunctuation: true,
      maxLength: 200,
    });

    // Standardize common abbreviations
    const abbreviations = {
      "st\\.": "Street",
      "rd\\.": "Road",
      "ave\\.": "Avenue",
      "apt\\.": "Apartment",
      "bldg\\.": "Building",
      "brgy\\.": "Barangay",
      "bg\\.": "Barangay",
    };

    Object.entries(abbreviations).forEach(([abbr, full]) => {
      const regex = new RegExp(`\\b${abbr}\\b`, "gi");
      sanitized = sanitized.replace(regex, full);
    });

    // Proper spacing after punctuation
    sanitized = sanitized
      .replace(/,(?!\s)/g, ", ")
      .replace(/\.(?!\s)/g, ". ")
      .replace(/\s+/g, " ")
      .trim();

    // Capitalize first letter of each sentence
    sanitized = sanitized.replace(/(^\w|\.\s+\w)/g, (letter) =>
      letter.toUpperCase()
    );

    return sanitized;
  },

  // Sanitize and format GCash reference number
  sanitizeGcashRef(ref) {
    let sanitized = String(ref || "")
      .trim()
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, "");

    // GCash reference format validation (letter followed by 12 numbers)
    if (/^[A-Z][0-9]{12}$/.test(sanitized)) {
      return sanitized;
    }

    return "";
  },
};

class OrderValidator {
  constructor(form) {
    this.form = form;
  }

  async validateAmounts() {
    const cartItems = this.getCartItems();
    const amounts = this.getAmounts();
    const errors = [];

    // Calculate expected totals
    const calculatedSubtotal = cartItems.reduce(
      (sum, item) => sum + item.price * item.quantity,
      0
    );

    const calculatedTotal =
      calculatedSubtotal + (amounts.deliveryFee || 0) - (amounts.discount || 0);

    // Validate subtotal
    if (Math.abs(calculatedSubtotal - amounts.subtotal) > 0.01) {
      errors.push(
        `Cart total mismatch (expected: ₱${calculatedSubtotal.toFixed(
          2
        )}, got: ₱${amounts.subtotal.toFixed(2)})`
      );
      console.error("Subtotal mismatch:", {
        calculated: calculatedSubtotal,
        displayed: amounts.subtotal,
      });
    }

    // Validate total
    if (Math.abs(calculatedTotal - amounts.total) > 0.01) {
      errors.push(
        `Order total mismatch (expected: ₱${calculatedTotal.toFixed(
          2
        )}, got: ₱${amounts.total.toFixed(2)})`
      );
      console.error("Total mismatch:", {
        calculated: calculatedTotal,
        displayed: amounts.total,
      });
    }

    // Validate individual amounts
    if (amounts.subtotal < 0) errors.push("Subtotal cannot be negative");
    if (amounts.deliveryFee < 0) errors.push("Delivery fee cannot be negative");
    if (amounts.discount < 0) errors.push("Discount cannot be negative");
    if (amounts.total < 0) errors.push("Total amount cannot be negative");
    if (amounts.discount > amounts.subtotal)
      errors.push("Discount cannot exceed subtotal");

    return {
      isValid: errors.length === 0,
      errors,
    };
  }

  getAmounts() {
    return {
      subtotal: this.parseAmount("#subtotal"),
      deliveryFee: this.parseAmount("#delivery-fee"),
      discount: this.parseAmount("#discount"),
      total: this.parseAmount("#total"),
    };
  }

  parseAmount(selector) {
    const element = this.form.querySelector(selector);
    return element ? parseFloat(element.value || "0") : 0;
  }

  getCartItems() {
    try {
      return JSON.parse(localStorage.getItem("cart") || "[]");
    } catch (e) {
      console.error("Failed to parse cart items:", e);
      return [];
    }
  }

  validateGcashReference() {
    const element = this.form.querySelector("#gcashReference");
    if (!element) return { isValid: true };

    const value = element.value.trim();
    let isValid = true;
    let errorMessage = "";

    if (!value) {
      isValid = false;
      errorMessage = "GCash reference number is required";
    } else if (!/^[A-Z][0-9]{12}$/.test(value.toUpperCase())) {
      isValid = false;
      errorMessage =
        "Please enter a valid GCash reference number (1 letter followed by 12 numbers)";
    }

    if (!isValid) {
      highlightError(element, errorMessage);
    } else {
      removeError(element);
    }

    return { isValid, errorMessage };
  }

  validateCustomerInfo() {
    const fields = {
      fullName: {
        selector: "#fullName",
        sanitizer: Sanitizer.sanitizeName.bind(Sanitizer),
        message: "Please enter a valid full name",
      },
      email: {
        selector: "#email",
        sanitizer: Sanitizer.sanitizeEmail.bind(Sanitizer),
        message: "Please enter a valid email address",
      },
      phone: {
        selector: "#phone",
        sanitizer: Sanitizer.sanitizePhone.bind(Sanitizer),
        message: "Please enter a valid phone number (e.g., 09123456789)",
      },
      address: {
        selector: "#address",
        sanitizer: Sanitizer.sanitizeAddress.bind(Sanitizer),
        message: "Please enter a valid delivery address",
      },
    };

    const errors = [];
    const customerInfo = {};

    for (const [field, config] of Object.entries(fields)) {
      const element = this.form.querySelector(config.selector);
      if (!element) continue;

      const sanitized = config.sanitizer(element.value);
      if (!sanitized) {
        errors.push(config.message);
        highlightError(element, config.message);
      } else {
        removeError(element);
        customerInfo[field] = sanitized;
      }
    }

    return {
      isValid: errors.length === 0,
      errors,
      data: customerInfo,
    };
  }
}

class OrderSubmissionHandler {
  constructor(form) {
    this.form = form;
    this.validator = new OrderValidator(form);
    this.submitButton = form.querySelector('button[type="submit"]');
    this.originalButtonText = this.submitButton?.textContent || "Place Order";

    // Bind event handlers
    this.handleSubmit = this.handleSubmit.bind(this);
    this.form.addEventListener("submit", this.handleSubmit);
  }

  setButtonState(state) {
    if (!this.submitButton) return;

    const states = {
      initial: {
        text: this.originalButtonText,
        disabled: false,
      },
      processing: {
        text: "Processing...",
        disabled: true,
      },
      validating: {
        text: "Validating...",
        disabled: true,
      },
      error: {
        text: this.originalButtonText,
        disabled: false,
      },
    };

    const newState = states[state] || states.initial;
    this.submitButton.textContent = newState.text;
    this.submitButton.disabled = newState.disabled;
  }

  async handleSubmit(event) {
    event.preventDefault();

    try {
      this.setButtonState("validating");

      // Validate customer information
      const customerValidation = this.validator.validateCustomerInfo();
      if (!customerValidation.isValid) {
        throw new OrderValidationError(customerValidation.errors[0]);
      }

      // Validate cart items
      const cartItems = this.validator.getCartItems();
      if (!cartItems.length) {
        throw new OrderValidationError("Your cart is empty");
      }

      // Validate amounts
      const amountValidation = await this.validator.validateAmounts();
      if (!amountValidation.isValid) {
        throw new OrderValidationError(amountValidation.errors[0]);
      }

      // Get payment method
      const paymentMethod = this.form.querySelector(
        'input[name="paymentMethod"]:checked'
      );
      if (!paymentMethod) {
        throw new OrderValidationError("Please select a payment method");
      }

      // Validate GCash reference if applicable
      if (paymentMethod.value === "gcash") {
        const gcashValidation = this.validator.validateGcashReference();
        if (!gcashValidation.isValid) {
          throw new OrderValidationError(gcashValidation.errorMessage);
        }
      }

      // Prepare order data
      const orderData = {
        customer: customerValidation.data,
        items: cartItems,
        payment: {
          method: paymentMethod.value,
          reference: this.form.querySelector("#gcashReference")?.value || null,
        },
        amounts: this.validator.getAmounts(),
      };

      // Submit order
      this.setButtonState("processing");
      const response = await fetch("process_order.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token":
            document.querySelector('meta[name="csrf-token"]')?.content || "",
        },
        body: JSON.stringify(orderData),
      });

      if (!response.ok) {
        throw new Error(`Server error: ${response.status}`);
      }

      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || "Failed to process order");
      }

      // Show success and redirect
      showNotification("success", "Order placed successfully!");
      setTimeout(() => {
        window.location.href = `order_confirmation.php?order_id=${result.orderId}`;
      }, 1500);
    } catch (error) {
      this.handleError(error);
    }
  }

  handleError(error) {
    console.error("Order submission error:", error);
    this.setButtonState("error");

    const message =
      error instanceof OrderValidationError
        ? error.message
        : "An error occurred while processing your order. Please try again.";

    showNotification("error", message);
  }
}

// UI helper functions
function highlightError(element, message) {
  element.classList.add("is-invalid");
  const feedback = element.nextElementSibling;
  if (feedback?.classList.contains("invalid-feedback")) {
    feedback.textContent = message;
  } else {
    const errorDiv = document.createElement("div");
    errorDiv.className = "invalid-feedback";
    errorDiv.textContent = message;
    element.parentNode.insertBefore(errorDiv, element.nextSibling);
  }
}

function removeError(element) {
  element.classList.remove("is-invalid");
  const feedback = element.nextElementSibling;
  if (feedback?.classList.contains("invalid-feedback")) {
    feedback.textContent = "";
  }
}

// Initialize form handling
document.addEventListener("DOMContentLoaded", () => {
  const checkoutForm = document.getElementById("checkoutForm");
  if (checkoutForm) {
    new OrderSubmissionHandler(checkoutForm);
  }
});
