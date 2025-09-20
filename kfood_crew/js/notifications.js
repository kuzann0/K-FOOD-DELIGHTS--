class NotificationSystem {
  constructor() {
    this.container = this.createContainer();
    this.notifications = new Map();
    this.counter = 0;
  }

  createContainer() {
    const container = document.createElement("div");
    container.className = "notification-container";
    document.body.appendChild(container);
    return container;
  }

  createNotification(message, type) {
    const id = `notification-${++this.counter}`;
    const notification = document.createElement("div");
    notification.className = `notification ${type}`;
    notification.id = id;

    const icon = document.createElement("i");
    icon.className = this.getIconClass(type);

    const text = document.createElement("span");
    text.textContent = message;

    const closeBtn = document.createElement("button");
    closeBtn.className = "notification-close";
    closeBtn.innerHTML = "&times;";
    closeBtn.onclick = () => this.remove(id);

    notification.appendChild(icon);
    notification.appendChild(text);
    notification.appendChild(closeBtn);

    return { id, element: notification };
  }

  getIconClass(type) {
    switch (type) {
      case "success":
        return "fas fa-check-circle";
      case "error":
        return "fas fa-exclamation-circle";
      case "warning":
        return "fas fa-exclamation-triangle";
      case "info":
        return "fas fa-info-circle";
      default:
        return "fas fa-bell";
    }
  }

  show(message, type, duration = 5000) {
    const { id, element } = this.createNotification(message, type);
    this.container.appendChild(element);
    this.notifications.set(id, element);

    // Add show class for animation
    setTimeout(() => element.classList.add("show"), 10);

    if (duration > 0) {
      setTimeout(() => this.remove(id), duration);
    }

    return id;
  }

  remove(id) {
    const notification = this.notifications.get(id);
    if (notification) {
      notification.classList.remove("show");
      setTimeout(() => {
        notification.remove();
        this.notifications.delete(id);
      }, 300);
    }
  }

  success(message, duration) {
    return this.show(message, "success", duration);
  }

  error(message, duration) {
    return this.show(message, "error", duration);
  }

  warning(message, duration) {
    return this.show(message, "warning", duration);
  }

  info(message, duration) {
    return this.show(message, "info", duration);
  }
}
