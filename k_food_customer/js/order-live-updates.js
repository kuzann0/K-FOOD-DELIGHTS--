// Initialize Socket.IO client
const socket = io("//" + window.location.hostname + ":3000");

// Listen for new orders
socket.on("new-order", (order) => {
  // Play notification sound
  playNotificationSound();

  // Show notification
  showNotification(`New Order #${order.orderNumber}`, {
    body: `Amount: ₱${order.amount.toFixed(2)}`,
    icon: "/assets/logo.png",
  });

  // Add order to the list
  addOrderToList(order);
});

// Listen for order updates
socket.on("order-update", (update) => {
  updateOrderStatus(update.orderId, update.status);
});

function playNotificationSound() {
  const audio = new Audio("/assets/notification.mp3");
  audio.play();
}

function showNotification(title, options) {
  if (!("Notification" in window)) {
    console.log("This browser does not support notifications");
    return;
  }

  if (Notification.permission === "granted") {
    new Notification(title, options);
  } else if (Notification.permission !== "denied") {
    Notification.requestPermission().then((permission) => {
      if (permission === "granted") {
        new Notification(title, options);
      }
    });
  }
}

function addOrderToList(order) {
  const ordersContainer = document.getElementById("ordersContainer");
  if (!ordersContainer) return;

  const orderElement = createOrderElement(order);

  // Add to the beginning of the list
  ordersContainer.insertBefore(orderElement, ordersContainer.firstChild);

  // Highlight new order
  orderElement.classList.add("new-order");
  setTimeout(() => {
    orderElement.classList.remove("new-order");
  }, 5000);
}

function createOrderElement(order) {
  const orderDiv = document.createElement("div");
  orderDiv.className = "order-item";
  orderDiv.dataset.orderId = order.orderId;

  orderDiv.innerHTML = `
        <div class="order-header">
            <h3>Order #${order.orderNumber}</h3>
            <span class="order-time">${new Date(
              order.timestamp
            ).toLocaleTimeString()}</span>
        </div>
        <div class="order-details">
            <div class="customer-info">
                <p><strong>Customer:</strong> ${order.customerName}</p>
                <p><strong>Phone:</strong> ${order.phone}</p>
                <p><strong>Address:</strong> ${order.address}</p>
            </div>
            <div class="order-items">
                ${order.items
                  .map(
                    (item) => `
                    <div class="item">
                        <span>${item.quantity}x ${item.name}</span>
                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `
                  )
                  .join("")}
            </div>
            <div class="order-summary">
                <p><strong>Subtotal:</strong> ₱${order.subtotal.toFixed(2)}</p>
                <p><strong>Delivery Fee:</strong> ₱${order.deliveryFee.toFixed(
                  2
                )}</p>
                <p class="total"><strong>Total:</strong> ₱${order.total.toFixed(
                  2
                )}</p>
            </div>
        </div>
        <div class="order-actions">
            <select class="status-select" onchange="updateOrderStatus('${
              order.orderId
            }', this.value)">
                <option value="pending" ${
                  order.status === "pending" ? "selected" : ""
                }>Pending</option>
                <option value="preparing" ${
                  order.status === "preparing" ? "selected" : ""
                }>Preparing</option>
                <option value="ready" ${
                  order.status === "ready" ? "selected" : ""
                }>Ready for Delivery</option>
                <option value="delivered" ${
                  order.status === "delivered" ? "selected" : ""
                }>Delivered</option>
            </select>
            <button class="view-details-btn" onclick="viewOrderDetails('${
              order.orderId
            }')">View Details</button>
        </div>
    `;

  return orderDiv;
}

function updateOrderStatus(orderId, newStatus) {
  fetch("api/update_order_status.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      orderId: orderId,
      status: newStatus,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update UI
        const orderElement = document.querySelector(
          `[data-order-id="${orderId}"]`
        );
        if (orderElement) {
          const statusSelect = orderElement.querySelector(".status-select");
          if (statusSelect) {
            statusSelect.value = newStatus;
          }
        }

        // Show success message
        showNotification("Status Updated", {
          body: `Order #${data.orderNumber} status updated to ${newStatus}`,
          icon: "/assets/logo.png",
        });
      } else {
        throw new Error(data.message || "Failed to update status");
      }
    })
    .catch((error) => {
      console.error("Error updating order status:", error);
      alert("Failed to update order status: " + error.message);
    });
}

function viewOrderDetails(orderId) {
  window.location.href = `order_details.php?id=${orderId}`;
}

// Request notification permission on page load
document.addEventListener("DOMContentLoaded", () => {
  if ("Notification" in window && Notification.permission !== "granted") {
    Notification.requestPermission();
  }
});
