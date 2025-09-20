class WebSocketHandler {
  constructor() {
    this.socket = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 1000; // Start with 1 second delay
    this.isReady = false;
    this.messageQueue = [];
    this.initialize();
  }

  initialize() {
    try {
      const config = WebSocketConfig.getConfig();
      this.socket = new WebSocket(config.url);
      this.attachEventListeners();
    } catch (error) {
      console.error("Failed to initialize WebSocket:", error);
      if (window.notifications) {
        window.notifications.error(
          "Connection error. Will retry automatically."
        );
      }
    }
  }

  attachEventListeners() {
    this.socket.addEventListener("open", () => {
      console.log("WebSocket connection established");
      this.reconnectAttempts = 0; // Reset reconnection attempts on successful connection
      this.reconnectDelay = 1000; // Reset delay
      this.isReady = true;
      this.processMessageQueue(); // Process any queued messages
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
      this.isReady = false; // Mark as not ready during reconnection
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

  sendMessage(message) {
    if (!this.socket || !message) return;

    if (this.socket.readyState === WebSocket.OPEN && this.isReady) {
      this.socket.send(JSON.stringify(message));
    } else {
      // Queue the message if socket isn't ready
      this.messageQueue.push(message);
    }
  }

  processMessageQueue() {
    while (
      this.messageQueue.length > 0 &&
      this.isReady &&
      this.socket.readyState === WebSocket.OPEN
    ) {
      const message = this.messageQueue.shift();
      try {
        this.socket.send(JSON.stringify(message));
      } catch (error) {
        console.error("Error sending queued message:", error);
        // Re-queue on error if it seems recoverable
        if (error.name !== "InvalidStateError") {
          this.messageQueue.unshift(message);
        }
      }
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
      // Gracefully handle known but non-actionable types
      case "welcome":
      case "ping":
        // Optionally log or ignore these message types
        // console.info(`Received '${data.type}' message from server.`);
        break;
      default:
        // Log and ignore unknown message types without breaking flow
        console.warn("Unknown message type:", data.type, data);
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
