class WebSocketManager {
  constructor(config = {}) {
    // Use WebSocketConfig if available, otherwise use defaults
    const wsConfig =
      typeof WebSocketConfig !== "undefined"
        ? WebSocketConfig.getConfig()
        : {
            url: `${location.protocol === "https:" ? "wss:" : "ws:"}//${
              location.host
            }/websocket`,
            reconnectDelay: 3000,
            maxReconnectAttempts: 5,
            maxQueueSize: 100,
            messageTimeout: 5000,
          };

    this.config = {
      url: wsConfig.url,
      reconnectAttempts: wsConfig.maxReconnectAttempts,
      reconnectInterval: wsConfig.reconnectDelay,
      protocols: wsConfig.protocols || ["kfooddelights-protocol"],
      debug: config.debug || false,
      maxQueueSize: wsConfig.maxQueueSize,
      messageTimeout: wsConfig.messageTimeout,
      ...config,
    };

    this.ws = null;
    this.reconnectCount = 0;
    this.reconnectTimer = null;
    this.handlers = new Map();
    this.connectionPromise = null;
    this.isConnecting = false;
    this.authenticated = false;
    this.messageQueue = [];
    this.pendingMessages = new Map();
    this.consecutiveErrors = 0;

    // Bind methods to preserve context
    this.connect = this.connect.bind(this);
    this.reconnect = this.reconnect.bind(this);
    this.handleOpen = this.handleOpen.bind(this);
    this.handleMessage = this.handleMessage.bind(this);
    this.handleError = this.handleError.bind(this);
    this.handleClose = this.handleClose.bind(this);
  }

  async connect() {
    if (this.isConnecting) {
      return this.connectionPromise;
    }

    this.isConnecting = true;
    this.connectionPromise = new Promise((resolve, reject) => {
      try {
        this.ws = new WebSocket(this.config.url, this.config.protocols);

        this.ws.onopen = async (event) => {
          this.log("WebSocket connection opened, authenticating...");

          try {
            // Authenticate with crew credentials
            const crewId = document.getElementById("crewId")?.value;
            const crewToken = document.getElementById("crewToken")?.value;

            if (crewId && crewToken) {
              this.ws.send(
                JSON.stringify({
                  type: "authenticate",
                  data: {
                    id: crewId,
                    token: crewToken,
                    role: "crew",
                  },
                })
              );
            }

            this.handleOpen(event);
            resolve(this.ws);
          } catch (error) {
            this.handleError(error);
            reject(error);
          }
        };

        this.ws.onmessage = this.handleMessage;
        this.ws.onerror = (error) => {
          this.handleError(error);
          reject(error);
        };
        this.ws.onclose = this.handleClose;

        // Set timeout for connection attempt
        const timeout = setTimeout(() => {
          if (this.ws.readyState !== WebSocket.OPEN) {
            const error = new Error("Connection timeout");
            this.handleError(error);
            reject(error);
          }
        }, 5000);

        // Clear timeout when connected
        this.ws.addEventListener("open", () => clearTimeout(timeout));
      } catch (error) {
        this.handleError(error);
        reject(error);
      }
    });

    try {
      await this.connectionPromise;
      this.log("WebSocket connected successfully");
      this.reconnectCount = 0;

      // Process any queued messages
      while (
        this.messageQueue.length > 0 &&
        this.ws.readyState === WebSocket.OPEN
      ) {
        const message = this.messageQueue.shift();
        this.emit(message.event, message.data);
      }

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
      const types =
        typeof WebSocketMessageTypes !== "undefined"
          ? WebSocketMessageTypes
          : {
              NEW_ORDER: "new_order",
              ORDER_UPDATED: "order_updated",
              ORDER_CANCELLED: "order_cancelled",
              ERROR: "error",
            };

      // Handle system messages first
      switch (message.type) {
        case "authenticate_success":
          this.authenticated = true;
          this.log("Successfully authenticated with WebSocket server");
          break;

        case "authenticate_error":
          this.authenticated = false;
          this.handleError(
            new Error(message.data.message || "Authentication failed")
          );
          break;

        case "ping":
          this.ws.send(JSON.stringify({ type: "pong" }));
          return;

        default:
          // Process business logic messages
          this.dispatchEvent("message", message);

          if (message.type) {
            // Map legacy message types to new ones
            const mappedType = this.mapMessageType(message.type);
            this.dispatchEvent(mappedType, message.data);

            // Also emit status-specific events for order updates
            if (message.type === types.ORDER_UPDATED && message.data?.status) {
              this.dispatchEvent(
                `order_${message.data.status.toLowerCase()}`,
                message.data
              );
            }
          }
      }
    } catch (error) {
      this.handleError(new Error(`Invalid message format: ${error.message}`));
    }
  }

