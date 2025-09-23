/**
 * WebSocket client manager for K-Food Delights
 */
class WebSocketClient {
  constructor() {
    this.connections = {};
    this.retryAttempts = {};
    this.maxRetries = 3;
    this.retryDelay = 1000; // Start with 1 second
    this.handlers = {
      order: {},
      payment: {},
    };

    // Initialize connections
    this.init();
  }

  init() {
    const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
    const host = window.location.hostname;

    // Initialize order socket
    this.initSocket("order", `${protocol}//${host}:8080`);

    // Initialize payment socket
    this.initSocket("payment", `${protocol}//${host}:8081`);

    // Set up periodic ping
    setInterval(() => this.pingConnections(), 30000);
  }

  initSocket(type, url) {
    try {
      console.log(`Initializing ${type} WebSocket connection to ${url}`);

      const ws = new WebSocket(url);
      this.connections[type] = ws;
      this.retryAttempts[type] = 0;

      ws.onopen = () => this.handleOpen(type);
      ws.onclose = () => this.handleClose(type);
      ws.onerror = (error) => this.handleError(type, error);
      ws.onmessage = (event) => this.handleMessage(type, event);
    } catch (error) {
      console.error(`Failed to initialize ${type} WebSocket:`, error);
      this.scheduleReconnect(type);
    }
  }

  handleOpen(type) {
    console.log(`${type} WebSocket connected`);
    this.retryAttempts[type] = 0;

    // Trigger connected handlers
    this.triggerHandler(type, "connected");
  }

  handleClose(type) {
    console.log(`${type} WebSocket disconnected`);

    // Trigger disconnected handlers
    this.triggerHandler(type, "disconnected");

    // Attempt to reconnect
    this.scheduleReconnect(type);
  }

  handleError(type, error) {
    console.error(`${type} WebSocket error:`, error);

    // Trigger error handlers
    this.triggerHandler(type, "error", error);
  }

  handleMessage(type, event) {
    try {
      const data = JSON.parse(event.data);
      console.log(`${type} WebSocket message:`, data);

      // Handle different message types
      switch (data.type) {
        case "pong":
          // Update connection health status
          break;

        case "auth_success":
          this.triggerHandler(type, "authenticated", data);
          break;

        case "order_update":
        case "order_status_changed":
          this.triggerHandler(type, "orderUpdate", data);
          break;

        case "payment_initiated":
        case "payment_processed":
        case "payment_verified":
          this.triggerHandler(type, "paymentUpdate", data);
          break;

        case "error":
          this.triggerHandler(type, "error", data);
          break;

        default:
          this.triggerHandler(type, "message", data);
      }
    } catch (error) {
      console.error(`Failed to handle ${type} WebSocket message:`, error);
    }
  }

  scheduleReconnect(type) {
    if (this.retryAttempts[type] >= this.maxRetries) {
      console.error(`Max retry attempts reached for ${type} WebSocket`);
      return;
    }

    const delay = this.retryDelay * Math.pow(2, this.retryAttempts[type]);
    console.log(`Scheduling ${type} WebSocket reconnect in ${delay}ms`);

    setTimeout(() => {
      this.retryAttempts[type]++;
      this.initSocket(type, this.connections[type].url);
    }, delay);
  }

  pingConnections() {
    Object.entries(this.connections).forEach(([type, ws]) => {
      if (ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: "ping" }));
      }
    });
  }

  on(type, event, handler) {
    if (!this.handlers[type][event]) {
      this.handlers[type][event] = [];
    }
    this.handlers[type][event].push(handler);
  }

  triggerHandler(type, event, data = null) {
    const handlers = this.handlers[type][event] || [];
    handlers.forEach((handler) => {
      try {
        handler(data);
      } catch (error) {
        console.error(`Error in ${type} ${event} handler:`, error);
      }
    });
  }

  sendMessage(type, data) {
    const ws = this.connections[type];
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(data));
    } else {
      console.error(`Cannot send message: ${type} WebSocket is not connected`);
      throw new Error(`WebSocket not connected`);
    }
  }
}

// Export as global if not using modules
window.KFoodWebSocket = new WebSocketClient();
