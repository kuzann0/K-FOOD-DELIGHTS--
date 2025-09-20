class OrderHandler {
  constructor() {
    this.form = document.getElementById("checkoutForm");
    this.submitBtn = document.getElementById("placeOrderBtn");
    this.modal = document.getElementById("orderConfirmationModal");
    this.modalContent = this.modal?.querySelector(".modal-content");
    this.wsHandler = null;

    this.init();
  }

  init() {
    if (!this.form || !this.submitBtn) {
      console.error("Required elements not found");
      return;
    }

    this.initializeWebSocket();
    this.setupEventListeners();
  }

  initializeWebSocket() {
    const wsUrl = "ws://127.0.0.1:8080/ws";

    this.wsHandler = new WebSocketHandler(wsUrl, {
      reconnectAttempts: 3,
      reconnectInterval: 3000,
      onMessage: (data) => this.handleWebSocketMessage(data),
      onError: (error) => this.handleWebSocketError(error),
    });
  }

  setupEventListeners() {
    this.form.addEventListener("submit", (e) => this.handleSubmit(e));

    // Real-time validation
    this.form.querySelectorAll("input, textarea").forEach((input) => {
      input.addEventListener("input", () => this.validateField(input));
      input.addEventListener("blur", () => this.validateField(input));
    });
  }

  validateField(input) {
    const value = input.value.trim();
    const name = input.name;
    let isValid = true;
    let message = "";

    switch (name) {
      case "phone":
        isValid = /^(\+63|0)[0-9]{10}$/.test(value);
        message = "Please enter a valid Philippine phone number";
        break;
      case "email":
        isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        message = "Please enter a valid email address";
        break;
      case "address":
        isValid = value.length >= 10;
        message = "Please enter a complete delivery address";
        break;
      case "deliveryInstructions":
        // Optional field, but if filled should be meaningful
        if (value.length > 0 && value.length < 10) {
          isValid = false;
          message = "Instructions should be at least 10 characters";
        }
        break;
      default:
        isValid = value.length > 0;
        message = "This field is required";
    }

    this.updateFieldStatus(input, isValid, message);
    return isValid;
  }

  updateFieldStatus(input, isValid, message) {
    const container = input.closest(".form-group");
    const errorElement =
      container?.querySelector(".error-message") ||
      this.createErrorElement(container);

    if (!isValid) {
      input.classList.add("invalid");
      errorElement.textContent = message;
      errorElement.style.display = "block";
    } else {
      input.classList.remove("invalid");
      errorElement.style.display = "none";
    }
  }

  createErrorElement(container) {
    const error = document.createElement("div");
    error.className = "error-message";
    container.appendChild(error);
    return error;
  }

  validateForm() {
    let isValid = true;
    const requiredFields = this.form.querySelectorAll(
      "input[required], textarea[required]"
    );

    requiredFields.forEach((field) => {
      if (!this.validateField(field)) {
        isValid = false;
      }
    });

    return isValid;
  }

  async handleSubmit(e) {
    e.preventDefault();

    if (!this.validateForm()) {
      window.notifications.error(
        "Please fill in all required fields correctly"
      );
      return;
    }

    try {
      const orderData = this.collectFormData();
      await this.showConfirmationModal(orderData);
    } catch (error) {
      console.error("Error preparing order:", error);
      window.notifications.error(
        "Error preparing your order. Please try again."
      );
    }
  }

  collectFormData() {
    const formData = new FormData(this.form);
    const orderData = {
      customerInfo: {
        name: formData.get("fullName"),
        email: formData.get("email"),
        phone: formData.get("phone"),
        address: formData.get("address"),
        deliveryInstructions: formData.get("deliveryInstructions") || "",
      },
      payment: {
        method: formData.get("paymentMethod"),
        details: {},
      },
      items: this.getCartItems(),
      amounts: this.getOrderAmounts(),
      discounts: this.getDiscounts(),
    };

    // Add payment specific details
    if (orderData.payment.method === "gcash") {
      orderData.payment.details.gcashNumber = formData.get("gcashNumber");
    }

    return orderData;
  }

  getCartItems() {
    // Implementation depends on how cart items are stored
    return JSON.parse(sessionStorage.getItem("cartItems") || "[]");
  }

  getOrderAmounts() {
    return {
      subtotal: parseFloat(
        document.getElementById("subtotal")?.dataset.value || 0
      ),
      discount: parseFloat(
        document.getElementById("discount")?.dataset.value || 0
      ),
      total: parseFloat(document.getElementById("total")?.dataset.value || 0),
    };
  }

  getDiscounts() {
    return {
      seniorId: document.getElementById("seniorId")?.value,
      pwdId: document.getElementById("pwdId")?.value,
      promoCode: document.getElementById("promoCode")?.value,
    };
  }

  async showConfirmationModal(orderData) {
    if (!this.modal || !this.modalContent) {
      throw new Error("Modal elements not found");
    }

    const summary = this.generateOrderSummary(orderData);
    this.modalContent.innerHTML = summary;

    this.modal.style.display = "block";

    // Set up confirmation button
    const confirmBtn = this.modalContent.querySelector("#confirmOrderBtn");
    if (confirmBtn) {
      confirmBtn.addEventListener("click", () => this.processOrder(orderData));
    }

    // Set up cancel button
    const cancelBtn = this.modalContent.querySelector("#cancelOrderBtn");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", () => this.hideModal());
    }
  }

  generateOrderSummary(orderData) {
    return `
            <h2>Confirm Your Order</h2>
            <div class="order-summary">
                <div class="summary-section">
                    <h3>Delivery Details</h3>
                    <p><strong>Name:</strong> ${this.escapeHtml(
                      orderData.customerInfo.name
                    )}</p>
                    <p><strong>Address:</strong> ${this.escapeHtml(
                      orderData.customerInfo.address
                    )}</p>
                    <p><strong>Phone:</strong> ${this.escapeHtml(
                      orderData.customerInfo.phone
                    )}</p>
                    ${
                      orderData.customerInfo.deliveryInstructions
                        ? `<p><strong>Instructions:</strong> ${this.escapeHtml(
                            orderData.customerInfo.deliveryInstructions
                          )}</p>`
                        : ""
                    }
                </div>
                
                <div class="summary-section">
                    <h3>Order Items</h3>
                    <ul class="order-items">
                        ${orderData.items
                          .map(
                            (item) => `
                            <li>
                                ${this.escapeHtml(item.name)} x ${item.quantity}
                                <span class="item-price">₱${(
                                  item.price * item.quantity
                                ).toFixed(2)}</span>
                            </li>
                        `
                          )
                          .join("")}
                    </ul>
                </div>

                <div class="summary-section">
                    <h3>Payment Details</h3>
                    <p><strong>Method:</strong> ${this.escapeHtml(
                      orderData.payment.method.toUpperCase()
                    )}</p>
                    <p><strong>Subtotal:</strong> ₱${orderData.amounts.subtotal.toFixed(
                      2
                    )}</p>
                    ${
                      orderData.amounts.discount > 0
                        ? `<p><strong>Discount:</strong> ₱${orderData.amounts.discount.toFixed(
                            2
                          )}</p>`
                        : ""
                    }
                    <p class="total"><strong>Total:</strong> ₱${orderData.amounts.total.toFixed(
                      2
                    )}</p>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" id="confirmOrderBtn" class="btn btn-primary">
                    Confirm Order
                </button>
                <button type="button" id="cancelOrderBtn" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        `;
  }

  escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  hideModal() {
    if (this.modal) {
      this.modal.style.display = "none";
    }
  }

  async processOrder(orderData) {
    try {
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

      // Send order to WebSocket server for real-time crew notification
      if (this.wsHandler) {
        this.wsHandler.send({
          type: "new_order",
          order: result.order,
        });
      }

      // Show success message and redirect
      window.notifications.success("Order placed successfully!");
      setTimeout(() => {
        window.location.href = `order_confirmation.php?order_id=${result.orderId}`;
      }, 1500);
    } catch (error) {
      console.error("Order processing error:", error);
      window.notifications.error(
        error.message || "Failed to process your order. Please try again."
      );
    } finally {
      this.hideModal();
    }
  }

  handleWebSocketMessage(data) {
    // Handle any WebSocket messages if needed
    console.log("WebSocket message received:", data);
  }

  handleWebSocketError(error) {
    console.error("WebSocket error:", error);
    // Continue with order processing even if WebSocket fails
  }
}

// Initialize the order handler when the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  window.orderHandler = new OrderHandler();
});
