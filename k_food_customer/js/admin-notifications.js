// Admin Notifications Handler
class AdminNotificationsHandler {
  constructor() {
    this.container = document.querySelector(".notifications-container");
    this.list = document.querySelector(".notifications-list");
    this.badge = document.querySelector(".notification-badge");
    this.socket = null;
    this.notifications = [];

    this.initializeWebSocket();
    this.setupEventListeners();
    this.loadNotifications();
  }

  initializeWebSocket() {
    try {
      this.socket = new WebSocket("ws://localhost:8080");

      this.socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.type === "admin_error_notification") {
          this.handleNewNotification(data.data);
        }
      };

      this.socket.onerror = (error) => {
        console.error("WebSocket error:", error);
      };

      this.socket.onclose = () => {
        // Attempt to reconnect after 5 seconds
        setTimeout(() => this.initializeWebSocket(), 5000);
      };
    } catch (e) {
      console.error("Failed to initialize WebSocket:", e);
    }
  }

  setupEventListeners() {
    // Toggle notification details on click
    this.list.addEventListener("click", (e) => {
      const item = e.target.closest(".notification-item");
      if (item) {
        item.classList.toggle("expanded");

        // Mark as read if not already
        if (!item.classList.contains("read")) {
          const notificationId = item.dataset.id;
          this.markAsRead(notificationId);
        }
      }
    });

    // Clear all button
    document.querySelector(".clear-all").addEventListener("click", () => {
      this.clearAllNotifications();
    });
  }

  async loadNotifications() {
    try {
      const response = await fetch("api/get_notifications.php");
      const data = await response.json();

      if (data.success) {
        this.notifications = data.notifications;
        this.renderNotifications();
        this.updateBadge();
      }
    } catch (e) {
      console.error("Failed to load notifications:", e);
    }
  }

  handleNewNotification(notification) {
    // Add to front of array
    this.notifications.unshift(notification);

    // Update UI
    this.renderNotifications();
    this.updateBadge();

    // Show toast notification
    this.showToast(notification);

    // Play sound for urgent notifications
    if (notification.is_urgent) {
      this.playNotificationSound();
    }
  }

  renderNotifications() {
    if (this.notifications.length === 0) {
      this.list.innerHTML = `
                <div class="notifications-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No new notifications</p>
                </div>
            `;
      return;
    }

    this.list.innerHTML = this.notifications
      .map(
        (notification) => `
            <div class="notification-item ${
              notification.is_urgent ? "urgent" : ""
            }"
                 data-id="${notification.id}">
                <div class="notification-header">
                    <span class="notification-type">${notification.type}</span>
                    <span class="notification-time">${
                      notification.timeAgo
                    }</span>
                </div>
                <p class="notification-message">${notification.message}</p>
                <div class="notification-details">
                    <p><strong>Error ID:</strong> ${
                      notification.details.errorId
                    }</p>
                    <p><strong>File:</strong> ${notification.details.file}</p>
                    <p><strong>Line:</strong> ${notification.details.line}</p>
                </div>
            </div>
        `
      )
      .join("");
  }

  async markAsRead(notificationId) {
    try {
      const response = await fetch("api/mark_notification_read.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ notificationId }),
      });

      const data = await response.json();
      if (data.success) {
        // Update local state
        const notification = this.notifications.find(
          (n) => n.id === notificationId
        );
        if (notification) {
          notification.read = true;
        }
        this.updateBadge();
      }
    } catch (e) {
      console.error("Failed to mark notification as read:", e);
    }
  }

  updateBadge() {
    const unreadCount = this.notifications.filter((n) => !n.read).length;
    const hasUrgent = this.notifications.some((n) => !n.read && n.is_urgent);

    if (unreadCount > 0) {
      this.badge.textContent = unreadCount;
      this.badge.className = `badge ${
        hasUrgent ? "badge-danger" : "badge-warning"
      } notification-badge`;
    } else {
      this.badge.textContent = "";
      this.badge.className = "notification-badge";
    }
  }

  showToast(notification) {
    const toast = document.createElement("div");
    toast.className = `toast ${notification.is_urgent ? "toast-urgent" : ""}`;
    toast.innerHTML = `
            <div class="toast-header">
                <strong>${notification.type}</strong>
                <button type="button" class="close" data-dismiss="toast">&times;</button>
            </div>
            <div class="toast-body">
                ${notification.message}
            </div>
        `;

    document.body.appendChild(toast);
    $(toast).toast({ delay: 5000 }).toast("show");

    // Remove from DOM after hiding
    $(toast).on("hidden.bs.toast", () => toast.remove());
  }

  playNotificationSound() {
    const audio = new Audio("/assets/sounds/notification.mp3");
    audio
      .play()
      .catch((e) => console.error("Failed to play notification sound:", e));
  }

  async clearAllNotifications() {
    try {
      const response = await fetch("api/clear_notifications.php", {
        method: "POST",
      });

      const data = await response.json();
      if (data.success) {
        this.notifications = [];
        this.renderNotifications();
        this.updateBadge();
      }
    } catch (e) {
      console.error("Failed to clear notifications:", e);
    }
  }
}

// Initialize when document is ready
document.addEventListener("DOMContentLoaded", () => {
  new AdminNotificationsHandler();
});
