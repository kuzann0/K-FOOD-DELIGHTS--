class WebSocketManager {
  constructor(config) {
    this.config = {
      port: config.port || 8080,
      host: config.host || "127.0.0.1",
      path: config.path || "/ws",
      reconnectAttempts: config.reconnectAttempts || 5,
      reconnectInterval: config.reconnectInterval || 3000,
      debug: config.debug || false,
      ...config,
    };

    this.ws = null;
    this.reconnectCount = 0;
    this.reconnectTimer = null;
    this.handlers = new Map();
    this.connectionPromise = null;
    this.isConnecting = false;

    // Bind methods to preserve context
    this.connect = this.connect.bind(this);
    this.reconnect = this.reconnect.bind(this);
    this.handleOpen = this.handleOpen.bind(this);
    this.handleMessage = this.handleMessage.bind(this);
    this.handleError = this.handleError.bind(this);
    this.handleClose = this.handleClose.bind(this);
  }

  get connectionUrl() {
    return `ws://${this.config.host}:${this.config.port}${this.config.path}`;
  }

  async connect() {
    if (this.isConnecting) {
      return this.connectionPromise;
    }

    this.isConnecting = true;
    this.connectionPromise = new Promise((resolve, reject) => {
      try {
        this.ws = new WebSocket(this.connectionUrl);

        this.ws.onopen = (event) => {
          this.handleOpen(event);
          resolve(this.ws);
        };

        this.ws.onmessage = this.handleMessage;
        this.ws.onerror = (error) => {
          this.handleError(error);
          reject(error);
        };
        this.ws.onclose = this.handleClose;

        // Set timeout for connection attempt
        setTimeout(() => {
          if (this.ws.readyState !== WebSocket.OPEN) {
            const error = new Error("Connection timeout");
            this.handleError(error);
            reject(error);
          }
        }, 5000);
      } catch (error) {
        this.handleError(error);
        reject(error);
      }
    });

    try {
      await this.connectionPromise;
      this.log("WebSocket connected successfully");
      this.reconnectCount = 0;
      return true;
    } catch (error) {
      this.log("WebSocket connection failed:", error);
      return false;
    } finally {
      this.isConnecting = false;
    }
  }

  on(event, callback) {
    if (!this.handlers.has(event)) {
      this.handlers.set(event, new Set());
    }
    this.handlers.get(event).add(callback);
    return () => this.off(event, callback);
  }

  off(event, callback) {
    if (this.handlers.has(event)) {
      this.handlers.get(event).delete(callback);
    }
  }

  emit(event, data) {
    if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
      this.log("Cannot emit event: WebSocket is not connected");
      return false;
    }

    try {
      this.ws.send(JSON.stringify({ event, data }));
      return true;
    } catch (error) {
      this.handleError(error);
      return false;
    }
  }

  async reconnect() {
    if (this.reconnectCount >= this.config.reconnectAttempts) {
      this.log("Maximum reconnection attempts reached");
      this.dispatchEvent("maxReconnectAttemptsReached");
      return false;
    }

    this.reconnectCount++;
    this.log(
      `Reconnecting... Attempt ${this.reconnectCount}/${this.config.reconnectAttempts}`
    );

    clearTimeout(this.reconnectTimer);
    this.reconnectTimer = setTimeout(async () => {
      const success = await this.connect();
      if (!success && this.reconnectCount < this.config.reconnectAttempts) {
        this.reconnect();
      }
    }, this.config.reconnectInterval);

    return true;
  }

  handleOpen(event) {
    this.log("WebSocket connection opened");
    this.dispatchEvent("open", event);
  }

  handleMessage(event) {
    try {
      const message = JSON.parse(event.data);
      this.dispatchEvent("message", message);

      // Also dispatch specific event type if present
      if (message.type) {
        this.dispatchEvent(message.type, message.data);
      }
    } catch (error) {
      this.handleError(new Error("Invalid message format"));
    }
  }

  handleError(error) {
    this.log("WebSocket error:", error);
    this.dispatchEvent("error", error);
  }

  handleClose(event) {
    this.log("WebSocket connection closed");
    this.dispatchEvent("close", event);

    if (!event.wasClean) {
      this.reconnect();
    }
  }

  dispatchEvent(event, data) {
    if (this.handlers.has(event)) {
      this.handlers.get(event).forEach((callback) => {
        try {
          callback(data);
        } catch (error) {
          console.error(`Error in ${event} handler:`, error);
        }
      });
    }
  }

  log(...args) {
    if (this.config.debug) {
      console.log("[WebSocketManager]", ...args);
    }
  }

  disconnect() {
    if (this.ws) {
      this.ws.close();
    }
    clearTimeout(this.reconnectTimer);
    this.handlers.clear();
  }
}
