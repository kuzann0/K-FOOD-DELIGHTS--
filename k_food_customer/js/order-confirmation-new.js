/**
 * OrderConfirmationHandler - Manages order confirmation UI and AJAX communication
 * Requires: ui-utilities.js, notification-system.js
 */
class OrderConfirmationHandler {
  constructor() {
    this.currentOrder = null;
    this.isProcessing = false;
    this.loadingOverlay = null;
    this.statusCheckInterval = null;
    this.endpoints = {
      submitOrder: "/process_order.php",
      checkStatus: "/api/check_order_status.php",
      updateOrder: "/api/update_order.php",
    };
  }

  static init() {
    // Create singleton instance
    if (!OrderConfirmationHandler.instance) {
      OrderConfirmationHandler.instance = new OrderConfirmationHandler();
    }
    return OrderConfirmationHandler.instance;
  }

  async submitOrder(orderData) {
    if (this.isProcessing) {
      throw new Error("Order is already being processed");
    }

    this.isProcessing = true;
    this.showLoadingOverlay("Processing your order...");

    try {
      const response = await fetch(this.endpoints.submitOrder, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
            ?.content,
        },
        body: JSON.stringify(orderData),
        credentials: "same-origin",
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        this.currentOrder = {
          orderId: result.orderId,
          orderNumber: result.orderNumber,
          status: result.status,
        };

        this.startStatusChecking();
        this.showConfirmation(result);
      } else {
        throw new Error(result.message || "Failed to process order");
      }
    } catch (error) {
      console.error("Order submission error:", error);
      this.showError("Failed to submit order. Please try again.");
    } finally {
      this.isProcessing = false;
      this.hideLoadingOverlay();
    }
  }

  startStatusChecking() {
    if (!this.currentOrder?.orderId) return;

    // Clear any existing interval
    if (this.statusCheckInterval) {
      clearInterval(this.statusCheckInterval);
    }

    // Start checking status every 30 seconds
    this.statusCheckInterval = setInterval(async () => {
      await this.checkOrderStatus();
    }, 30000);
  }

  async checkOrderStatus() {
    if (!this.currentOrder?.orderId) return;

    try {
      const response = await fetch(
        `${this.endpoints.checkStatus}?orderId=${this.currentOrder.orderId}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "same-origin",
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        this.updateOrderStatus(result.status);
      }
    } catch (error) {
      console.error("Status check error:", error);
    }
  }

  updateOrderStatus(newStatus) {
    if (!this.currentOrder) return;

    const oldStatus = this.currentOrder.status;
    this.currentOrder.status = newStatus;

    // Update UI elements
    this.updateStatusDisplay(newStatus);

    // Show notification for status change
    if (oldStatus !== newStatus) {
      this.showStatusNotification(newStatus);
    }

    // Stop checking if order is in final state
    if (["Delivered", "Cancelled"].includes(newStatus)) {
      this.stopStatusChecking();
    }
  }

  showConfirmation(orderData) {
    // Implement UI confirmation display
    const modal = document.getElementById("orderConfirmationModal");
    if (modal) {
      const content = modal.querySelector(".modal-content");
      if (content) {
        content.innerHTML = `
                    <h2>Order Confirmed!</h2>
                    <p>Order Number: ${orderData.orderNumber}</p>
                    <p>Status: ${orderData.status}</p>
                    <p>Thank you for your order!</p>
                    <a href="/order_tracking.php?id=${orderData.orderId}" class="btn btn-primary">Track Order</a>
                `;
      }
      modal.style.display = "block";
    }

    // Show success notification
    if (window.notifications) {
      window.notifications.success("Order placed successfully!");
    }
  }

  showError(message) {
    if (window.notifications) {
      window.notifications.error(message);
    }
  }

  showStatusNotification(status) {
    if (window.notifications) {
      const messages = {
        Processing: "Your order is being prepared!",
        "Out for Delivery": "Your order is on the way!",
        Delivered: "Your order has been delivered!",
        Cancelled: "Your order has been cancelled.",
      };
      window.notifications.info(messages[status] || `Order status: ${status}`);
    }
  }

  showLoadingOverlay(message) {
    if (!this.loadingOverlay) {
      this.loadingOverlay = document.createElement("div");
      this.loadingOverlay.className = "loading-overlay";
      this.loadingOverlay.innerHTML = `
                <div class="loading-spinner"></div>
                <div class="loading-message">${message}</div>
            `;
      document.body.appendChild(this.loadingOverlay);
    }
    this.loadingOverlay.style.display = "flex";
  }

  hideLoadingOverlay() {
    if (this.loadingOverlay) {
      this.loadingOverlay.style.display = "none";
    }
  }

  stopStatusChecking() {
    if (this.statusCheckInterval) {
      clearInterval(this.statusCheckInterval);
      this.statusCheckInterval = null;
    }
  }

  destroy() {
    this.stopStatusChecking();
    if (this.loadingOverlay) {
      this.loadingOverlay.remove();
      this.loadingOverlay = null;
    }
  }
}
