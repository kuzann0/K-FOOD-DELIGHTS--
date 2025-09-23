/**
 * SystemMonitor - Handles system monitoring and health check UI updates
 */
class SystemMonitor {
  constructor() {
    this.updateInterval = 30000; // 30 seconds
    this.healthCheckInterval = 60000; // 1 minute
    this.initialized = false;

    this.init();
  }

  init() {
    if (this.initialized) return;

    // Initialize WebSocket connection
    this.initWebSocket();

    // Set up UI update intervals
    this.startUpdateIntervals();

    // Set up event listeners
    this.bindEvents();

    // Initial updates
    this.updateAll();

    this.initialized = true;
  }

  initWebSocket() {
    const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
    const wsUrl = `${protocol}//${window.location.host}/ws`;

    this.ws = new WebSocket(wsUrl);

    this.ws.onopen = () => {
      console.log("Monitor WebSocket connected");
      this.updateHealthCheck("websocketHealth", "pass");
    };

    this.ws.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        this.handleWebSocketMessage(data);
      } catch (error) {
        console.error("Failed to parse WebSocket message:", error);
      }
    };

    this.ws.onerror = (error) => {
      console.error("WebSocket error:", error);
      this.updateHealthCheck("websocketHealth", "fail");
    };

    this.ws.onclose = () => {
      console.log("WebSocket connection closed");
      this.updateHealthCheck("websocketHealth", "fail");
      // Attempt to reconnect after 5 seconds
      setTimeout(() => this.initWebSocket(), 5000);
    };
  }

  startUpdateIntervals() {
    // Regular stats updates
    setInterval(() => this.updateSystemStats(), this.updateInterval);

    // Health checks
    setInterval(() => this.performHealthChecks(), this.healthCheckInterval);
  }

  bindEvents() {
    // Alert controls
    const refreshBtn = document.getElementById("refreshAlerts");
    if (refreshBtn) {
      refreshBtn.addEventListener("click", () => this.updateAlerts());
    }

    const severityFilter = document.getElementById("alertSeverityFilter");
    if (severityFilter) {
      severityFilter.addEventListener("change", () => this.updateAlerts());
    }
  }

  async updateAll() {
    await Promise.all([
      this.updateSystemStats(),
      this.updateAlerts(),
      this.performHealthChecks(),
    ]);
  }

  async updateSystemStats() {
    try {
      const response = await fetch("api/system_stats.php");
      const stats = await response.json();

      if (!stats.success) {
        throw new Error(stats.message || "Failed to fetch system stats");
      }

      // Update system load
      this.updateLoadIndicator(stats.data.load);

      // Update memory usage
      this.updateMemoryIndicator(stats.data.memory);

      // Update error rate
      this.updateErrorRate(stats.data.errorRate);

      // Update response time
      this.updateResponseTime(stats.data.responseTime);

      // Update business metrics
      this.updateBusinessMetrics(stats.data.business);
    } catch (error) {
      console.error("Failed to update system stats:", error);
      this.showError("Failed to update system statistics");
    }
  }

  updateLoadIndicator(load) {
    const loadElement = document.getElementById("systemLoad");
    if (!loadElement) return;

    const valueSpan = loadElement.querySelector(".value");
    const progressBar = loadElement.querySelector(".progress-bar");

    valueSpan.textContent = `${load.current.toFixed(2)}%`;

    // Update progress bar
    const percentage = (load.current / load.max) * 100;
    progressBar.style.width = `${percentage}%`;

    // Set color based on load
    if (percentage > 90) {
      progressBar.className = "progress-bar critical";
    } else if (percentage > 70) {
      progressBar.className = "progress-bar warning";
    } else {
      progressBar.className = "progress-bar normal";
    }
  }

  updateMemoryIndicator(memory) {
    const memElement = document.getElementById("memoryUsage");
    if (!memElement) return;

    const valueSpan = memElement.querySelector(".value");
    const progressBar = memElement.querySelector(".progress-bar");

    const usedGB = memory.used / (1024 * 1024 * 1024);
    const totalGB = memory.total / (1024 * 1024 * 1024);
    const percentage = (memory.used / memory.total) * 100;

    valueSpan.textContent = `${usedGB.toFixed(1)}GB / ${totalGB.toFixed(1)}GB`;
    progressBar.style.width = `${percentage}%`;

    // Set color based on usage
    if (percentage > 90) {
      progressBar.className = "progress-bar critical";
    } else if (percentage > 70) {
      progressBar.className = "progress-bar warning";
    } else {
      progressBar.className = "progress-bar normal";
    }
  }

  updateErrorRate(errorRate) {
    const element = document.getElementById("errorRate");
    if (!element) return;

    const valueSpan = element.querySelector(".value");
    const indicator = element.querySelector(".trend-indicator");

    valueSpan.textContent = `${errorRate.current}/min`;

    // Update trend indicator
    const trend = errorRate.current - errorRate.previous;
    if (trend > 0) {
      indicator.className = "trend-indicator up negative";
    } else if (trend < 0) {
      indicator.className = "trend-indicator down positive";
    } else {
      indicator.className = "trend-indicator stable";
    }
  }

  updateResponseTime(responseTime) {
    const element = document.getElementById("responseTime");
    if (!element) return;

    const valueSpan = element.querySelector(".value");
    const indicator = element.querySelector(".trend-indicator");

    valueSpan.textContent = `${responseTime.average}ms`;

    // Set color based on response time
    if (responseTime.average > 1000) {
      element.className = "stat-value critical";
    } else if (responseTime.average > 500) {
      element.className = "stat-value warning";
    } else {
      element.className = "stat-value normal";
    }
  }

  async updateAlerts() {
    try {
      const severity =
        document.getElementById("alertSeverityFilter")?.value || "all";

      const response = await fetch(
        `api/system_alerts.php?severity=${severity}`
      );
      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Failed to fetch alerts");
      }

      this.renderAlerts(data.alerts);
    } catch (error) {
      console.error("Failed to update alerts:", error);
      this.showError("Failed to update system alerts");
    }
  }

  renderAlerts(alerts) {
    const alertsList = document.getElementById("alertsList");
    if (!alertsList) return;

    if (alerts.length === 0) {
      alertsList.innerHTML =
        '<div class="no-alerts">No alerts to display</div>';
      return;
    }

    alertsList.innerHTML = alerts
      .map(
        (alert) => `
            <div class="alert-item ${alert.severity}">
                <div class="alert-header">
                    <span class="alert-title">${this.escapeHtml(
                      alert.title
                    )}</span>
                    <span class="alert-time">${this.formatTime(
                      alert.created_at
                    )}</span>
                </div>
                <div class="alert-message">${this.escapeHtml(
                  alert.message
                )}</div>
                ${
                  alert.status === "unread"
                    ? `
                    <button class="btn-acknowledge" data-alert-id="${alert.id}">
                        Acknowledge
                    </button>
                `
                    : ""
                }
            </div>
        `
      )
      .join("");

    // Add event listeners for acknowledge buttons
    alertsList.querySelectorAll(".btn-acknowledge").forEach((button) => {
      button.addEventListener("click", () =>
        this.acknowledgeAlert(button.dataset.alertId)
      );
    });
  }

  async acknowledgeAlert(alertId) {
    try {
      const response = await fetch("api/acknowledge_alert.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ alertId }),
      });

      const data = await response.json();

      if (data.success) {
        this.updateAlerts(); // Refresh alerts list
      } else {
        throw new Error(data.message || "Failed to acknowledge alert");
      }
    } catch (error) {
      console.error("Failed to acknowledge alert:", error);
      this.showError("Failed to acknowledge alert");
    }
  }

  async performHealthChecks() {
    try {
      const response = await fetch("api/health_checks.php");
      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Failed to perform health checks");
      }

      Object.entries(data.checks).forEach(([check, status]) => {
        this.updateHealthCheck(check, status);
      });
    } catch (error) {
      console.error("Failed to perform health checks:", error);
      this.showError("Failed to perform system health checks");
    }
  }

  updateHealthCheck(checkId, status) {
    const element = document.getElementById(checkId);
    if (!element) return;

    const statusSpan = element.querySelector(".status");
    if (!statusSpan) return;

    // Remove existing status classes
    element.classList.remove("pass", "warn", "fail");

    // Add new status class
    element.classList.add(status);

    // Update status text
    statusSpan.textContent = status.charAt(0).toUpperCase() + status.slice(1);
  }

  handleWebSocketMessage(data) {
    switch (data.type) {
      case "system_alert":
        this.updateAlerts();
        break;
      case "health_status":
        this.updateHealthCheck(data.check, data.status);
        break;
      case "stats_update":
        this.updateSystemStats();
        break;
    }
  }

  showError(message) {
    // Show error notification
    if (window.notifications?.error) {
      window.notifications.error(message);
    } else {
      console.error(message);
    }
  }

  formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
}

// Initialize system monitor when document is ready
document.addEventListener("DOMContentLoaded", () => {
  window.systemMonitor = new SystemMonitor();
});
