// websocket-handler.js

class WebSocketHandler {
  constructor() {
    this.initialized = false;
    this.orderSocket = null;
    this.paymentSocket = null;
    this.reconnectAttempts = {
      order: 0,
      payment: 0,
    };
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 1000; // Start with 1 second
    this.authToken = null;
    this.messageQueue = {
      order: [],
      payment: [],
    };
    this.authenticated = {
      order: false,
      payment: false,
    };

    // Event callbacks
    this.callbacks = {
      order: new Map(),
      payment: new Map(),
    };

    // Initialize ping interval
    setInterval(() => this.pingConnections(), 30000);
  }

  init(authToken) {
    if (this.initialized) return;

    this.authToken = authToken;
    const wsProtocol = location.protocol === "https:" ? "wss:" : "ws:";
    const host = location.hostname;

    // Initialize order socket
    this.initSocket("order", `${wsProtocol}//${host}:8080`);

    // Initialize payment socket
    this.initSocket("payment", `${wsProtocol}//${host}:8081`);

    this.initialized = true;
  }

  initSocket(type, url) {
    try {
      const socket = new WebSocket(url);

      socket.onopen = () => this.handleOpen(type, socket);
      socket.onclose = () => this.handleClose(type);
      socket.onerror = (error) => this.handleError(type, error);
      socket.onmessage = (event) => this.handleMessage(type, event);

      if (type === "order") {
        this.orderSocket = socket;
      } else {
        this.paymentSocket = socket;
      }
    } catch (error) {
      console.error(`Failed to initialize ${type} WebSocket:`, error);
      this.scheduleReconnect(type);
    }
  }

  handleOpen(type, socket) {
    console.log(`${type} WebSocket connected`);
    this.reconnectAttempts[type] = 0;

    // Authenticate immediately after connection
    this.authenticate(type);

    // Trigger callbacks
    this.triggerCallback(type, "connect");
  }

  handleClose(type) {
    console.log(`${type} WebSocket disconnected`);

    // Trigger callbacks
    this.triggerCallback(type, "disconnect");

    // Attempt to reconnect
    this.scheduleReconnect(type);
  }

  handleError(type, error) {
    console.error(`${type} WebSocket error:`, error);
    this.triggerCallback(type, "error", error);
  }

  handleMessage(type, event) {
    try {
      const data = JSON.parse(event.data);
      console.debug(`${type} message received:`, data);

      switch (data.type) {
        case "auth_success":
          this.handleAuthSuccess(type, data);
          break;

        case "pong":
          // Update connection health status
          break;

        case "order_update":
        case "order_status_changed":
          this.triggerCallback(type, "orderUpdate", data);
          break;

        case "payment_initiated":
        case "payment_processed":
        case "payment_verified":
          this.triggerCallback(type, "paymentUpdate", data);
          break;

        case "error":
          this.handleError(type, new Error(data.message));
          break;

        default:
          this.triggerCallback(type, "message", data);
      }
    } catch (error) {
      console.error(`Failed to handle ${type} message:`, error);
    }
  }

  authenticate(type) {
    if (!this.authToken) {
      console.error("No auth token available");
      return;
    }

    this.send(type, {
      type: "authenticate",
      token: this.authToken,
    });
  }

  handleAuthSuccess(type, data) {
    console.log(`${type} WebSocket authenticated`);
    this.authenticated[type] = true;

    // Trigger authenticated callback
    this.triggerCallback(type, "authenticated", data);

    // Flush any queued messages
    if (this.messageQueue[type].length > 0) {
      console.log(
        `Flushing queued ${type} messages:`,
        this.messageQueue[type].length
      );
      this.flushMessageQueue(type);
    }

    if (type === "order") {
      // Subscribe to order updates after authentication
      this.send(type, { type: "subscribe_orders" });
    }
  }

  send(type, data) {
    const socket = type === "order" ? this.orderSocket : this.paymentSocket;

    if (socket && socket.readyState === WebSocket.OPEN) {
      socket.send(JSON.stringify(data));
    } else {
      console.error(`Cannot send message: ${type} WebSocket is not connected`);
      throw new Error("WebSocket not connected");
    }
  }

  scheduleReconnect(type) {
    if (this.reconnectAttempts[type] >= this.maxReconnectAttempts) {
      console.error(`Max reconnection attempts reached for ${type}`);
      return;
    }

    const delay =
      this.reconnectDelay * Math.pow(2, this.reconnectAttempts[type]);
    console.log(`Scheduling ${type} reconnect in ${delay}ms`);

    setTimeout(() => {
      this.reconnectAttempts[type]++;
      const wsProtocol = location.protocol === "https:" ? "wss:" : "ws:";
      const host = location.hostname;
      const port = type === "order" ? "8080" : "8081";

      this.initSocket(type, `${wsProtocol}//${host}:${port}`);
    }, delay);
  }

  pingConnections() {
    ["order", "payment"].forEach((type) => {
      try {
        this.send(type, { type: "ping" });
      } catch (error) {
        // Connection might be closed, ignore
      }
    });
  }

  on(type, event, callback) {
    if (!this.callbacks[type].has(event)) {
      this.callbacks[type].set(event, new Set());
    }
    this.callbacks[type].get(event).add(callback);
  }

  off(type, event, callback) {
    if (this.callbacks[type].has(event)) {
      this.callbacks[type].get(event).delete(callback);
    }
  }

  triggerCallback(type, event, data = null) {
    const callbacks = this.callbacks[type].get(event);
    if (callbacks) {
      callbacks.forEach((callback) => {
        try {
          callback(data);
        } catch (error) {
          console.error(`Error in ${type} ${event} callback:`, error);
        }
      });
    }
  }
}

// Create global instance
window.wsHandler = new WebSocketHandler();

// Initialize when document is ready
document.addEventListener("DOMContentLoaded", () => {
  // Get auth token from meta tag or localStorage
  const authToken =
    document.querySelector('meta[name="auth-token"]')?.content ||
    localStorage.getItem("auth-token");

  if (authToken) {
    window.wsHandler.init(authToken);
  } else {
    console.error("No authentication token found");
  }
});
