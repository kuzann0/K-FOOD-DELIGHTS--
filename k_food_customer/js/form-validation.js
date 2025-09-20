// Form validation rules and handlers for KfoodDelights checkout
const FormValidation = {
  // Validation rules for each field
  rules: {
    landmark: {
      required: true,
      minLength: 5,
      message: "Please provide a clear landmark for delivery",
    },
    instructions: {
      required: false,
      maxLength: 500,
      message: "Delivery instructions are too long",
    },
    paymentMethod: {
      required: true,
      message: "Please select a payment method",
    },
    address: {
      required: true,
      minLength: 10,
      message: "Please provide a complete delivery address",
    },
    phone: {
      required: true,
      pattern: /^\+?[0-9]{10,}$/,
      message: "Please enter a valid phone number",
    },
    email: {
      required: true,
      pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
      message: "Please enter a valid email address",
    },
    fullName: {
      required: true,
      minLength: 3,
      message: "Please enter your full name",
    },
  },

  // Initialize form validation
  init(formId) {
    const form = document.getElementById(formId);
    if (!form) {
      console.error(`Form with id '${formId}' not found`);
      return;
    }

    // Attach validation handlers to form fields
    Object.keys(this.rules).forEach((fieldName) => {
      const field = form.querySelector(`[name="${fieldName}"]`);
      if (field) {
        field.addEventListener("blur", () => this.validateField(field));
        field.addEventListener("input", () => this.clearError(field));
      }
    });

    // Handle form submission
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      this.validateAndSubmit(form);
    });
  },

  // Validate and submit form
  async validateAndSubmit(form) {
    try {
      // Disable form submission while processing
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> Processing...';
      }

      // Clear previous errors
      this.clearAllErrors(form);

      // Validate all fields
      const validationResult = this.validateForm(form);
      if (!validationResult.isValid) {
        this.showFormError(validationResult.errors.join("<br>"));
        return;
      }

      // Collect form data
      const orderData = await this.collectOrderData(form);

      // Show order confirmation modal
      await CheckoutHandlers.showOrderConfirmation(orderData);
    } catch (error) {
      console.error("Form submission error:", error);
      this.showFormError(
        error.message || "An error occurred while processing your order"
      );
    } finally {
      // Re-enable submit button
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = "Place Order";
      }
    }
  },

  // Validate entire form
  validateForm(form) {
    const errors = [];
    let isValid = true;

    // Standard field validation
    Object.keys(this.rules).forEach((fieldName) => {
      const field = form.querySelector(`[name="${fieldName}"]`);
      if (field && !this.validateField(field)) {
        isValid = false;
        errors.push(this.rules[fieldName].message);
      }
    });

    // Validate cart items
    const cartItems = JSON.parse(localStorage.getItem("cart") || "[]");
    if (cartItems.length === 0) {
      isValid = false;
      errors.push("Your cart is empty");
    }

    // Payment method specific validation
    const paymentMethod = form.querySelector(
      'input[name="paymentMethod"]:checked'
    );
    if (paymentMethod) {
      if (paymentMethod.value === "gcash") {
        const gcashNumber = form.querySelector("#gcashNumber");
        const gcashReference = form.querySelector("#gcashReference");

        if (!gcashNumber?.value || !/^\d{11}$/.test(gcashNumber.value)) {
          isValid = false;
          errors.push("Invalid GCash number");
        }
        if (
          !gcashReference?.value ||
          !/^[A-Za-z0-9]{6,}$/.test(gcashReference.value)
        ) {
          isValid = false;
          errors.push("Invalid reference number");
        }
      }
    }

    return { isValid, errors };
  },

  // Collect form data
  async collectOrderData(form) {
    const formData = new FormData(form);
    const cartItems = JSON.parse(localStorage.getItem("cart") || "[]");

    return {
      customerInfo: {
        name: formData.get("fullName"),
        email: formData.get("email"),
        phone: formData.get("phone"),
        address: formData.get("address"),
        landmark: formData.get("landmark"),
        instructions: formData.get("instructions") || "",
      },
      items: cartItems,
      payment: {
        method: formData.get("paymentMethod"),
        details: this.getPaymentDetails(formData),
      },
    };
  },

  // Get payment method specific details
  getPaymentDetails(formData) {
    const method = formData.get("paymentMethod");
    switch (method) {
      case "gcash":
        return {
          number: formData.get("gcashNumber"),
          reference: formData.get("gcashReference"),
        };
      case "cash":
        return {
          changeFor: formData.get("cashAmount") || 0,
        };
      default:
        return {};
    }
  },

  // Validate a single field
  validateField(field) {
    const rule = this.rules[field.name];
    if (!rule) return true;

    const value = field.value.trim();
    let isValid = true;
    let errorMessage = "";

    // Required field validation
    if (rule.required && !value) {
      isValid = false;
      errorMessage = rule.message;
    }

    // Length validation
    if (isValid && rule.minLength && value.length < rule.minLength) {
      isValid = false;
      errorMessage = `Must be at least ${rule.minLength} characters`;
    }

    if (isValid && rule.maxLength && value.length > rule.maxLength) {
      isValid = false;
      errorMessage = `Must be less than ${rule.maxLength} characters`;
    }

    // Pattern validation
    if (isValid && rule.pattern && !rule.pattern.test(value)) {
      isValid = false;
      errorMessage = rule.message;
    }

    this.setFieldStatus(field, isValid, errorMessage);
    return isValid;
  },

  // Clear all form errors
  clearAllErrors(form) {
    const errorElements = form.querySelectorAll(".error-message, .is-invalid");
    errorElements.forEach((element) => {
      if (element.classList.contains("error-message")) {
        element.style.display = "none";
        element.textContent = "";
      } else {
        element.classList.remove("is-invalid");
      }
    });

    // Clear form-level error container
    const formError = document.getElementById("form-error");
    if (formError) {
      formError.style.display = "none";
      formError.textContent = "";
    }
  },

  // Clear error state from a field
  clearError(field) {
    this.setFieldStatus(field, true, "");
  },

  // Set field validation status
  setFieldStatus(field, isValid, message) {
    const container = field.closest(".form-group");
    if (!container) return;

    const errorElement =
      container.querySelector(".error-message") ||
      this.createErrorElement(container);

    field.classList.toggle("is-invalid", !isValid);
    field.classList.toggle("is-valid", isValid && field.value.trim() !== "");

    errorElement.style.display = isValid ? "none" : "block";
    errorElement.textContent = message;
  },

  // Create error message element
  createErrorElement(container) {
    const errorElement = document.createElement("div");
    errorElement.className = "error-message text-danger mt-1";
    container.appendChild(errorElement);
    return errorElement;
  },

  // Show form error message with animation
  showFormError(message) {
    const errorContainer = document.getElementById("form-error");
    if (errorContainer) {
      // Apply fade in animation
      errorContainer.style.opacity = "0";
      errorContainer.style.display = "block";
      errorContainer.textContent = message;

      requestAnimationFrame(() => {
        errorContainer.style.transition = "opacity 0.3s ease-in";
        errorContainer.style.opacity = "1";
      });

      // Smooth scroll to error message
      errorContainer.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    } else {
      // Fallback to notification if error container doesn't exist
      notificationManager.show(message, "error", {
        duration: 5000,
        position: "top-center",
        animation: true,
      });
    }
  },
};

// Initialize form validation on page load
document.addEventListener("DOMContentLoaded", () => {
  FormValidation.init("checkout-form");
});
