// Initialize WebSocket connection for crew dashboard
class CrewWebSocket {
  constructor(config = {}) {
    this.config = {
      url: config.url || "ws://localhost:8080",
      reconnectAttempts: config.reconnectAttempts || 5,
      reconnectDelay: config.reconnectDelay || 5000,
      debug: config.debug || false,
    };

    this.connect();
    this.setupEventListeners();
  }

  connect() {
    try {
      this.ws = new WebSocket(this.config.url);
      this.ws.onopen = this.handleOpen.bind(this);
      this.ws.onmessage = this.handleMessage.bind(this);
      this.ws.onclose = this.handleClose.bind(this);
      this.ws.onerror = this.handleError.bind(this);
    } catch (error) {
      console.error("WebSocket connection error:", error);
      this.scheduleReconnect();
    }
  }

  handleOpen() {
    console.log("Connected to WebSocket server");
    // Authenticate as crew member
    this.send({
      type: "authentication",
      userType: "crew",
    });
  }

  handleMessage(event) {
    try {
      const data = JSON.parse(event.data);

      switch (data.type) {
        case "new_order":
          this.handleNewOrder(data.order);
          break;
        case "order_update":
          this.handleOrderUpdate(data.order);
          break;
        case "authentication":
          this.handleAuthentication(data);
          break;
      }
    } catch (error) {
      console.error("Error handling message:", error);
    }
  }

  handleNewOrder(order) {
    // Create new order card
    const orderCard = this.createOrderCard(order);

    // Add to orders container with animation
    const container = document.getElementById("ordersContainer");
    if (container) {
      // Remove empty state if present
      const emptyState = container.querySelector(".empty-state");
      if (emptyState) {
        emptyState.remove();
      }

      // Add new order with animation
      orderCard.classList.add("new-order");
      container.insertBefore(orderCard, container.firstChild);

      // Play notification sound
      this.playNotificationSound();

      // Show notification
      this.showNotification(
        "New Order Received",
        `Order #${order.order_number} from ${order.customer_name}`
      );
    }
  }

  handleOrderUpdate(order) {
    const orderCard = document.querySelector(
      `[data-order-id="${order.order_id}"]`
    );
    if (orderCard) {
      // Update status
      const statusEl = orderCard.querySelector(".order-status");
      if (statusEl) {
        statusEl.textContent = order.status;
        statusEl.className = `order-status ${order.status.toLowerCase()}`;
      }

      // Update other order details if needed
      const updatedEl = orderCard.querySelector(".order-updated");
      if (updatedEl) {
        updatedEl.textContent = "Updated: " + new Date().toLocaleTimeString();
      }
    }
  }

  handleAuthentication(data) {
    if (data.status === "success") {
      console.log("Successfully authenticated with WebSocket server");
    } else {
      console.error("Authentication failed:", data.message);
      this.showError(
        "Connection Error",
        "Failed to authenticate with the server"
      );
    }
  }

  createOrderCard(order) {
    const card = document.createElement("div");
    card.className = "order-card";
    card.dataset.orderId = order.order_id;

    card.innerHTML = `
            <div class="order-header">
                <h3>Order #${order.order_number}</h3>
                <span class="order-status ${order.status.toLowerCase()}">${
      order.status
    }</span>
            </div>
            <div class="order-details">
                <p><i class="fas fa-user"></i> ${order.customer_name}</p>
                <p><i class="fas fa-phone"></i> ${order.contact_number}</p>
                <p><i class="fas fa-map-marker-alt"></i> ${
                  order.delivery_address
                }</p>
            </div>
            <div class="order-items">
                <h4>Items:</h4>
                <ul>
                    ${order.items.map((item) => `<li>${item}</li>`).join("")}
                </ul>
            </div>
            <div class="order-footer">
                <span class="order-total">Total: ₱${parseFloat(
                  order.total_amount
                ).toFixed(2)}</span>
                <button class="action-btn" onclick="openOrderDetails('${
                  order.order_id
                }')">
                    View Details
                </button>
            </div>
        `;

    return card;
  }

  handleClose() {
    console.log("WebSocket connection closed");
    this.scheduleReconnect();
  }

  handleError(error) {
    console.error("WebSocket error:", error);
  }

  scheduleReconnect() {
    if (this.reconnectAttempts < this.config.reconnectAttempts) {
      this.reconnectAttempts++;
      setTimeout(() => this.connect(), this.config.reconnectDelay);
    } else {
      this.showError(
        "Connection Lost",
        "Unable to reconnect to the server. Please refresh the page."
      );
    }
  }

  send(data) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(data));
    }
  }

  setupEventListeners() {
    // Status filter buttons
    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
        const status = this.dataset.status;
        document.querySelectorAll(".order-card").forEach((card) => {
          if (
            status === "all" ||
            card.querySelector(".order-status").textContent.toLowerCase() ===
              status
          ) {
            card.style.display = "block";
          } else {
            card.style.display = "none";
          }
        });

        // Update active filter
        document
          .querySelectorAll(".filter-btn")
          .forEach((b) => b.classList.remove("active"));
        this.classList.add("active");
      });
    });
  }

  playNotificationSound() {
    const audio = new Audio("audio/notification.mp3");
    audio
      .play()
      .catch((error) =>
        console.log("Error playing notification sound:", error)
      );
  }

  showNotification(title, message) {
    if ("Notification" in window) {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          new Notification(title, {
            body: message,
            icon: "../resources/images/logo.png",
          });
        }
      });
    }

    // Also show in-app notification
    const notification = document.createElement("div");
    notification.className = "notification new-order";
    notification.innerHTML = `
            <i class="fas fa-bell"></i>
            <div class="notification-content">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
        `;

    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
  }

  showError(title, message) {
    const notification = document.createElement("div");
    notification.className = "notification error";
    notification.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <div class="notification-content">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
        `;

    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
  }
}

// Initialize WebSocket connection when the page loads
document.addEventListener("DOMContentLoaded", () => {
  window.crewWS = new CrewWebSocket({
    debug: true,
  });
});
