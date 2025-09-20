class NotificationSystem {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'success', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;

        // Create icon element
        const icon = document.createElement('i');
        icon.className = `icon fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
        
        // Create message element
        const messageElement = document.createElement('span');
        messageElement.className = 'message';
        messageElement.textContent = message;

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'close-btn';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.addEventListener('click', () => this.dismiss(notification));

        // Assemble notification
        notification.appendChild(icon);
        notification.appendChild(messageElement);
        notification.appendChild(closeBtn);

        // Add to container
        this.container.appendChild(notification);

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => this.dismiss(notification), duration);
        }

        return notification;
    }

    dismiss(notification) {
        if (!notification.isClosing) {
            notification.isClosing = true;
            notification.style.animation = 'slideOut 0.5s ease forwards';
            
            setTimeout(() => {
                if (notification.parentNode === this.container) {
                    this.container.removeChild(notification);
                }
                
                // Remove container if empty
                if (this.container.children.length === 0) {
                    document.body.removeChild(this.container);
                }
            }, 500);
        }
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }
}