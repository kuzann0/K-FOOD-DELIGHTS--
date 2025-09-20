// WebSocket configuration for KfoodDelights
const WebSocketConfig = {
  // Default configuration
  config: {
    protocol: window.location.protocol === "https:" ? "wss:" : "ws:",
    host: window.location.host, // Use full host including port if specified
    path: "/websocket", // Changed to match server endpoint
    reconnectDelay: 5000,
    maxReconnectAttempts: 5,
    fallbackPollInterval: 10000, // For AJAX fallback
  },

  // Get WebSocket URL
  getUrl() {
    const { protocol, host, path } = this.config;
    // Ensure no double slashes in path
    const cleanPath = path.startsWith("/") ? path : `/${path}`;
    return `${protocol}//${host}${cleanPath}`;
  },

  // Get complete configuration
  getConfig() {
    return {
      url: this.getUrl(),
      reconnectDelay: this.config.reconnectDelay,
      maxReconnectAttempts: this.config.maxReconnectAttempts,
      fallbackPollInterval: this.config.fallbackPollInterval,
      protocols: ["kfooddelights-protocol"],
    };
  },

  // Update configuration
  updateConfig(newConfig) {
    this.config = {
      ...this.config,
      ...newConfig,
    };
  },

  // Test connection
  testConnection() {
    return new Promise((resolve, reject) => {
      try {
        const ws = new WebSocket(this.getUrl());
        const timeout = setTimeout(() => {
          ws.close();
          reject(new Error("Connection test timeout"));
        }, 5000);

        ws.onopen = () => {
          clearTimeout(timeout);
          ws.close();
          resolve(true);
        };

        ws.onerror = (error) => {
          clearTimeout(timeout);
          reject(new Error(`Connection test failed: ${error.message}`));
        };
      } catch (error) {
        reject(error);
      }
    });
  },
};

// Message types enum
const WebSocketMessageTypes = {
  // Order Management
  NEW_ORDER: "new_order",
  ORDER_UPDATED: "order_updated",
  ORDER_CONFIRMED: "order_confirmed",
  ORDER_CANCELLED: "order_cancelled",

  // Status Updates
  STATUS_UPDATE: "status_update",
  PREPARING: "preparing",
  READY: "ready",
  DELIVERING: "delivering",
  DELIVERED: "delivered",

  // System Messages
  ERROR: "error",
  WELCOME: "welcome",
  PING: "ping",
  PONG: "pong",

  // Notifications
  CUSTOMER_NOTIFICATION: "customer_notification",
  CREW_NOTIFICATION: "crew_notification",
};

// Event handlers enum
const WebSocketEvents = {
  CONNECTING: "connecting",
  CONNECTED: "connected",
  DISCONNECTED: "disconnected",
  RECONNECTING: "reconnecting",
  MESSAGE_RECEIVED: "message_received",
  MESSAGE_SENT: "message_sent",
  ERROR: "error",
  TIMEOUT: "timeout",
};

// Export configuration
window.WebSocketConfig = WebSocketConfig;
window.WebSocketMessageTypes = WebSocketMessageTypes;
window.WebSocketEvents = WebSocketEvents;
