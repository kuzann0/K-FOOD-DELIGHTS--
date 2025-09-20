class NotificationSystem {
  constructor() {
    this.container = this.createContainer();
    this.notifications = new Set();
  }

  createContainer() {
    const container =
      document.getElementById("notification-container") ||
      document.createElement("div");
    container.id = "notification-container";
    container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            font-family: 'Poppins', sans-serif;
        `;
    if (!document.getElementById("notification-container")) {
      document.body.appendChild(container);
    }
    return container;
  }

  show(type, message, duration = 5000) {
    const notification = document.createElement("div");
    const id = Date.now().toString();

    notification.className = `notification ${type}`;
    notification.innerHTML = `
            <div class="notification-content">
                <i class="notification-icon fas ${this.getIcon(type)}"></i>
                <span class="notification-message">${message}</span>
                <button class="notification-close">×</button>
            </div>
            <div class="notification-progress"></div>
        `;

    notification.style.cssText = `
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 8px;
            background: var(--notification-bg, #ffffff);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        `;

    // Set type-specific styles
    this.applyTypeStyles(notification, type);

    // Add to DOM and animate in
    this.container.appendChild(notification);
    this.notifications.add(id);

    // Force reflow
    notification.offsetHeight;
    notification.style.transform = "translateX(0)";

    // Setup progress bar animation
    const progress = notification.querySelector(".notification-progress");
    progress.style.cssText = `
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.5);
            transform-origin: left;
            transform: scaleX(1);
            transition: transform ${duration}ms linear;
        `;

    // Start progress bar animation
    setTimeout(() => (progress.style.transform = "scaleX(0)"), 50);

    // Setup close button
    const closeBtn = notification.querySelector(".notification-close");
    closeBtn.addEventListener("click", () => this.close(notification, id));

    // Auto close after duration
    setTimeout(() => this.close(notification, id), duration);
  }

  close(notification, id) {
    if (!notification || !this.notifications.has(id)) return;

    notification.style.transform = "translateX(120%)";
    this.notifications.delete(id);

    setTimeout(() => {
      if (notification.parentNode === this.container) {
        this.container.removeChild(notification);
      }
    }, 300);
  }

  getIcon(type) {
    const icons = {
      success: "fa-check-circle",
      error: "fa-times-circle",
      warning: "fa-exclamation-circle",
      info: "fa-info-circle",
    };
    return icons[type] || icons.info;
  }

  applyTypeStyles(notification, type) {
    const styles = {
      success: {
        "--notification-bg": "#4caf50",
        color: "#ffffff",
      },
      error: {
        "--notification-bg": "#f44336",
        color: "#ffffff",
      },
      warning: {
        "--notification-bg": "#ff9800",
        color: "#ffffff",
      },
      info: {
        "--notification-bg": "#2196f3",
        color: "#ffffff",
      },
    };

    Object.assign(notification.style, styles[type] || styles.info);
  }

  success(message, duration) {
    this.show("success", message, duration);
  }

  error(message, duration) {
    this.show("error", message, duration);
  }

  warning(message, duration) {
    this.show("warning", message, duration);
  }

  info(message, duration) {
    this.show("info", message, duration);
  }
}