  mapMessageType(type) {
    // Map old message types to new standardized ones
    const typeMap = {
      orderPlaced: WebSocketMessageTypes?.NEW_ORDER || "new_order",
      orderUpdated: WebSocketMessageTypes?.ORDER_UPDATED || "order_updated",
      orderConfirmed:
        WebSocketMessageTypes?.ORDER_CONFIRMED || "order_confirmed",
      orderCancelled:
        WebSocketMessageTypes?.ORDER_CANCELLED || "order_cancelled",
      error: WebSocketMessageTypes?.ERROR || "error",
    };

    return typeMap[type] || type;
  }

  send(message) {
    if (!message.id) {
      message.id = `msg_${Date.now()}_${Math.random()
        .toString(36)
        .substring(2, 9)}`;
    }

    if (this.ws?.readyState === WebSocket.OPEN && this.authenticated) {
      try {
        const messageStr = JSON.stringify(message);
        this.ws.send(messageStr);
        this.trackPendingMessage(message);
      } catch (error) {
        this.queueMessage(message);
        this.handleError(new Error(`Failed to send message: ${error.message}`));
      }
    } else {
      this.queueMessage(message);
    }
  }

  queueMessage(message) {
    if (this.messageQueue.length >= this.config.maxQueueSize) {
      const droppedMessage = this.messageQueue.shift();
      this.handleError(
        {
          message: "Message queue full, dropping oldest message",
          droppedMessage,
        },
        "warning"
      );
    }

    this.messageQueue.push(message);

    // Try to process queue immediately if possible
    if (this.ws?.readyState === WebSocket.OPEN && this.authenticated) {
      this.processQueue();
    }
  }

  processQueue() {
    if (
      !this.ws ||
      this.ws.readyState !== WebSocket.OPEN ||
      !this.authenticated
    ) {
      return;
    }

    while (this.messageQueue.length > 0) {
      const message = this.messageQueue[0];

      try {
        const messageStr = JSON.stringify(message);
        this.ws.send(messageStr);
        this.trackPendingMessage(message);
        this.messageQueue.shift();
      } catch (error) {
        this.handleError(
          new Error(`Failed to process queued message: ${error.message}`)
        );
        break;
      }
    }
  }

  trackPendingMessage(message) {
    this.pendingMessages.set(message.id, {
      message,
      timestamp: Date.now(),
      timeout: setTimeout(() => {
        if (this.pendingMessages.has(message.id)) {
          this.pendingMessages.delete(message.id);
          this.handleError(
            {
              message: "Message acknowledgment timeout",
              messageId: message.id,
              originalMessage: message,
            },
            "warning"
          );

          // Re-queue if retries not exhausted
          if (!message.retries || message.retries < 3) {
            message.retries = (message.retries || 0) + 1;
            this.queueMessage(message);
          }
        }
      }, this.config.messageTimeout),
    });
  }

  handleMessageAck(messageId) {
    const pending = this.pendingMessages.get(messageId);
    if (pending) {
      clearTimeout(pending.timeout);
      this.pendingMessages.delete(messageId);

      // Reset consecutive errors on successful acknowledgment
      this.consecutiveErrors = 0;
    }
  }

  handleError(error, level = "error") {
    const errorMessage =
      error instanceof Error ? error.message : error.toString();
    this.log(`WebSocket ${level}: ${errorMessage}`, level);

    // Track consecutive errors for reconnection logic
    if (level === "error") {
      this.consecutiveErrors = (this.consecutiveErrors || 0) + 1;

      if (this.consecutiveErrors >= this.maxRetries) {
        this.log(
          "Max consecutive errors reached, stopping reconnection attempts",
          "error"
        );
        this.stopReconnecting = true;
        this.dispatchEvent("max_errors_reached", {
          message: "Maximum consecutive errors reached",
          count: this.consecutiveErrors,
        });
      }
    } else {
      // Reset error count on warnings or info messages
      this.consecutiveErrors = 0;
    }

    // Dispatch error event with enhanced details
    this.dispatchEvent("error", {
      message: errorMessage,
      level,
      timestamp: new Date().toISOString(),
      connectionState: this.ws?.readyState,
      authenticated: this.authenticated,
      retryCount: this.retryCount,
      consecutiveErrors: this.consecutiveErrors,
    });
  }

  clearError() {
    this.consecutiveErrors = 0;
    this.stopReconnecting = false;
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
