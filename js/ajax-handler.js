/**
 * AjaxHandler
 * Base class for handling AJAX polling and real-time updates in KfoodDelights
 */
class AjaxHandler {
  constructor(config = {}) {
    // Required configuration
    this.endpoint = config.endpoint;
    this.interval = config.interval || 5000; // Default 5s polling
    this.onSuccess = config.onSuccess || (() => {});
    this.onError = config.onError || console.error;

    // Optional configuration
    this.method = config.method || "GET";
    this.headers = config.headers || {};
    this.body = config.body;
    this.retryAttempts = config.retryAttempts || 3;
    this.retryDelay = config.retryDelay || 1000;
    this.timeout = config.timeout || 10000;

    // Internal state
    this.pollTimer = null;
    this.retryCount = 0;
    this.lastSuccess = null;
    this.isPolling = false;

    // Bind methods
    this.poll = this.poll.bind(this);
    this.startPolling = this.startPolling.bind(this);
    this.stopPolling = this.stopPolling.bind(this);
    this.handleError = this.handleError.bind(this);
  }

  /**
   * Start polling the endpoint
   */
  startPolling() {
    if (this.isPolling) return;

    this.isPolling = true;
    this.poll(); // Initial poll
    this.pollTimer = setInterval(this.poll, this.interval);

    // Reset retry count on successful start
    this.retryCount = 0;
  }

  /**
   * Stop polling the endpoint
   */
  stopPolling() {
    if (this.pollTimer) {
      clearInterval(this.pollTimer);
      this.pollTimer = null;
    }
    this.isPolling = false;
  }

  /**
   * Perform a single poll request
   */
  async poll() {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), this.timeout);

      const response = await fetch(this.endpoint, {
        method: this.method,
        headers: {
          "Content-Type": "application/json",
          ...this.headers,
        },
        body: this.method !== "GET" ? JSON.stringify(this.body) : undefined,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      // Update success metrics
      this.lastSuccess = new Date();
      this.retryCount = 0;

      // Call success handler
      await this.onSuccess(data);
    } catch (error) {
      this.handleError(error);
    }
  }

  /**
   * Handle errors during polling
   */
  handleError(error) {
    // Don't count aborted requests as errors
    if (error.name === "AbortError") {
      console.warn("Request timed out");
      return;
    }

    this.retryCount++;

    if (this.retryCount <= this.retryAttempts) {
      // Exponential backoff for retries
      const delay = this.retryDelay * Math.pow(2, this.retryCount - 1);
      setTimeout(this.poll, delay);
    } else {
      // Stop polling if max retries exceeded
      this.stopPolling();
    }

    // Call error handler
    this.onError(error);
  }

  /**
   * Update polling configuration
   */
  updateConfig(config = {}) {
    // Update configuration
    Object.assign(this, config);

    // Restart polling if interval changed
    if (config.interval && this.isPolling) {
      this.stopPolling();
      this.startPolling();
    }
  }

  /**
   * Get polling status information
   */
  getStatus() {
    return {
      isPolling: this.isPolling,
      lastSuccess: this.lastSuccess,
      retryCount: this.retryCount,
      endpoint: this.endpoint,
      interval: this.interval,
    };
  }
}

/**
 * Specialized handler for order status updates
 */
class OrderStatusHandler extends AjaxHandler {
  constructor(config) {
    super({
      endpoint: "/api/check_order_status.php",
      interval: 5000,
      ...config,
    });
  }
}

/**
 * Specialized handler for kitchen updates
 */
class KitchenStatusHandler extends AjaxHandler {
  constructor(config) {
    super({
      endpoint: "/api/kitchen_status.php",
      interval: 10000,
      ...config,
    });
  }
}

/**
 * Specialized handler for admin metrics
 */
class AdminMetricsHandler extends AjaxHandler {
  constructor(config) {
    super({
      endpoint: "/api/system_metrics.php",
      interval: 30000,
      ...config,
    });
  }
}
