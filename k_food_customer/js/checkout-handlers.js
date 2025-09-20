// Checkout handlers for KfoodDelights order processing
const CheckoutHandlers = {
  // WebSocket instance
  ws: null,
  // Order confirmation modal
  modal: null,
  // Message queue for WebSocket
  messageQueue: [],
  // Connection state
  isConnected: false,

  // Initialize handlers
  init() {
    this.initializeWebSocket();
    this.initializeModal();
    this.attachEventListeners();
  },

  // Initialize WebSocket connection
  initializeWebSocket() {
    const config = WebSocketConfig.getConfig();
    this.ws = new WebSocket(config.url);

    this.ws.onopen = () => {
      console.log("WebSocket connected");
      this.isConnected = true;
      this.processMessageQueue();
    };

    this.ws.onclose = () => {
      console.log("WebSocket disconnected");
      this.isConnected = false;
      setTimeout(() => this.initializeWebSocket(), 5000); // Reconnect after 5s
    };

    this.ws.onerror = (error) => {
      console.error("WebSocket error:", error);
      notificationManager.show("Connection error. Retrying...", "error");
    };

    this.ws.onmessage = (event) => this.handleWebSocketMessage(event);
  },

  // Initialize order confirmation modal
  initializeModal() {
    this.modal = document.getElementById("orderConfirmationModal");
    if (!this.modal) {
      console.error("Order confirmation modal not found");
      return;
    }

    // Add modal event listeners
    const closeButtons = this.modal.querySelectorAll(
      ".modal-close, .btn-cancel"
    );
    closeButtons.forEach((button) => {
      button.addEventListener("click", () => this.hideModal());
    });

    const confirmButton = this.modal.querySelector(".btn-confirm");
    if (confirmButton) {
      confirmButton.addEventListener("click", () => this.processOrder());
    }
  },

  // Attach event listeners
  attachEventListeners() {
    const placeOrderButton = document.getElementById("placeOrderButton");
    if (placeOrderButton) {
      placeOrderButton.addEventListener("click", (e) =>
        this.handlePlaceOrder(e)
      );
    }
  },

  // Handle place order button click
  async handlePlaceOrder(e) {
    e.preventDefault();

    try {
      // Validate form
      const form = document.getElementById("checkoutForm");
      if (!form) throw new Error("Checkout form not found");

      const formData = new FormData(form);
      const orderData = await this.prepareOrderData(formData);

      // Show confirmation modal
      this.showOrderConfirmation(orderData);
    } catch (error) {
      console.error("Order preparation error:", error);
      notificationManager.show(error.message, "error");
    }
  },

  // Prepare order data from form
  async prepareOrderData(formData) {
    const data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });

    // Get cart items
    const cartItems = JSON.parse(localStorage.getItem("cart") || "[]");
    if (!cartItems.length) {
      throw new Error("Your cart is empty");
    }

    return {
      customerInfo: {
        name: data.name,
        email: data.email,
        phone: data.phone,
        address: data.address,
        landmark: data.landmark,
        instructions: data.instructions || "",
      },
      payment: {
        method: data.paymentMethod,
        details: this.getPaymentDetails(data),
      },
      items: cartItems,
      amounts: this.calculateAmounts(cartItems),
    };
  },

  // Get payment method specific details
  getPaymentDetails(data) {
    switch (data.paymentMethod) {
      case "gcash":
        return {
          number: data.gcashNumber,
          reference: data.gcashReference,
        };
      case "cash":
        return {
          changeFor: data.cashAmount || 0,
        };
      default:
        return {};
    }
  },

  // Calculate order amounts
  calculateAmounts(items) {
    const subtotal = items.reduce(
      (sum, item) => sum + item.price * item.quantity,
      0
    );
    const deliveryFee = 50; // Fixed delivery fee
    const total = subtotal + deliveryFee;

    return {
      subtotal,
      deliveryFee,
      total,
    };
  },

  // Show order confirmation modal
  showOrderConfirmation(orderData) {
    if (!this.modal) return;

    // Update modal content
    this.updateModalContent(orderData);

    // Show modal
    this.modal.style.display = "block";
    setTimeout(() => {
      this.modal.classList.add("show");
    }, 50);
  },

  // Update modal content
  updateModalContent(data) {
    try {
      // Update order items
      const itemsContainer = this.modal.querySelector(".order-items");
      if (itemsContainer) {
        itemsContainer.innerHTML = this.generateItemsHtml(data.items);
      }

      // Update customer info
      const customerInfo = this.modal.querySelector(".customer-info");
      if (customerInfo) {
        customerInfo.innerHTML = this.generateCustomerInfoHtml(
          data.customerInfo
        );
      }

      // Update payment info
      const paymentInfo = this.modal.querySelector(".payment-info");
      if (paymentInfo) {
        paymentInfo.innerHTML = this.generatePaymentInfoHtml(
          data.payment,
          data.amounts
        );
      }
    } catch (error) {
      console.error("Error updating modal content:", error);
      throw new Error("Could not display order confirmation");
    }
  },

  // Process confirmed order
  async processOrder() {
    try {
      const orderData = this.getModalOrderData();
      const response = await this.submitOrder(orderData);

      if (response.success) {
        // Clear cart
        localStorage.removeItem("cart");

        // Emit WebSocket event
        this.emitOrderPlaced(response.orderId, orderData);

        // Show success and redirect
        this.handleOrderSuccess(response);
      } else {
        throw new Error(response.message || "Order processing failed");
      }
    } catch (error) {
      console.error("Order processing error:", error);
      notificationManager.show(error.message, "error");
    }
  },

  // Submit order to server
  async submitOrder(orderData) {
    const response = await fetch("process_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify(orderData),
    });

    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`);
    }

    return await response.json();
  },

  // Emit orderPlaced event via WebSocket
  emitOrderPlaced(orderId, orderData) {
    const message = {
      type: "orderPlaced",
      data: {
        orderId,
        timestamp: new Date().toISOString(),
        ...orderData,
      },
    };

    this.sendWebSocketMessage(message);
  },

  // Send WebSocket message with queue support
  sendWebSocketMessage(message) {
    if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(message));
    } else {
      this.messageQueue.push(message);
    }
  },

  // Process queued messages
  processMessageQueue() {
    while (this.messageQueue.length > 0 && this.isConnected) {
      const message = this.messageQueue.shift();
      this.sendWebSocketMessage(message);
    }
  },

  // Handle WebSocket messages
  handleWebSocketMessage(event) {
    try {
      const message = JSON.parse(event.data);
      switch (message.type) {
        case "orderConfirmed":
          this.handleOrderConfirmed(message.data);
          break;
        case "error":
          this.handleError(message.data);
          break;
        // Add other message type handlers as needed
      }
    } catch (error) {
      console.error("Error processing WebSocket message:", error);
    }
  },

  // Handle successful order
  handleOrderSuccess(response) {
    notificationManager.show(
      "Order placed successfully! Redirecting to confirmation page...",
      "success"
    );

    // Redirect to confirmation page
    setTimeout(() => {
      window.location.href = `order-confirmation.php?id=${response.orderId}`;
    }, 2000);
  },

  // Hide modal
  hideModal() {
    if (!this.modal) return;

    this.modal.classList.remove("show");
    setTimeout(() => {
      this.modal.style.display = "none";
    }, 300);
  },

  // Generate HTML helpers
  generateItemsHtml(items) {
    return items
      .map(
        (item) => `
            <div class="order-item">
                <div class="item-details">
                    <span class="item-name">${item.name}</span>
                    <span class="item-quantity">x${item.quantity}</span>
                </div>
                <span class="item-price">₱${(
                  item.price * item.quantity
                ).toFixed(2)}</span>
            </div>
        `
      )
      .join("");
  },

  generateCustomerInfoHtml(info) {
    return `
            <div class="customer-details">
                <p><strong>Name:</strong> ${info.name}</p>
                <p><strong>Phone:</strong> ${info.phone}</p>
                <p><strong>Address:</strong> ${info.address}</p>
                <p><strong>Landmark:</strong> ${info.landmark}</p>
                ${
                  info.instructions
                    ? `<p><strong>Instructions:</strong> ${info.instructions}</p>`
                    : ""
                }
            </div>
        `;
  },

  generatePaymentInfoHtml(payment, amounts) {
    return `
            <div class="payment-details">
                <p><strong>Payment Method:</strong> ${payment.method.toUpperCase()}</p>
                <div class="amount-breakdown">
                    <p>Subtotal: ₱${amounts.subtotal.toFixed(2)}</p>
                    <p>Delivery Fee: ₱${amounts.deliveryFee.toFixed(2)}</p>
                    <p class="total"><strong>Total:</strong> ₱${amounts.total.toFixed(
                      2
                    )}</p>
                </div>
            </div>
        `;
  },
};

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  CheckoutHandlers.init();
});
