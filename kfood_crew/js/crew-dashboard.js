class OrderManager {
  constructor() {
    this.orders = new Map();
    this.currentFilter = "all";
    this.lastUpdate = null;
    this.wsManager = null;
    this.pollInterval = null;
    this.init();
  }

  init() {
    // Initialize WebSocket before other operations
    this.initializeWebSocket()
      .then(() => {
        this.setupEventListeners();
        this.loadOrders();
      })
      .catch((error) => {
        console.error("Failed to initialize WebSocket:", error);
        if (window.notifications) {
          window.notifications.error(
            "Connection error. Falling back to polling."
          );
        }
        this.startPolling();
      });
  }

  async initializeWebSocket() {
    try {
      // Check server status first
      const statusResponse = await fetch("api/server-status.php");
      const statusData = await statusResponse.json();

      if (statusData.status !== "running") {
        throw new Error("WebSocket server is not running");
      }

      // Create WebSocket manager with configuration
      this.wsManager = new WebSocketManager({
        host: window.location.hostname,
        path: "/websocket",
        reconnectAttempts: 5,
        reconnectInterval: 3000,
        debug: true,
      });

      await this.setupWebSocketHandlers();
      return true;
    } catch (error) {
      console.error("WebSocket initialization error:", error);
      throw error;
    }
  }

  async setupWebSocketHandlers() {
    if (!this.wsManager) {
      throw new Error("WebSocket manager not initialized");
    }

    // Connection events
    this.wsManager.on("open", () => {
      if (window.notifications) {
        window.notifications.success("Connected to order system");
      }
      this.stopPolling();
    });

    this.wsManager.on("close", () => {
      if (window.notifications) {
        window.notifications.warning(
          "Connection lost. Attempting to reconnect..."
        );
      }
    });

    this.wsManager.on("error", (error) => {
      console.error("WebSocket error:", error);
      if (window.notifications) {
        window.notifications.error("Connection error. Switching to polling.");
      }
      this.startPolling();
    });

    // Order events
    this.wsManager.on("new_order", (order) => this.handleNewOrder(order));
    this.wsManager.on("order_update", (update) =>
      this.handleOrderUpdate(update)
    );
  }

  startPolling() {
    if (this.pollInterval) {
      clearInterval(this.pollInterval);
    }

    // Initial load
    this.loadOrders();

    // Start polling every 30 seconds
    this.pollInterval = setInterval(() => this.loadOrders(), 30000);
    console.log("Fallback: Started polling for orders");
  }

  stopPolling() {
    if (this.pollInterval) {
      clearInterval(this.pollInterval);
      this.pollInterval = null;
      console.log("Stopped polling");
    }
  }

  async loadOrders() {
    try {
      const response = await fetch("api/get_orders.php");

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Failed to load orders");
      }

      // Clear existing orders
      this.orders.clear();

      // Process and add new orders
      data.orders.forEach((order) => {
        this.orders.set(order.id, order);
      });

      this.renderOrders();
      this.lastUpdate = new Date();
    } catch (error) {
      console.error("Error loading orders:", error);
      if (window.notifications) {
        window.notifications.error(
          "Failed to load orders. Please refresh the page."
        );
      }
    }
  }

  handleNewOrder(order) {
    if (!this.orders.has(order.id)) {
      this.orders.set(order.id, order);
      this.renderOrders();
      this.playNotificationSound();
      if (window.notifications) {
        window.notifications.info(`New order received: #${order.number}`);
      }
    }
  }

  handleOrderUpdate(update) {
    const order = this.orders.get(update.id);
    if (order) {
      Object.assign(order, update);
      this.renderOrders();
      if (window.notifications) {
        window.notifications.info(`Order #${order.number} updated`);
      }
    }
  }

  playNotificationSound() {
    // Add sound notification logic here if needed
  }

  setupEventListeners() {
    // Filter buttons
    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        this.setFilter(e.target.dataset.status);
      });
    });

    // Navigation links
    document.querySelectorAll(".nav-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        if (e.target.dataset.view) {
          e.preventDefault();
          this.changeView(e.target.dataset.view);
        }
      });
    });
  }

  setFilter(status) {
    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.status === status);
    });
    this.currentFilter = status;
    this.renderOrders();
  }

  changeView(view) {
    document.querySelectorAll(".nav-link").forEach((link) => {
      link.classList.toggle("active", link.dataset.view === view);
    });
    this.renderOrders();
  }

  renderOrders() {
    const container = document.getElementById("ordersContainer");
    if (!container) return;

    container.innerHTML = "";

    const filteredOrders = Array.from(this.orders.values()).filter(
      (order) =>
        this.currentFilter === "all" || order.status === this.currentFilter
    );

    if (filteredOrders.length === 0) {
      container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No orders found</h3>
                    <p>No ${
                      this.currentFilter === "all" ? "" : this.currentFilter
                    } orders at the moment</p>
                </div>
            `;
      return;
    }

    filteredOrders
      .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
      .forEach((order) => {
        container.appendChild(this.createOrderCard(order));
      });
  }

  createOrderCard(order) {
    const card = document.createElement("div");
    card.className = `order-card ${order.status}`;
    card.innerHTML = `
            <div class="order-header">
                <h3>Order #${order.number}</h3>
                <span class="status-badge ${order.status}">${
      order.status
    }</span>
            </div>
            <div class="order-body">
                <div class="order-info">
                    <p><i class="fas fa-user"></i> ${order.customer_name}</p>
                    <p><i class="fas fa-clock"></i> ${new Date(
                      order.created_at
                    ).toLocaleString()}</p>
                    <p><i class="fas fa-money-bill"></i> ₱${
                      order.total_amount
                    }</p>
                </div>
                <div class="order-items">
                    ${this.renderOrderItems(order.items)}
                </div>
            </div>
            <div class="order-footer">
                ${this.renderActionButtons(order)}
            </div>
        `;

    // Add event listeners for action buttons
    const actionButtons = card.querySelectorAll(".action-btn");
    actionButtons.forEach((btn) => {
      btn.addEventListener("click", () =>
        this.handleOrderAction(btn.dataset.action, order.id)
      );
    });

    return card;
  }

  renderOrderItems(items) {
    return items
      .map(
        (item) => `
            <div class="order-item">
                <span class="item-name">${item.name}</span>
                <span class="item-quantity">x${item.quantity}</span>
            </div>
        `
      )
      .join("");
  }

  renderActionButtons(order) {
    const buttons = [];
    switch (order.status) {
      case "pending":
        buttons.push(`
                    <button class="action-btn accept" data-action="accept" data-order-id="${order.id}">
                        <i class="fas fa-check"></i> Accept
                    </button>
                `);
        break;
      case "preparing":
        buttons.push(`
                    <button class="action-btn ready" data-action="ready" data-order-id="${order.id}">
                        <i class="fas fa-utensils"></i> Mark Ready
                    </button>
                `);
        break;
      case "ready":
        buttons.push(`
                    <button class="action-btn deliver" data-action="deliver" data-order-id="${order.id}">
                        <i class="fas fa-motorcycle"></i> Mark Delivered
                    </button>
                `);
        break;
    }
    return buttons.join("");
  }

  async handleOrderAction(action, orderId) {
    try {
      const response = await fetch("api/update_order_status.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          order_id: orderId,
          action: action,
        }),
      });

      if (!response.ok) {
        throw new Error("Failed to update order status");
      }

      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message || "Failed to update order");
      }

      // Update local order status
      const order = this.orders.get(orderId);
      if (order) {
        order.status = result.new_status;
        this.renderOrders();
      }

      if (window.notifications) {
        window.notifications.success(
          `Order #${order.number} ${action}ed successfully`
        );
      }

      // Notify via WebSocket if connected
      if (this.wsManager) {
        this.wsManager.emit("order_update", {
          order_id: orderId,
          status: result.new_status,
        });
      }
    } catch (error) {
      console.error("Error updating order:", error);
      if (window.notifications) {
        window.notifications.error("Failed to update order. Please try again.");
      }
    }
  }
}

// Initialize the order manager when the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  window.orderManager = new OrderManager();
});

// Modal Functions
function viewOrderDetails(orderId) {
  const order = window.orderManager.orders.get(orderId);
  if (!order) return;

  const modal = document.getElementById("orderModal");
  modal.querySelector(".modal-content").innerHTML = `
        <div class="modal-header">
            <h2>Order #${order.order_id}</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="customer-info">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> ${order.customer_name}</p>
                <p><strong>Phone:</strong> ${order.customer_phone}</p>
                <p><strong>Address:</strong> ${order.delivery_address}</p>
            </div>
            <div class="order-details">
                <h3>Order Items</h3>
                ${order.items
                  .map(
                    (item) => `
                    <div class="detail-item">
                        <span>${item.name}</span>
                        <span>×${item.quantity}</span>
                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `
                  )
                  .join("")}
                <div class="order-total">
                    <strong>Total:</strong> ₱${order.total.toFixed(2)}
                </div>
            </div>
            <div class="order-notes">
                <h3>Special Instructions</h3>
                <p>${order.special_instructions || "None"}</p>
            </div>
        </div>
    `;
  modal.style.display = "block";
}

function closeModal() {
  document.getElementById("orderModal").style.display = "none";
}
