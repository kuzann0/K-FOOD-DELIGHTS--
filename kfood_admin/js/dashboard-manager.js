/**
 * AdminDashboardManager - Handles admin dashboard data polling and updates
 */
class AdminDashboardManager {
  constructor(options = {}) {
    this.options = {
      pollInterval: options.pollInterval || 10000, // 10 seconds
      errorRetryDelay: options.errorRetryDelay || 30000, // 30 seconds
      maxRetries: options.maxRetries || 3,
      endpoints: {
        dashboardData: "/kfood_admin/api/get_dashboard_data.php",
        updateSettings: "/kfood_admin/api/update_dashboard_settings.php",
      },
      ...options,
    };

    this.currentRetries = 0;
    this.polling = false;
    this.lastUpdate = new Date().toISOString();
    this.dataCallbacks = new Map();
    this.errorHandlers = new Set();
    this.stats = {};
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
        `${this.options.endpoints.dashboardData}?since=${this.lastUpdate}`,
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
        this.updateDashboard(data);
      }

      // Schedule next poll
      this.pollTimeout = setTimeout(
        () => this.poll(),
        this.options.pollInterval
      );
    } catch (error) {
      console.error("Dashboard polling error:", error);
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

  updateDashboard(data) {
    // Update statistics
    this.stats = {
      ...this.stats,
      ...data.stats,
    };

    // Update charts if available
    if (data.chartData && window.dashboardCharts) {
      Object.entries(data.chartData).forEach(([chartId, chartData]) => {
        window.dashboardCharts.updateChart(chartId, chartData);
      });
    }

    // Update order lists
    if (data.orders) {
      this.updateOrderLists(data.orders);
    }

    // Update alerts
    if (data.alerts) {
      this.showAlerts(data.alerts);
    }

    // Notify callbacks
    this.dataCallbacks.forEach((callback) => callback(data));
  }

  updateOrderLists(orders) {
    const containers = {
      pending: document.getElementById("pendingOrdersList"),
      processing: document.getElementById("processingOrdersList"),
      completed: document.getElementById("completedOrdersList"),
    };

    Object.entries(orders).forEach(([status, orderList]) => {
      const container = containers[status.toLowerCase()];
      if (container) {
        this.renderOrderList(container, orderList);
      }
    });
  }

  renderOrderList(container, orders) {
    // Clear existing content if this is a full refresh
    if (!orders.isPartialUpdate) {
      container.innerHTML = "";
    }

    orders.forEach((order) => {
      const orderElement = this.createOrderElement(order);
      if (orders.isPartialUpdate) {
        // Find and update existing order or prepend new one
        const existing = container.querySelector(
          `[data-order-id="${order.orderId}"]`
        );
        if (existing) {
          existing.replaceWith(orderElement);
        } else {
          container.prepend(orderElement);
        }
      } else {
        container.appendChild(orderElement);
      }
    });
  }

  createOrderElement(order) {
    const element = document.createElement("div");
    element.className = "order-card";
    element.dataset.orderId = order.orderId;

    element.innerHTML = `
            <div class="order-header">
                <span class="order-number">#${order.orderNumber}</span>
                <span class="order-time">${order.created_at}</span>
            </div>
            <div class="order-details">
                <p class="customer-name">${order.customerName}</p>
                <p class="order-items">${order.items}</p>
                <p class="order-total">Total: ₱${order.totalAmount}</p>
            </div>
            <div class="order-actions">
                <select class="status-select" data-order-id="${order.orderId}">
                    <option value="Pending" ${
                      order.status === "Pending" ? "selected" : ""
                    }>Pending</option>
                    <option value="Processing" ${
                      order.status === "Processing" ? "selected" : ""
                    }>Processing</option>
                    <option value="Out for Delivery" ${
                      order.status === "Out for Delivery" ? "selected" : ""
                    }>Out for Delivery</option>
                    <option value="Delivered" ${
                      order.status === "Delivered" ? "selected" : ""
                    }>Delivered</option>
                    <option value="Cancelled" ${
                      order.status === "Cancelled" ? "selected" : ""
                    }>Cancelled</option>
                </select>
                <button class="view-details-btn" data-order-id="${
                  order.orderId
                }">View Details</button>
            </div>
        `;

    // Add event listeners
    const statusSelect = element.querySelector(".status-select");
    statusSelect.addEventListener("change", (e) => {
      this.updateOrderStatus(order.orderId, e.target.value);
    });

    const viewButton = element.querySelector(".view-details-btn");
    viewButton.addEventListener("click", () => {
      this.showOrderDetails(order.orderId);
    });

    return element;
  }

  async updateOrderStatus(orderId, status) {
    try {
      const response = await fetch("/api/update_order_status.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
            ?.content,
        },
        body: JSON.stringify({ orderId, status }),
        credentials: "include",
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        if (window.notifications) {
          window.notifications.success("Order status updated successfully");
        }
      } else {
        throw new Error(result.message);
      }
    } catch (error) {
      console.error("Error updating order status:", error);
      if (window.notifications) {
        window.notifications.error("Failed to update order status");
      }
    }
  }

  async showOrderDetails(orderId) {
    // Implement order details modal display
  }

  showAlerts(alerts) {
    if (!window.notifications) return;

    alerts.forEach((alert) => {
      window.notifications[alert.type](alert.message);
    });
  }

  handleError(error) {
    this.errorHandlers.forEach((handler) => handler(error));
  }

  onDataUpdate(callback) {
    const id = Date.now();
    this.dataCallbacks.set(id, callback);
    return () => this.dataCallbacks.delete(id);
  }

  onError(handler) {
    this.errorHandlers.add(handler);
    return () => this.errorHandlers.delete(handler);
  }

  destroy() {
    this.stopPolling();
    this.dataCallbacks.clear();
    this.errorHandlers.clear();
  }
}

// Initialize dashboard when document is ready
document.addEventListener("DOMContentLoaded", () => {
  const dashboardManager = new AdminDashboardManager({
    pollInterval: 10000,
    errorRetryDelay: 30000,
    maxRetries: 3,
  });

  // Start polling
  dashboardManager.startPolling();

  // Handle errors
  dashboardManager.onError((error) => {
    if (window.notifications) {
      window.notifications.error(
        "Dashboard update error. Please refresh the page."
      );
    }
    console.error("Dashboard error:", error);
  });

  // Make manager globally available
  window.dashboardManager = dashboardManager;
});
