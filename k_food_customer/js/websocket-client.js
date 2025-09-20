class WebSocketClient {
  constructor(config = {}) {
    this.config = {
      url: config.url || "ws://localhost:8080",
      reconnectAttempts: config.reconnectAttempts || 5,
      reconnectDelay: config.reconnectDelay || 5000,
      pingInterval: config.pingInterval || 30000,
      debug: config.debug || false,
      ...config,
    };

    this.ws = null;
    this.reconnectCount = 0;
    this.handlers = new Map();
    this.pingTimer = null;
    this.authenticated = false;
    this.connecting = false;

    // Bind methods
    this.connect = this.connect.bind(this);
    this.reconnect = this.reconnect.bind(this);
    this.handleOpen = this.handleOpen.bind(this);
    this.handleMessage = this.handleMessage.bind(this);
    this.handleError = this.handleError.bind(this);
    this.handleClose = this.handleClose.bind(this);

    // Initialize connection
    this.connect();
  }

  log(...args) {
    if (this.config.debug) {
      console.log("[WebSocket]", ...args);
    }
  }

  connect() {
    if (this.connecting) return;
    this.connecting = true;

    try {
      this.ws = new WebSocket(this.config.url);
      this.ws.onopen = this.handleOpen;
      this.ws.onmessage = this.handleMessage;
      this.ws.onerror = this.handleError;
      this.ws.onclose = this.handleClose;
    } catch (error) {
      this.log("Connection error:", error);
      this.handleError(error);
    }
  }

  handleOpen() {
    this.connecting = false;
    this.reconnectCount = 0;
    this.log("Connected");

    // Start ping interval
    this.startPingInterval();

    // Emit open event
    this.emit("open");

    // Authenticate the connection
    if (this.config.userType) {
      this.authenticate(this.config.userType);
    }
  }

  handleMessage(event) {
    try {
      const data = JSON.parse(event.data);
      this.log("Received:", data);

      if (data.type === "authentication") {
        this.authenticated = data.status === "success";
        if (this.authenticated) {
          this.emit("authenticated", data);
        } else {
          this.emit("auth_error", data);
        }
        return;
      }

      if (data.type === "ping") {
        this.send({ type: "pong" });
        return;
      }

      this.emit(data.type, data);
    } catch (error) {
      this.log("Message parsing error:", error);
      this.emit("error", { error: "Message parsing failed" });
    }
  }

  handleError(error) {
    this.connecting = false;
    this.log("WebSocket error:", error);
    this.emit("error", error);
  }

  handleClose() {
    this.connecting = false;
    this.authenticated = false;
    this.stopPingInterval();
    this.log("Connection closed");
    this.emit("close");

    if (this.reconnectCount < this.config.reconnectAttempts) {
      this.reconnect();
    } else {
      this.emit("max_reconnects");
    }
  }

  reconnect() {
    this.reconnectCount++;
    this.log(`Reconnecting... Attempt ${this.reconnectCount}`);
    this.emit("reconnecting", { attempt: this.reconnectCount });

    setTimeout(() => {
      this.connect();
    }, this.config.reconnectDelay);
  }

  send(data) {
    if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
      this.log("Cannot send: connection not open");
      return false;
    }

    try {
      this.ws.send(JSON.stringify(data));
      return true;
    } catch (error) {
      this.log("Send error:", error);
      return false;
    }
  }

  authenticate(userType) {
    this.send({
      type: "authentication",
      userType: userType,
    });
  }

  on(event, handler) {
    if (!this.handlers.has(event)) {
      this.handlers.set(event, new Set());
    }
    this.handlers.get(event).add(handler);
  }

  off(event, handler) {
    if (this.handlers.has(event)) {
      if (handler) {
        this.handlers.get(event).delete(handler);
      } else {
        this.handlers.delete(event);
      }
    }
  }

  emit(event, data = null) {
    if (this.handlers.has(event)) {
      this.handlers.get(event).forEach((handler) => {
        try {
          handler(data);
        } catch (error) {
          this.log(`Error in ${event} handler:`, error);
        }
      });
    }
  }

  startPingInterval() {
    this.stopPingInterval();
    this.pingTimer = setInterval(() => {
      if (this.ws.readyState === WebSocket.OPEN) {
        this.send({ type: "ping" });
      }
    }, this.config.pingInterval);
  }

  stopPingInterval() {
    if (this.pingTimer) {
      clearInterval(this.pingTimer);
      this.pingTimer = null;
    }
  }

  close() {
    this.stopPingInterval();
    if (this.ws) {
      this.ws.close();
    }
  }
}
