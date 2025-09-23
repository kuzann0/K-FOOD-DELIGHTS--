class OrderTracker {
  constructor() {
    this.ws = null;
    this.ordersContainer = document.getElementById("active-orders");
    this.initWebSocket();
    this.bindEvents();
  }

  initWebSocket() {
    const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
    const wsUrl = `${protocol}//127.0.0.1:8080/ws`;

    this.ws = new WebSocket(wsUrl);

    this.ws.onopen = () => {
      console.log("Connected to order tracking system");
      // Authenticate the crew member
      if (window.CREW_AUTH_TOKEN) {
        this.ws.send(
          JSON.stringify({
            type: "auth",
            token: window.CREW_AUTH_TOKEN,
            role: "crew",
          })
        );
      }
    };

    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      switch (data.type) {
        case "new_order":
          this.handleNewOrder(data.order);
          break;
        case "order_update":
          this.updateOrderStatus(data.orderId, data.status);
          break;
        case "order_list":
          this.renderOrderList(data.orders);
          break;
      }
    };

    this.ws.onerror = (error) => {
      console.error("WebSocket error:", error);
    };

    this.ws.onclose = () => {
      console.log("Connection closed. Reconnecting...");
      setTimeout(() => this.initWebSocket(), 5000);
    };
  }

  bindEvents() {
    // Delegate event listener for order status updates
    document.addEventListener("click", (e) => {
      if (e.target.matches(".status-btn")) {
        const orderId = e.target.closest(".order-card").dataset.orderId;
        const newStatus = e.target.dataset.status;
        this.updateOrderStatus(orderId, newStatus);
      }
    });
  }

  handleNewOrder(order) {
    // Play notification sound
    this.playNotificationSound();

    // Show notification
    this.showNotification("New Order!", `Order #${order.id} has been placed`);

    // Add order to the list
    const orderCard = this.createOrderCard(order);
    this.ordersContainer.insertBefore(
      orderCard,
      this.ordersContainer.firstChild
    );

    // Highlight the new order
    orderCard.classList.add("new-order");
    setTimeout(() => orderCard.classList.remove("new-order"), 5000);
  }

  createOrderCard(order) {
    const card = document.createElement("div");
    card.className = "order-card";
    card.dataset.orderId = order.id;

    card.innerHTML = `
            <div class="order-header">
                <h3>Order #${order.id}</h3>
                <span class="order-time">${this.formatTime(
                  order.created_at
                )}</span>
            </div>
            <div class="order-items">
                ${order.items
                  .map(
                    (item) => `
                    <div class="order-item">
                        <span>${item.name} x${item.quantity}</span>
                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `
                  )
                  .join("")}
            </div>
            <div class="order-details">
                <p><strong>Total:</strong> ₱${order.total.toFixed(2)}</p>
                <p><strong>Payment:</strong> ${order.payment_method}</p>
                <p><strong>Customer:</strong> ${this.escapeHtml(
                  order.customer_name
                )}</p>
                <p><strong>Address:</strong> ${this.escapeHtml(
                  order.address
                )}</p>
                ${
                  order.instructions
                    ? `
                    <p><strong>Instructions:</strong> ${this.escapeHtml(
                      order.instructions
                    )}</p>
                `
                    : ""
                }
            </div>
            <div class="order-status">
                <div class="status-label ${order.status}">${order.status}</div>
                <div class="status-buttons">
                    ${this.getStatusButtons(order.status)}
                </div>
            </div>
        `;

    return card;
  }

  getStatusButtons(currentStatus) {
    const statuses = {
      pending: ["accept", "reject"],
      accepted: ["preparing"],
      preparing: ["ready"],
      ready: ["delivered"],
      delivered: [],
    };

    return (statuses[currentStatus] || [])
      .map(
        (status) => `
                <button class="status-btn ${status}" data-status="${status}">
                    ${this.getStatusIcon(status)}
                    ${this.capitalizeFirst(status)}
                </button>
            `
      )
      .join("");
  }

  getStatusIcon(status) {
    const icons = {
      accept: '<i class="fas fa-check"></i>',
      reject: '<i class="fas fa-times"></i>',
      preparing: '<i class="fas fa-utensils"></i>',
      ready: '<i class="fas fa-bell"></i>',
      delivered: '<i class="fas fa-truck"></i>',
    };
    return icons[status] || "";
  }

  async updateOrderStatus(orderId, newStatus) {
    try {
      const response = await fetch("api/update_order_status.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]')
            .content,
        },
        body: JSON.stringify({ orderId, status: newStatus }),
      });

      const result = await response.json();

      if (result.success) {
        // Update UI
        const orderCard = document.querySelector(
          `.order-card[data-order-id="${orderId}"]`
        );
        if (orderCard) {
          orderCard.querySelector(
            ".status-label"
          ).className = `status-label ${newStatus}`;
          orderCard.querySelector(".status-label").textContent = newStatus;
          orderCard.querySelector(".status-buttons").innerHTML =
            this.getStatusButtons(newStatus);
        }

        // Notify WebSocket server
        this.ws.send(
          JSON.stringify({
            type: "status_update",
            orderId: orderId,
            status: newStatus,
          })
        );
      } else {
        throw new Error(result.message || "Failed to update order status");
      }
    } catch (error) {
      console.error("Error updating order status:", error);
      alert("Failed to update order status: " + error.message);
    }
  }

  playNotificationSound() {
    const audio = new Audio("assets/notification.mp3");
    audio
      .play()
      .catch((error) => console.log("Error playing notification:", error));
  }

  showNotification(title, message) {
    if ("Notification" in window && Notification.permission === "granted") {
      new Notification(title, { body: message });
    } else if (
      "Notification" in window &&
      Notification.permission !== "denied"
    ) {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          new Notification(title, { body: message });
        }
      });
    }
  }

  formatTime(timestamp) {
    return new Date(timestamp).toLocaleTimeString("en-US", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  capitalizeFirst(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  // Request notification permission
  if ("Notification" in window) {
    Notification.requestPermission();
  }

  new OrderTracker();
});
