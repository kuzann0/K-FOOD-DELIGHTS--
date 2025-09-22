/**
 * Global notification system for KFood Delights
 * Requires: None
 */

class NotificationSystem {
  constructor(config = {}) {
    this.config = {
      position: config.position || "top-right",
      duration: config.duration || 5000,
      theme: config.theme || "kfood",
      sound: config.sound !== false,
      ...config,
    };

    this.init();
  }

  init() {
    // Create container if not exists
    if (!document.getElementById("kfood-notifications")) {
      const container = document.createElement("div");
      container.id = "kfood-notifications";
      container.className = `notification-container ${this.config.position}`;
      document.body.appendChild(container);

      // Add styles
      this.addStyles();
    }

    // Initialize audio
    if (this.config.sound) {
      this.notificationSound = new Audio("/assets/notification.mp3");
    }
  }

  addStyles() {
    const styles = `
            .notification-container {
                position: fixed;
                z-index: 9999;
                max-width: 400px;
                width: 100%;
                padding: 10px;
            }
            
            .notification-container.top-right {
                top: 20px;
                right: 20px;
            }
            
            .notification-container.top-left {
                top: 20px;
                left: 20px;
            }
            
            .notification {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                margin-bottom: 10px;
                padding: 15px;
                display: flex;
                align-items: flex-start;
                animation: slideIn 0.3s ease-out;
                border-left: 4px solid #ff6b6b;
            }
            
            .notification.success {
                border-left-color: #2ecc71;
            }
            
            .notification.error {
                border-left-color: #e74c3c;
            }
            
            .notification.warning {
                border-left-color: #f1c40f;
            }
            
            .notification-icon {
                margin-right: 12px;
                font-size: 20px;
            }
            
            .notification-content {
                flex: 1;
            }
            
            .notification-title {
                font-weight: 600;
                margin-bottom: 5px;
            }
            
            .notification-message {
                color: #666;
                font-size: 14px;
            }
            
            .notification-close {
                color: #999;
                cursor: pointer;
                font-size: 18px;
                padding: 5px;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;

    const styleSheet = document.createElement("style");
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
  }

  show(title, message = "", type = "info") {
    const container = document.getElementById("kfood-notifications");
    const notification = document.createElement("div");
    notification.className = `notification ${type}`;

    notification.innerHTML = `
            <div class="notification-icon">
                ${this.getIcon(type)}
            </div>
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                ${
                  message
                    ? `<div class="notification-message">${message}</div>`
                    : ""
                }
            </div>
            <div class="notification-close">&times;</div>
        `;

    container.appendChild(notification);

    // Play sound for important notifications
    if (this.config.sound && (type === "error" || type === "success")) {
      this.notificationSound?.play().catch(() => {});
    }

    // Add close handler
    const closeBtn = notification.querySelector(".notification-close");
    closeBtn.addEventListener("click", () => this.dismiss(notification));

    // Auto dismiss
    if (this.config.duration) {
      setTimeout(() => this.dismiss(notification), this.config.duration);
    }

    return notification;
  }

  success(title, message = "") {
    return this.show(title, message, "success");
  }

  error(title, message = "") {
    return this.show(title, message, "error");
  }

  warning(title, message = "") {
    return this.show(title, message, "warning");
  }

  info(title, message = "") {
    return this.show(title, message, "info");
  }

  dismiss(notification) {
    notification.style.animation = "slideOut 0.3s ease-out forwards";
    setTimeout(() => notification.remove(), 300);
  }

  getIcon(type) {
    const icons = {
      success: '<i class="fas fa-check-circle"></i>',
      error: '<i class="fas fa-times-circle"></i>',
      warning: '<i class="fas fa-exclamation-circle"></i>',
      info: '<i class="fas fa-info-circle"></i>',
    };
    return icons[type] || icons.info;
  }
}

// Initialize global notification system
window.notifications = new NotificationSystem({
  position: "top-right",
  duration: 5000,
  sound: true,
});
