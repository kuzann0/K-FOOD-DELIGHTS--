// Order Confirmation Modal Handler
const OrderConfirmationHandler = {
  modal: null,
  socket: null,

  init() {
    // Wait for DOM before initializing
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this._initialize());
    } else {
      this._initialize();
    }
  },

  _initialize() {
    this.modal = document.getElementById("orderConfirmationModal");
    if (!this.modal) {
      console.error("Order confirmation modal not found");
      return;
    }

    this.attachEventListeners();
    this.initWebSocket();
  },

  attachEventListeners() {
    // Close button handler
    const closeBtn = this.modal.querySelector(".modal-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => this.close());
    }

    // Click outside modal to close
    this.modal.addEventListener("click", (e) => {
      if (e.target === this.modal) {
        this.close();
      }
    });

    // Confirmation button handler
    const confirmBtn = this.modal.querySelector(".btn-confirm");
    if (confirmBtn) {
      confirmBtn.addEventListener("click", () => this.processOrder());
    }

    // Prevent event bubbling from modal content
    this.modal
      .querySelector(".modal-content")
      ?.addEventListener("click", (e) => {
        e.stopPropagation();
      });
  },

  show(orderData) {
    if (!this.modal) {
      console.error("Modal not initialized");
      return;
    }

    try {
      this.updateModalContent(orderData);
      this.modal.style.display = "block";
      // Use requestAnimationFrame for smooth animation
      requestAnimationFrame(() => {
        this.modal.classList.add("show");
      });
    } catch (error) {
      console.error("Error showing modal:", error);
      notificationManager.show(
        "An error occurred while preparing your order. Please try again.",
        NotificationType.ERROR
      );
    }
  },

  close() {
    if (!this.modal) return;

    this.modal.classList.remove("show");
    setTimeout(() => {
      this.modal.style.display = "none";
    }, 300);
  },

  updateModalContent(data) {
    try {
      // Update order items
      const orderItemsContainer = document.getElementById("modalOrderItems");
      if (orderItemsContainer) {
        let itemsHtml = "";
        data.items.forEach((item) => {
          itemsHtml += `
            <div class="order-item">
              <div class="item-details">
                <span class="item-name">${item.name}</span>
                <span class="item-quantity">x${item.quantity}</span>
              </div>
              <span class="item-price">₱${(item.price * item.quantity).toFixed(
                2
              )}</span>
            </div>
          `;
        });
        orderItemsContainer.innerHTML = itemsHtml;
      }

      // Update delivery details
      const elements = {
        customerName: document.getElementById("modalCustomerName"),
        customerPhone: document.getElementById("modalCustomerPhone"),
        deliveryAddress: document.getElementById("modalDeliveryAddress"),
        deliveryInstructions: document.getElementById(
          "modalDeliveryInstructions"
        ),
      };

      // Safely update elements
      if (elements.customerName) {
        elements.customerName.textContent = data.customerInfo.fullName;
      }
      if (elements.customerPhone) {
        elements.customerPhone.textContent = data.customerInfo.phone;
      }
      if (elements.deliveryAddress) {
        elements.deliveryAddress.textContent = data.customerInfo.address;
      }
      if (elements.deliveryInstructions) {
        elements.deliveryInstructions.textContent =
          data.customerInfo.deliveryInstructions || "No special instructions";
      }

      // Update payment summary
      const paymentElements = {
        subtotal: document.getElementById("modalSubtotal"),
        deliveryFee: document.getElementById("modalDeliveryFee"),
        discounts: document.getElementById("modalDiscounts"),
        total: document.getElementById("modalTotalAmount"),
        discountRow: document.getElementById("modalDiscountRow"),
      };

      // Safely update payment elements
      if (paymentElements.subtotal) {
        paymentElements.subtotal.textContent = data.amounts.subtotal.toFixed(2);
      }
      if (paymentElements.deliveryFee) {
        paymentElements.deliveryFee.textContent =
          data.amounts.deliveryFee.toFixed(2);
      }
      if (paymentElements.discountRow && paymentElements.discounts) {
        if (data.amounts.totalDiscount > 0) {
          paymentElements.discounts.textContent =
            data.amounts.totalDiscount.toFixed(2);
          paymentElements.discountRow.style.display = "flex";
        } else {
          paymentElements.discountRow.style.display = "none";
        }
      }
      if (paymentElements.total) {
        paymentElements.total.textContent = data.amounts.total.toFixed(2);
      }
    } catch (error) {
      console.error("Error updating modal content:", error);
      throw error;
    }
  },

  async processOrder() {
    const confirmBtn = this.modal.querySelector(".btn-confirm");
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }

    try {
      const checkoutForm = document.getElementById("checkoutForm");
      if (!checkoutForm) {
        throw new Error("Checkout form not found");
      }

      const formData = new FormData(checkoutForm);
      const orderData = Object.fromEntries(formData.entries());

      // Add cart items
      orderData.items = cartManager.items;

      // Send order to server
      const response = await fetch("process_order.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
            ?.content,
        },
        body: JSON.stringify(orderData),
      });

      const data = await response.json();

      if (data.success) {
        notificationManager.show(data.message, NotificationType.SUCCESS);
        cartManager.clearCart();

        // Redirect to confirmation page
        if (data.routing && data.routing.redirectUrl) {
          setTimeout(() => {
            window.location.href = data.routing.redirectUrl;
          }, 1500);
        }
      } else {
        throw new Error(data.message || "Failed to process order");
      }
    } catch (error) {
      notificationManager.show(error.message, NotificationType.ERROR);

      // Re-enable button
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Place Order';
      }
    }
  },

  initWebSocket() {
    const wsProtocol = window.location.protocol === "https:" ? "wss:" : "ws:";
    const wsHost = window.location.hostname;
    const wsPort = "8080";
    const wsUrl = `${wsProtocol}//${wsHost}:${wsPort}/ws`;

    try {
      this.socket = new WebSocket(wsUrl);

      this.socket.onopen = () => {
        console.log("WebSocket connection established");
        // Register as customer
        this.socket.send(
          JSON.stringify({
            action: "authenticate",
            userType: "customer",
            userId: document.getElementById("userId").value,
          })
        );
      };

      this.socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        this.handleWebSocketMessage(data);
      };

      this.socket.onerror = (error) => {
        console.error("WebSocket error:", error);
      };

      this.socket.onclose = () => {
        console.log("WebSocket connection closed");
        // Try to reconnect after 5 seconds
        setTimeout(() => this.initWebSocket(), 5000);
      };
    } catch (error) {
      console.error("Failed to initialize WebSocket:", error);
    }
  },

  handleWebSocketMessage(data) {
    switch (data.type) {
      case "order_status":
        this.handleOrderStatusUpdate(data);
        break;
      case "order_confirmation":
        this.handleOrderConfirmation(data);
        break;
    }
  },

  handleOrderStatusUpdate(data) {
    notificationManager.show(
      `Order #${data.orderNumber} status: ${data.status}`,
      NotificationType.INFO
    );
  },

  handleOrderConfirmation(data) {
    notificationManager.show(
      `Order #${data.orderNumber} has been confirmed! Estimated delivery time: ${data.estimatedTime}`,
      NotificationType.SUCCESS
    );
  },
};

// Initialize handler
OrderConfirmationHandler.init();
