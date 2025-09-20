// Order Confirmation Modal Handler
class OrderConfirmation {
    constructor() {
        this.modal = null;
        this.socket = null;
        this.initialize();
    }

    initialize() {
        document.body.insertAdjacentHTML("beforeend", modalHtml);
        this.modal = document.getElementById('orderConfirmationModal');
        if (!this.modal) {
            console.error('Order confirmation modal not found');
            return;
        }

        // Set up event listeners
        this.attachEventListeners();
        
        // Initialize WebSocket if needed
      this.initializeWebSocket();
  }

  attachEventListeners() {
    // Close button
    this.modal
      .querySelector(".close-modal")
      .addEventListener("click", () => this.close());

    // Cancel and confirm buttons
    this.modal.querySelector(".modal-footer").addEventListener("click", (e) => {
      const action = e.target.getAttribute("data-action");
      if (action === "cancel") this.close();
      if (action === "confirm") this.processOrder();
    });

    // Click outside modal
    this.modal.addEventListener("click", (e) => {
      if (e.target === this.modal) this.close();
    });
  }

  show() {
    const cartItems = JSON.parse(
      document.getElementById("cart-data").value || "[]"
    );
    const amounts = recalculateAmounts(cartItems);

    // Update items list
    this.modal.querySelector("#modal-items-list").innerHTML = cartItems
      .map(
        (item) => `
            <div class="item-row">
                <span>${item.name} x ${item.quantity}</span>
                <span>₱${(item.price * item.quantity).toFixed(2)}</span>
            </div>
        `
      )
      .join("");

    // Update totals
    this.modal.querySelector(
      "#modal-subtotal"
    ).textContent = `₱${amounts.subtotal.toFixed(2)}`;
    this.modal.querySelector(
      "#modal-delivery-fee"
    ).textContent = `₱${amounts.deliveryFee.toFixed(2)}`;
    this.modal.querySelector(
      "#modal-discount"
    ).textContent = `₱${amounts.discount.toFixed(2)}`;
    this.modal.querySelector(
      "#modal-total"
    ).textContent = `₱${amounts.total.toFixed(2)}`;

    // Update delivery info
    const deliveryInfo = {
      name: document.getElementById("fullName").value,
      phone: document.getElementById("phone").value,
      address: document.getElementById("address").value,
      instructions: document.getElementById("deliveryInstructions").value,
    };

    this.modal.querySelector("#modal-delivery-info").innerHTML = `
            <div class="info-row"><strong>Name:</strong> ${
              deliveryInfo.name
            }</div>
            <div class="info-row"><strong>Phone:</strong> ${
              deliveryInfo.phone
            }</div>
            <div class="info-row"><strong>Address:</strong> ${
              deliveryInfo.address
            }</div>
            ${
              deliveryInfo.instructions
                ? `<div class="info-row"><strong>Instructions:</strong> ${deliveryInfo.instructions}</div>`
                : ""
            }
        `;

    // Update payment method
    const paymentMethod = document.querySelector(
      'input[name="paymentMethod"]:checked'
    );
    this.modal.querySelector("#modal-payment-info").innerHTML = `
            <div class="info-row"><strong>Payment Method:</strong> ${
              paymentMethod ? paymentMethod.value : "Not selected"
            }</div>
            ${
              paymentMethod && paymentMethod.value === "gcash"
                ? `
                <div class="info-row"><strong>GCash Number:</strong> ${
                  document.getElementById("gcashNumber").value
                }</div>
                <div class="info-row"><strong>Reference Number:</strong> ${
                  document.getElementById("gcashReference").value
                }</div>
            `
                : ""
            }
        `;

    this.modal.style.display = "block";
  }

  close() {
    this.modal.style.display = "none";
  }

  async processOrder() {
    const submitButton = this.modal.querySelector(".btn-primary");
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = "Processing...";

    try {
      const form = document.getElementById("checkout-form");
      const formData = new FormData(form);
      const cartItems = JSON.parse(
        document.getElementById("cart-data").value || "[]"
      );
      const amounts = recalculateAmounts(cartItems);

      // Prepare order data
      const orderData = {
        customer: {
          name: formData.get("fullName"),
          email: formData.get("email"),
          phone: formData.get("phone"),
          address: formData.get("address"),
        },
        order: {
          items: cartItems,
          amounts: amounts,
          payment: {
            method: formData.get("paymentMethod"),
            details:
              formData.get("paymentMethod") === "gcash"
                ? {
                    gcashNumber: formData.get("gcashNumber"),
                    referenceNumber: formData.get("gcashReference"),
                  }
                : null,
          },
          instructions: formData.get("deliveryInstructions") || "",
        },
      };

      // Submit to server
      const response = await fetch("process_order.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
            .content,
        },
        body: JSON.stringify(orderData),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || "Failed to process order");
      }

      // Notify crew dashboard via WebSocket handler
      if (window.wsHandler) {
        window.wsHandler.sendOrderUpdate(result.orderId, "new", {
          orderNumber: result.orderNumber,
          orderData: {
            ...orderData,
            orderNumber: result.orderNumber,
            status: "Pending",
          },
          timestamp: new Date().toISOString(),
        });
      }

