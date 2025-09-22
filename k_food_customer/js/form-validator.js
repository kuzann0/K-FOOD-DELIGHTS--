/**
 * FormValidator - Form validation module for KFood Delights
 * Requires: ui-utilities.js
 */
var FormValidator = {
  /**
   * Initialize the form validator
   */
  init: function () {
    if (!window.UIUtilities) {
      console.error("UIUtilities not found. Please include ui-utilities.js");
      return;
    }
  },

  /**
   * Validate a checkout form
   * @param {HTMLFormElement} form Form element to validate
   * @returns {Object} Validation result with isValid and errors
   */
  validateCheckoutForm: function (form) {
    if (!form || !(form instanceof HTMLFormElement)) {
      throw new Error("Invalid form element provided");
    }

    var errors = [];

    // Basic field validation using UIUtilities
    var basicValidation = UIUtilities.validateForm(form, {
      showErrors: true,
      scrollToError: true,
    });

    if (!basicValidation.valid) {
      errors = basicValidation.errors.map(function (error) {
        return error.message;
      });
    }

    // Payment method validation
    var selectedPayment = form.querySelector(
      'input[name="paymentMethod"]:checked'
    );
    if (!selectedPayment) {
      errors.push("Please select a payment method");
      this.showFieldError(
        form.querySelector("#payment-methods"),
        "Please select a payment method"
      );
    }

    // GCash validation if selected
    if (selectedPayment && selectedPayment.value === "gcash") {
      var gcashNumber = form.querySelector("#gcashNumber");
      var gcashReference = form.querySelector("#gcashReference");

      if (gcashNumber && !/^\d{11}$/.test(gcashNumber.value.trim())) {
        errors.push("GCash number must be 11 digits");
        this.showFieldError(gcashNumber, "GCash number must be 11 digits");
      }

      if (
        gcashReference &&
        !/^[A-Za-z0-9]{6,}$/.test(gcashReference.value.trim())
      ) {
        errors.push("Invalid GCash reference number format");
        this.showFieldError(gcashReference, "Invalid reference number format");
      }
    }

    // Cart validation
    var cartItems = this.validateCartItems();
    if (!cartItems.valid) {
      errors = errors.concat(cartItems.errors);
    }

    // Discount validation
    if (form.querySelector("#seniorDiscount:checked")) {
      var seniorId = form.querySelector("#seniorId");
      if (!seniorId || !seniorId.value.trim()) {
        errors.push("Senior Citizen ID is required");
        this.showFieldError(seniorId, "Senior Citizen ID is required");
      }
    }

    if (form.querySelector("#pwdDiscount:checked")) {
      var pwdId = form.querySelector("#pwdId");
      if (!pwdId || !pwdId.value.trim()) {
        errors.push("PWD ID is required");
        this.showFieldError(pwdId, "PWD ID is required");
      }
    }

    return {
      valid: errors.length === 0,
      errors: errors,
    };
  },

  /**
   * Validate cart items
   * @returns {Object} Validation result
   */
  validateCartItems: function () {
    var errors = [];
    var cartDataElement = document.getElementById("cart-data");

    if (!cartDataElement || !cartDataElement.value) {
      errors.push("Cart is empty");
      return { valid: false, errors: errors };
    }

    try {
      var cartItems = JSON.parse(cartDataElement.value);
      if (!Array.isArray(cartItems) || cartItems.length === 0) {
        errors.push("Cart is empty");
        return { valid: false, errors: errors };
      }

      // Validate each item
      cartItems.forEach(function (item) {
        if (!item || typeof item !== "object") {
          errors.push("Invalid cart item");
          return;
        }

        if (
          !item.product_id ||
          !item.name ||
          typeof item.price !== "number" ||
          typeof item.quantity !== "number"
        ) {
          errors.push("Invalid item data: " + item.name);
        }

        if (item.quantity <= 0) {
          errors.push("Invalid quantity for: " + item.name);
        }
      });
    } catch (error) {
      console.error("Cart validation error:", error);
      errors.push("Invalid cart data format");
    }

    return {
      valid: errors.length === 0,
      errors: errors,
      items: cartItems,
    };
  },

  /**
   * Prepare order data from form
   * @param {HTMLFormElement} form Form element
   * @returns {Object} Prepared order data
   */
  prepareOrderData: function (form) {
    var validation = this.validateCheckoutForm(form);
    if (!validation.valid) {
      throw new Error("Please fix the form errors before submitting");
    }

    // Get field values with validation
    var getFieldValue = function (selector, required) {
      var element = form.querySelector(selector);
      if (!element) {
        if (required) {
          throw new Error("Required field not found: " + selector);
        }
        return "";
      }
      var value = element.value.trim();
      if (required && !value) {
        throw new Error(selector.replace("#", "") + " is required");
      }
      return value;
    };

    // Get cart data
    var cartValidation = this.validateCartItems();
    if (!cartValidation.valid) {
      throw new Error(cartValidation.errors[0]);
    }

    // Get selected payment method
    var selectedPayment = form.querySelector(
      'input[name="paymentMethod"]:checked'
    );
    if (!selectedPayment) {
      throw new Error("Please select a payment method");
    }

    // Calculate amounts
    var amounts = window.recalculateAmounts(cartValidation.items);

    // Prepare the order data
    var orderData = {
      customerInfo: {
        name: getFieldValue("#fullName", true),
        email: getFieldValue("#email", true),
        phone: getFieldValue("#phone", true),
        address: getFieldValue("#address", true),
        deliveryInstructions: getFieldValue("#deliveryInstructions", false),
      },
      payment: {
        method: selectedPayment.value,
      },
      items: cartValidation.items.map(function (item) {
        return {
          product_id: item.product_id,
          name: item.name,
          price: item.price,
          quantity: item.quantity,
        };
      }),
      amounts: amounts,
      discounts: {
        seniorDiscount: form.querySelector("#seniorDiscount")?.checked || false,
        pwdDiscount: form.querySelector("#pwdDiscount")?.checked || false,
        seniorId: form.querySelector("#seniorId")?.value || null,
        pwdId: form.querySelector("#pwdId")?.value || null,
      },
    };

    // Add GCash details if selected
    if (orderData.payment.method === "gcash") {
      orderData.payment.gcashNumber = getFieldValue("#gcashNumber", true);
      orderData.payment.gcashReference = getFieldValue("#gcashReference", true);
    }

    return orderData;
  },

  /**
   * Show error for a specific field
   * @param {HTMLElement} element Field element
   * @param {string} message Error message
   */
  showFieldError: function (element, message) {
    if (!element) return;

    element.classList.add("invalid");
    var errorElement = document.createElement("div");
    errorElement.className = "error-message";
    errorElement.textContent = message;

    // Remove any existing error message
    var existingError = element.parentNode.querySelector(".error-message");
    if (existingError) {
      existingError.remove();
    }

    element.parentNode.appendChild(errorElement);
  },
};

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  FormValidator.init();
});
