/**
 * NotificationManager - Handles system notifications and alerts
 * ES5-compatible implementation
 */
var NotificationManager = {
  container: null,
  defaultDuration: 5000, // 5 seconds
  notificationTypes: {
    SUCCESS: "success",
    ERROR: "error",
    WARNING: "warning",
    INFO: "info",
  },

  /**
   * Initialize the notification manager
   */
  init: function () {
    this.ensureContainer();
  },

  /**
   * Ensure notification container exists
   */
  ensureContainer: function () {
    if (!this.container) {
      this.container = document.getElementById("notificationContainer");
      if (!this.container) {
        this.container = document.createElement("div");
        this.container.id = "notificationContainer";
        this.container.className = "notification-container";
        document.body.appendChild(this.container);
      }
    }
  },

  /**
   * Show a notification
   * @param {string} message Message to display
   * @param {string} type Notification type
   * @param {number} [duration] Duration to show notification
   */
  show: function (message, type, duration) {
    var self = this;
    this.ensureContainer();

    var notification = document.createElement("div");
    notification.className =
      "notification notification-" + (type || this.notificationTypes.INFO);
    notification.innerHTML =
      '<div class="notification-content">' +
      message +
      "</div>" +
      '<button class="notification-close">&times;</button>';

    // Add close button handler
    var closeButton = notification.querySelector(".notification-close");
    closeButton.addEventListener("click", function () {
      self.remove(notification);
    });

    // Add to container
    this.container.appendChild(notification);

    // Auto remove after duration
    setTimeout(function () {
      if (notification.parentNode === self.container) {
        self.remove(notification);
      }
    }, duration || this.defaultDuration);

    return notification;
  },

  /**
   * Show success notification
   * @param {string} message Message to display
   * @param {number} [duration] Duration to show notification
   */
  success: function (message, duration) {
    return this.show(message, this.notificationTypes.SUCCESS, duration);
  },

  /**
   * Show error notification
   * @param {string} message Message to display
   * @param {number} [duration] Duration to show notification
   */
  error: function (message, duration) {
    return this.show(message, this.notificationTypes.ERROR, duration);
  },

  /**
   * Show warning notification
   * @param {string} message Message to display
   * @param {number} [duration] Duration to show notification
   */
  warning: function (message, duration) {
    return this.show(message, this.notificationTypes.WARNING, duration);
  },

  /**
   * Show info notification
   * @param {string} message Message to display
   * @param {number} [duration] Duration to show notification
   */
  info: function (message, duration) {
    return this.show(message, this.notificationTypes.INFO, duration);
  },

  /**
   * Remove a notification
   * @param {HTMLElement} notification Notification element to remove
   */
  remove: function (notification) {
    notification.classList.add("notification-hiding");
    setTimeout(function () {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 300); // Match CSS transition duration
  },

  /**
   * Clear all notifications
   */
  clearAll: function () {
    while (this.container.firstChild) {
      this.remove(this.container.firstChild);
    }
  },
};

// Initialize notification manager when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  window.notificationManager = NotificationManager;
  NotificationManager.init();
});
