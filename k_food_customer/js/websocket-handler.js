class WebSocketHandler {
  constructor() {
    this.socket = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 1000; // Start with 1 second delay
    this.initialize();
  }

  initialize() {
    try {
      const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
      const host = window.location.hostname;
      const port = "8080"; // WebSocket server port
      this.socket = new WebSocket(`${protocol}//${host}:${port}`);
      this.attachEventListeners();
    } catch (error) {
      console.error("Failed to initialize WebSocket:", error);
    }
  }

  attachEventListeners() {
    this.socket.addEventListener("open", () => {
      console.log("WebSocket connection established");
      this.reconnectAttempts = 0; // Reset reconnection attempts on successful connection
      this.reconnectDelay = 1000; // Reset delay
    });

    this.socket.addEventListener("error", (error) => {
      console.error("WebSocket error:", error);
    });

    this.socket.addEventListener("close", () => {
      console.log("WebSocket connection closed");
      this.handleReconnection();
    });

    this.socket.addEventListener("message", (event) => {
      try {
        const data = JSON.parse(event.data);
        this.handleMessage(data);
      } catch (error) {
        console.error("Error processing WebSocket message:", error);
      }
    });
  }

  handleReconnection() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      setTimeout(() => {
        console.log(
          `Attempting to reconnect (${this.reconnectAttempts + 1}/${
            this.maxReconnectAttempts
          })...`
        );
        this.reconnectAttempts++;
        this.initialize();
      }, this.reconnectDelay);

      // Exponential backoff for next attempt
      this.reconnectDelay = Math.min(this.reconnectDelay * 2, 30000);
    } else {
      console.error("Max reconnection attempts reached");
      showNotification(
        "error",
        "Lost connection to server. Please refresh the page."
      );
    }
  }

  handleMessage(data) {
    switch (data.type) {
      case "order_status":
        this.handleOrderStatus(data);
        break;
      case "error":
        this.handleError(data);
        break;
      default:
        console.warn("Unknown message type:", data.type);
    }
  }

  handleOrderStatus(data) {
    const statusElement = document.getElementById("order-status");
    if (statusElement) {
      statusElement.textContent = data.status;
      statusElement.className = `status-${data.status
        .toLowerCase()
        .replace(/\s+/g, "-")}`;
    }

    // Show notification for status updates
    if (data.message) {
      showNotification("info", data.message);
    }
  }

  handleError(data) {
    console.error("Server error:", data.message);
    showNotification("error", data.message || "An error occurred");
  }

  sendMessage(message) {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      try {
        this.socket.send(JSON.stringify(message));
      } catch (error) {
        console.error("Error sending WebSocket message:", error);
        showNotification("error", "Failed to send update to server");
      }
    } else {
      console.warn("WebSocket is not connected");
    }
  }

  sendOrderUpdate(orderId, status, additionalData = {}) {
    this.sendMessage({
      type: "order_update",
      orderId: orderId,
      status: status,
      timestamp: new Date().toISOString(),
      ...additionalData,
    });
  }
}

// Initialize WebSocket handler when page loads
document.addEventListener("DOMContentLoaded", () => {
  window.wsHandler = new WebSocketHandler();
});
