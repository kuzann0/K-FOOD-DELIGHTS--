class WebSocketEventHandler {
  constructor(orderManager) {
    this.orderManager = orderManager;
    this.notifications = window.notifications;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 5000; // 5 seconds
  }

  handleOpen() {
    console.log("WebSocket connected");
    this.resetReconnectAttempts();

    // Clear polling if it was active
    if (this.orderManager.pollInterval) {
      clearInterval(this.orderManager.pollInterval);
      this.orderManager.pollInterval = null;
    }

    // Authenticate as crew member
    this.sendMessage({
      action: "authenticate",
      type: "crew",
    });

    // Request initial orders
    this.sendMessage({
      action: "check_orders",
    });

    this.notifications.success("Connected to order system");
  }

  handleMessage(event) {
    try {
      const data = JSON.parse(event.data);

      switch (data.type) {
        case "authentication":
          this.handleAuthentication(data);
          break;

        case "new_order":
          this.handleNewOrder(data);
          break;

        case "order_update":
          this.handleOrderUpdate(data);
          break;

        case "preparation_update":
          this.handlePreparationUpdate(data);
          break;

        case "error":
          this.handleError(data);
          break;

        default:
          console.warn("Unknown message type:", data.type);
      }
    } catch (e) {
      console.error("Error processing message:", e);
      this.notifications.error("Error processing server message");
    }
  }

  handleError(error) {
    console.error("WebSocket error:", error);
    this.notifications.error("Connection error. Falling back to polling.");
    this.orderManager.startPolling();
  }

  handleClose() {
    console.log("WebSocket connection closed");
    this.notifications.warning("Connection lost. Retrying...");
    this.attemptReconnect();
  }

  handleAuthentication(data) {
    console.log("Authentication:", data.message);
    if (data.status === "success") {
      this.notifications.success("Connected to order system");
    }
  }

  handleNewOrder(data) {
    const order = data.order;

    // Add order to manager's collection
    if (!this.orderManager.orders.has(order.order_id)) {
      this.orderManager.orders.set(order.order_id, order);

      // Show notification
      this.notifications.success("New order received!", {
        body: `Order #${order.order_id} from ${order.customer_name}`,
        icon: "../resources/images/logo.png",
      });

      // Play notification sound
      this.playNotificationSound();

      // Update display
      this.orderManager.renderOrders();
    }
  }

  handleOrderUpdate(data) {
    const order = data.order;

    // Update order in manager's collection
    if (this.orderManager.orders.has(order.order_id)) {
      const existingOrder = this.orderManager.orders.get(order.order_id);
      this.orderManager.orders.set(order.order_id, {
        ...existingOrder,
        ...order,
      });

      // Show notification
      this.notifications.info(
        `Order #${order.order_id} status updated to ${order.status}`
      );

      // Update display
      this.orderManager.renderOrders();
    }
  }

  handlePreparationUpdate(data) {
    const order = data.order;

    // Update order in manager's collection
    if (this.orderManager.orders.has(order.order_id)) {
      const existingOrder = this.orderManager.orders.get(order.order_id);
      this.orderManager.orders.set(order.order_id, {
        ...existingOrder,
        preparation_status: order.preparation_status,
        estimated_completion_time: order.estimated_completion_time,
      });

      // Show notification
      this.notifications.info(
        `Order #${order.order_id} preparation status: ${order.preparation_status}`
      );

      // Update display
      this.orderManager.renderOrders();
    }
  }

  attemptReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      setTimeout(() => {
        this.orderManager.initializeWebSocket();
      }, this.reconnectDelay);

      // Start polling in the meantime
      this.orderManager.startPolling();
    } else {
      this.notifications.error(
        "Could not reconnect to server. Using polling instead."
      );
      this.orderManager.startPolling();
    }
  }

  resetReconnectAttempts() {
    this.reconnectAttempts = 0;
  }

  sendMessage(message) {
    if (
      this.orderManager.ws &&
      this.orderManager.ws.readyState === WebSocket.OPEN
    ) {
      this.orderManager.ws.send(JSON.stringify(message));
      return true;
    }
    return false;
  }

  playNotificationSound() {
    const audio = new Audio("../resources/sounds/notification.mp3");
    audio
      .play()
      .catch((e) => console.error("Failed to play notification sound:", e));
  }
}
