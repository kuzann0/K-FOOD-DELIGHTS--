/**
 * CheckoutFormValidator - Handles form validation and order data preparation
 */
class CheckoutFormValidator {
  constructor() {
    this.form = document.getElementById("checkoutForm");
    this.customerNameInput = document.getElementById("customerName");
    this.phoneInput = document.getElementById("phone");
    this.addressInput = document.getElementById("address");
    this.emailInput = document.getElementById("email");
    this.paymentInputs = document.querySelectorAll(
      'input[name="paymentMethod"]'
    );
    this.gcashRefInput = document.getElementById("gcashReference");

    this.errorDisplays = {
      customerName: document.getElementById("customerNameError"),
      phone: document.getElementById("phoneError"),
      address: document.getElementById("addressError"),
      email: document.getElementById("emailError"),
      payment: document.getElementById("paymentError"),
      gcashRef: document.getElementById("gcashReferenceError"),
    };

    this.bindEvents();
  }

  bindEvents() {
    if (this.form) {
      this.form.addEventListener("submit", (e) => this.validateForm(e));
    }

    // Add real-time validation for customer info fields
    ["customerName", "phone", "address"].forEach((field) => {
      const input = document.getElementById(field);
      if (input) {
        input.addEventListener("blur", () => {
          const isValid = this.validateField(field);
          if (!isValid) {
            input.classList.add("invalid");
          } else {
            input.classList.remove("invalid");
          }
        });
        input.addEventListener("input", () => {
          this.clearError(field);
          input.classList.remove("invalid");
        });
      }
    });
  }

  validateForm(e) {
    e.preventDefault();

    const isValid = this.validateAllFields();
    if (!isValid) {
      return false;
    }

    try {
      const orderData = this.prepareOrderData();
      this.submitOrder(orderData);
    } catch (error) {
      console.error("Error preparing order data:", error);
      notificationManager.show(
        "Error preparing order: " + error.message,
        NotificationType.ERROR
      );
    }
  }

  validateField(field) {
    switch (field) {
      case "customerName":
        const name = this.customerNameInput?.value.trim();
        if (!name) {
          this.showError(field, "Customer name is required");
          return false;
        }
        if (name.length < 2 || name.length > 50) {
          this.showError(field, "Name must be between 2 and 50 characters");
          return false;
        }
        if (!/^[a-zA-Z\s]+$/.test(name)) {
          this.showError(field, "Name can only contain letters and spaces");
          return false;
        }
        break;

      case "phone":
        const phone = this.phoneInput?.value.trim();
        if (!phone) {
          this.showError(field, "Phone number is required");
          return false;
        }
        if (!/^[0-9]{11}$/.test(phone)) {
          this.showError(field, "Please enter a valid 11-digit phone number");
          return false;
        }
        break;

      case "address":
        const address = this.addressInput?.value.trim();
        if (!address) {
          this.showError(field, "Delivery address is required");
          return false;
        }
        if (address.length < 10) {
          this.showError(field, "Please enter a complete delivery address");
          return false;
        }
        break;

      case "email":
        const email = this.emailInput?.value.trim();
        if (!email) {
          this.showError(field, "Email address is required");
          return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          this.showError(field, "Please enter a valid email address");
          return false;
        }
        break;
    }

    this.clearError(field);
    return true;
  }

  validateAllFields() {
    let isValid = true;
    ["customerName", "phone", "address", "email"].forEach((field) => {
      if (!this.validateField(field)) {
        isValid = false;
      }
    });

    // Validate payment method
    const selectedPayment = Array.from(this.paymentInputs).find(
      (input) => input.checked
    );
    if (!selectedPayment) {
      this.showError("payment", "Please select a payment method");
      isValid = false;
    } else if (selectedPayment.value === "gcash") {
      if (!this.gcashRefInput?.value.trim()) {
        this.showError("gcashRef", "GCash reference number is required");
        isValid = false;
      }
    }

    return isValid;
  }

  prepareOrderData() {
    if (!this.form) {
      throw new Error("Checkout form not found");
    }

    // Validate cart data
    const cartData = document.getElementById("cart-data")?.value;
    if (!cartData) {
      throw new Error("No items in cart");
    }

    const items = JSON.parse(cartData);
    if (!Array.isArray(items) || items.length === 0) {
      throw new Error("Cart is empty");
    }

    // Get cart totals
    const amounts = window.cartManager?.getOrderTotals();
    if (!amounts || typeof amounts.total !== "number") {
      throw new Error("Invalid order amounts");
    }

    // Prepare order data structure
    const paymentMethod = document.querySelector(
      'input[name="paymentMethod"]:checked'
    );
    if (!paymentMethod) {
      throw new Error("Payment method is required");
    }

    const orderData = {
      customerInfo: {
        name: this.customerNameInput.value.trim(),
        email: this.emailInput.value.trim(),
        phone: this.phoneInput.value.trim(),
        address: this.addressInput.value.trim(),
        instructions:
          document.getElementById("deliveryInstructions")?.value.trim() || "",
      },
      items: items.map((item) => ({
        product_id: item.product_id,
        name: item.name,
        price: parseFloat(item.price),
        quantity: parseInt(item.quantity),
      })),
      amounts: {
        subtotal: amounts.subtotal,
        total: amounts.total,
        discount: amounts.discount || 0,
      },
      payment: {
        method: paymentMethod.value,
      },
      timestamp: new Date().toISOString(),
    };

    // Add GCash reference if payment method is GCash
    if (paymentMethod.value === "gcash") {
      const gcashRef = this.gcashRefInput?.value.trim();
      if (!gcashRef) {
        throw new Error("GCash reference number is required");
      }
      orderData.payment.gcashReference = gcashRef;
    }

    return orderData;
  }

  async submitOrder(orderData) {
    if (!window.orderConfirmationHandler) {
      throw new Error("Order confirmation handler not initialized");
    }

    const result = window.orderConfirmationHandler.showConfirmation(orderData);
    if (!result) {
      throw new Error("Order validation failed");
    }
  }

  showError(field, message) {
    const errorElement = this.errorDisplays[field];
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = "block";

      // Add error class to input
      const input = document.getElementById(field);
      if (input) {
        input.classList.add("invalid");
      }
    }
  }

  clearError(field) {
    const errorElement = this.errorDisplays[field];
    if (errorElement) {
      errorElement.textContent = "";
      errorElement.style.display = "none";

      // Remove error class from input
      const input = document.getElementById(field);
      if (input) {
        input.classList.remove("invalid");
      }
    }
  }
}

// Initialize validation when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  window.checkoutFormValidator = new CheckoutFormValidator();
});
