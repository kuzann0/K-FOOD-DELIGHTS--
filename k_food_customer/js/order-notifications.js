// Initialize Pusher
const pusher = new Pusher("YOUR_APP_KEY", {
  cluster: "YOUR_APP_CLUSTER",
  encrypted: true,
});

function initializeOrderNotifications(roleId) {
  // Subscribe to the role-specific channel
  const channel = pusher.subscribe("orders-" + roleId);

  // Listen for new orders
  channel.bind("new-order", function (data) {
    // Play notification sound
    const audio = new Audio("assets/notification.mp3");
    audio.play();

    // Show notification
    showNotification(`New Order #${data.orderNumber}`, {
      body: `Amount: ₱${data.amount.toFixed(2)}`,
      icon: "/assets/icon.png",
    });

    // Update orders list in real-time
    updateOrdersList(data);
  });
}

function showNotification(title, options) {
  // Check if browser supports notifications
  if (!("Notification" in window)) {
    console.log("This browser does not support notifications");
    return;
  }

  // Check if we already have permission
  if (Notification.permission === "granted") {
    new Notification(title, options);
  } else if (Notification.permission !== "denied") {
    // Request permission
    Notification.requestPermission().then(function (permission) {
      if (permission === "granted") {
        new Notification(title, options);
      }
    });
  }
}

function updateOrdersList(orderData) {
  // Get the orders container
  const ordersContainer = document.getElementById("orders-container");
  if (!ordersContainer) return;

  // Create new order element
  const orderElement = document.createElement("div");
  orderElement.className = "order-item new";
  orderElement.innerHTML = `
        <div class="order-header">
            <span class="order-number">#${orderData.orderNumber}</span>
            <span class="order-time">${new Date(
              orderData.timestamp
            ).toLocaleTimeString()}</span>
        </div>
        <div class="order-details">
            <span class="order-amount">₱${orderData.amount.toFixed(2)}</span>
            <button onclick="viewOrder(${
              orderData.orderId
            })" class="view-btn">View Details</button>
        </div>
    `;

  // Add to the top of the list
  ordersContainer.insertBefore(orderElement, ordersContainer.firstChild);

  // Highlight new order briefly
  setTimeout(() => {
    orderElement.classList.remove("new");
  }, 5000);
}

// Initialize when document is ready
document.addEventListener("DOMContentLoaded", function () {
  // Get role ID from the page
  const roleId = document.body.dataset.roleId;
  if (roleId) {
    initializeOrderNotifications(roleId);
  }
});
