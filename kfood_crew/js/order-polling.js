class OrderPollingManager {
  constructor(options = {}) {
    this.options = {
      pollInterval: options.pollInterval || 5000, // 5 seconds
      errorRetryDelay: options.errorRetryDelay || 10000, // 10 seconds
      maxRetries: options.maxRetries || 3,
      endpoints: {
        fetchOrders: "/api/fetch_orders.php",
        updateOrder: "/api/update_order.php",
      },
      ...options,
    };

    this.currentRetries = 0;
    this.polling = false;
    this.lastUpdate = new Date().toISOString();
    this.orderCallbacks = new Map();
    this.errorHandlers = new Set();
  }

  startPolling() {
    if (this.polling) return;
    this.polling = true;
    this.poll();
  }

  stopPolling() {
    this.polling = false;
    if (this.pollTimeout) {
      clearTimeout(this.pollTimeout);
    }
  }

  async poll() {
    if (!this.polling) return;

    try {
      const response = await fetch(
        `${this.options.endpoints.fetchOrders}?since=${this.lastUpdate}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.success) {
        this.currentRetries = 0;
        this.lastUpdate = data.timestamp;

        if (data.orders && data.orders.length > 0) {
          this.handleNewOrders(data.orders);
        }
      }

      // Schedule next poll
      this.pollTimeout = setTimeout(
        () => this.poll(),
        this.options.pollInterval
      );
    } catch (error) {
      console.error("Polling error:", error);
      this.handleError(error);

      // Retry with backoff
      if (this.currentRetries < this.options.maxRetries) {
        this.currentRetries++;
        this.pollTimeout = setTimeout(
          () => this.poll(),
          this.options.errorRetryDelay * this.currentRetries
        );
      } else {
        this.notifyError(new Error("Maximum retry attempts reached"));
      }
    }
  }

  handleNewOrders(orders) {
    orders.forEach((order) => {
      this.orderCallbacks.forEach((callback) => callback(order));
    });
  }

  handleError(error) {
    this.errorHandlers.forEach((handler) => handler(error));
  }

  onNewOrder(callback) {
    const id = Date.now();
    this.orderCallbacks.set(id, callback);
    return () => this.orderCallbacks.delete(id);
  }

  onError(handler) {
    this.errorHandlers.add(handler);
    return () => this.errorHandlers.delete(handler);
  }

  async updateOrderStatus(orderId, status) {
    try {
      const response = await fetch(this.options.endpoints.updateOrder, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({ orderId, status }),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      return result;
    } catch (error) {
      console.error("Error updating order:", error);
      this.handleError(error);
      throw error;
    }
  }
}

// Initialize Order Polling
document.addEventListener("DOMContentLoaded", () => {
  const orderManager = new OrderPollingManager({
    pollInterval: 5000,
    errorRetryDelay: 10000,
    maxRetries: 3,
  });

  // Start polling when page loads
  orderManager.startPolling();

  // Handle new orders
  orderManager.onNewOrder((order) => {
    // Trigger notification sound
    if (window.notificationSound) {
      window.notificationSound.play();
    }

    // Add order to dashboard
    if (window.dashboard) {
      window.dashboard.addOrder(order);
    }
  });

  // Handle errors
  orderManager.onError((error) => {
    if (window.notifications) {
      window.notifications.error(
        "Order system error. Please refresh the page."
      );
    }
    console.error("Order system error:", error);
  });

  // Make manager globally available
  window.orderManager = orderManager;
});