      // Show success animation and notification
      const successAnimation = document.createElement("div");
      successAnimation.className = "order-success-animation";
      successAnimation.innerHTML = `
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="success-message">
          <h3>Order Placed Successfully!</h3>
          <p>Order #${result.orderNumber}</p>
        </div>
      `;
      document.body.appendChild(successAnimation);

      // Add success animation styles
      const style = document.createElement("style");
      style.textContent = `
        .order-success-animation {
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: white;
          padding: 30px;
          border-radius: 16px;
          box-shadow: 0 8px 32px rgba(0,0,0,0.1);
          text-align: center;
          animation: slideIn 0.5s ease, fadeOut 0.5s ease 2s forwards;
          z-index: 2000;
        }
        .success-icon {
          font-size: 48px;
          color: #4CAF50;
          margin-bottom: 20px;
          animation: scaleIn 0.5s ease;
        }
        .success-message h3 {
          color: #333;
          margin: 0 0 10px 0;
          font-family: 'Poppins', sans-serif;
        }
        .success-message p {
          color: #666;
          margin: 0;
          font-size: 1.1em;
        }
        @keyframes slideIn {
          from { transform: translate(-50%, -40%); opacity: 0; }
          to { transform: translate(-50%, -50%); opacity: 1; }
        }
        @keyframes fadeOut {
          to { opacity: 0; }
        }
        @keyframes scaleIn {
          from { transform: scale(0); }
          to { transform: scale(1); }
        }
      `;
      document.head.appendChild(style);

      // Show success notification
      showNotification(
        "success",
        `Order #${result.orderNumber} placed successfully!`
      );

      // Redirect after animation
      setTimeout(() => {
        window.location.href = `order_confirmation.php?order_id=${result.orderId}`;
      }, 2500);
    } catch (error) {
      console.error("Order processing error:", error);
      showNotification(
        "error",
        error.message || "Failed to process your order. Please try again."
      );
      submitButton.disabled = false;
      submitButton.textContent = originalText;
    }
  }
}

class OrderHandler {
  static init() {
    // Wait for DOM to be fully loaded
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => OrderHandler.setup());
    } else {
      OrderHandler.setup();
    }
  }

  static setup() {
    try {
      // Initialize confirmation modal
      window.orderConfirmation = new OrderConfirmation();

      // Find checkout form
      const checkoutForm = document.getElementById("checkoutForm");
      if (!checkoutForm) {
        console.warn(
          "Checkout form not found. Order confirmation system not initialized."
        );
        return;
      }

      // Initialize validator and attach submit handler
      const validator = new OrderValidator(checkoutForm);
      checkoutForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        // Remove any existing error messages
        document
          .querySelectorAll(".validation-error")
          .forEach((el) => el.remove());

        try {
          // Validate customer information
          const customerValidation = validator.validateCustomerInfo();
          if (customerValidation.errors.length > 0) {
            throw new OrderValidationError(
              customerValidation.errors.join("\n")
            );
          }

          // Validate cart amounts
          const amountValidation = await validator.validateAmounts();
          if (!amountValidation.isValid) {
            throw new OrderValidationError(amountValidation.errors.join("\n"));
          }

          // Validate payment method
          const selectedPayment = document.querySelector(
            ".payment-method.selected"
          );
          if (!selectedPayment) {
            throw new OrderValidationError("Please select a payment method");
          }

          // Additional GCash validation if selected
          if (selectedPayment.dataset.method === "gcash") {
            const gcashValidation = validator.validateGcashReference();
            if (!gcashValidation.isValid) {
              throw new OrderValidationError(gcashValidation.errorMessage);
            }
          }

          // If all validations pass, show confirmation modal
          window.orderConfirmation.show();
        } catch (error) {
          if (error instanceof OrderValidationError) {
            // Create styled error container
            const errorContainer = document.createElement("div");
            errorContainer.className = "validation-error";
            errorContainer.style.cssText = `
              color: #ff6666;
              background-color: #fff5f5;
              padding: 15px;
              border-radius: 8px;
              margin-bottom: 20px;
              border: 1px solid #ffe5e5;
              font-family: 'Poppins', sans-serif;
            `;

            const errorMessage = document.createElement("div");
            errorMessage.textContent = error.message;
            errorContainer.appendChild(errorMessage);

            // Insert at the top of the form
            checkoutForm.insertBefore(errorContainer, checkoutForm.firstChild);

            // Scroll error into view
            errorContainer.scrollIntoView({
              behavior: "smooth",
              block: "center",
            });
          } else {
            console.error("Unexpected error:", error);
            showNotification(
              "error",
              "An unexpected error occurred. Please try again."
            );
          }
        }
      });

      console.log("Order confirmation system initialized successfully");
    } catch (error) {
      console.error("Error initializing order confirmation system:", error);
    }
  }
}

// Initialize the order handler
OrderHandler.init();
