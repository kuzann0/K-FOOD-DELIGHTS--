// Real-time order updates and notifications
let notificationSound = new Audio("../resources/sounds/notification.mp3");
let lastCheckTime = Date.now();

// Check for new notifications every 30 seconds
setInterval(checkNewNotifications, 30000);

// Initial check
checkNewNotifications();

function checkNewNotifications() {
  fetch("api/check_notifications.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.notifications.length > 0) {
        handleNewNotifications(data.notifications);
      }
    })
    .catch((error) => console.error("Error checking notifications:", error));
}

function handleNewNotifications(notifications) {
  notifications.forEach((notification) => {
    if (notification.created_at > lastCheckTime) {
      showNotification(notification);
      if (notification.notification_type === "order") {
        notificationSound.play();
        updateOrdersGrid();
      }
    }
  });
  lastCheckTime = Date.now();
}

function showNotification(notification) {
  const notifDiv = document.createElement("div");
  notifDiv.className = "notification-toast";
  notifDiv.innerHTML = `
        <div class="notification-icon">
            <i class="fas ${
              notification.notification_type === "order"
                ? "fa-utensils"
                : "fa-bell"
            }"></i>
        </div>
        <div class="notification-content">
            <p>${notification.message}</p>
            <small>${new Date(
              notification.created_at
            ).toLocaleTimeString()}</small>
        </div>
    `;

  document.body.appendChild(notifDiv);

  // Slide in animation
  setTimeout(() => notifDiv.classList.add("show"), 100);

  // Auto remove after 5 seconds
  setTimeout(() => {
    notifDiv.classList.remove("show");
    setTimeout(() => notifDiv.remove(), 300);
  }, 5000);
}

// Update orders grid
function updateOrdersGrid() {
  const activeFilter =
    document.querySelector(".filter-btn.active").dataset.status;
  loadOrders(activeFilter);
}

// Load orders based on status
function loadOrders(status = "all") {
  fetch(`api/get_orders.php?status=${status}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayOrders(data.orders);
      }
    })
    .catch((error) => console.error("Error loading orders:", error));
}

// Display orders in grid
function displayOrders(orders) {
  const container = document.getElementById("ordersContainer");

  if (!orders || orders.length === 0) {
    container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No orders yet</h3>
                <p>New orders will appear here</p>
            </div>
        `;
    return;
  }

  container.innerHTML = orders
    .map(
      (order) => `
        <div class="order-card ${order.status.toLowerCase()}" data-order-id="${
        order.order_id
      }">
            <div class="order-header">
                <h3>Order #${order.order_number}</h3>
                <span class="status-badge ${order.status.toLowerCase()}">${
        order.status
      }</span>
            </div>
            <div class="order-info">
                <p><i class="fas fa-user"></i> ${order.customer_name}</p>
                <p><i class="fas fa-phone"></i> ${order.contact_number}</p>
                <p><i class="fas fa-map-marker-alt"></i> ${
                  order.delivery_address
                }</p>
            </div>
            <div class="order-footer">
                <span class="order-total">₱${parseFloat(
                  order.total_amount
                ).toFixed(2)}</span>
                <button class="view-details-btn" onclick="viewOrderDetails(${
                  order.order_id
                })">
                    View Details
                </button>
            </div>
        </div>
    `
    )
    .join("");
}

// View order details
function viewOrderDetails(orderId) {
  fetch(`api/get_order_details.php?order_id=${orderId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showOrderModal(data.order);
      }
    })
    .catch((error) => console.error("Error loading order details:", error));
}

// Show order modal
function showOrderModal(order) {
  const modal = document.getElementById("orderModal");
  const content = modal.querySelector(".modal-content");

  content.innerHTML = `
        <div class="modal-header">
            <h2>Order #${order.order_number}</h2>
            <button class="close-modal" onclick="closeOrderModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="order-details">
                <h3>Customer Details</h3>
                <p><strong>Name:</strong> ${order.customer_name}</p>
                <p><strong>Phone:</strong> ${order.contact_number}</p>
                <p><strong>Address:</strong> ${order.delivery_address}</p>
                ${
                  order.special_instructions
                    ? `
                <p><strong>Special Instructions:</strong> ${order.special_instructions}</p>
                `
                    : ""
                }
            </div>
            <div class="order-items">
                <h3>Order Items</h3>
                <div class="items-list">
                    ${order.items
                      .map(
                        (item) => `
                        <div class="item">
                            <span class="item-name">${item.product_name}</span>
                            <span class="item-quantity">x${item.quantity}</span>
                            <span class="item-price">₱${parseFloat(
                              item.price
                            ).toFixed(2)}</span>
                        </div>
                    `
                      )
                      .join("")}
                </div>
            </div>
            <div class="order-summary">
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <span>₱${parseFloat(order.subtotal).toFixed(2)}</span>
                </div>
                <div class="summary-line">
                    <span>Delivery Fee:</span>
                    <span>₱${parseFloat(order.delivery_fee).toFixed(2)}</span>
                </div>
                ${
                  order.discount_amount > 0
                    ? `
                <div class="summary-line discount">
                    <span>Discount:</span>
                    <span>-₱${parseFloat(order.discount_amount).toFixed(
                      2
                    )}</span>
                </div>
                `
                    : ""
                }
                <div class="summary-line total">
                    <span>Total:</span>
                    <span>₱${parseFloat(order.total_amount).toFixed(2)}</span>
                </div>
            </div>
            <div class="order-actions">
                ${getOrderActions(order)}
            </div>
        </div>
    `;

  modal.classList.add("active");
}

// Get order action buttons based on status
function getOrderActions(order) {
  switch (order.status.toLowerCase()) {
    case "pending":
      return `
                <button class="action-btn accept" onclick="updateOrderStatus(${order.order_id}, 'Preparing')">
                    Accept & Start Preparing
                </button>
                <button class="action-btn reject" onclick="updateOrderStatus(${order.order_id}, 'Cancelled')">
                    Reject Order
                </button>
            `;
    case "preparing":
      return `
                <button class="action-btn ready" onclick="updateOrderStatus(${order.order_id}, 'Ready')">
                    Mark as Ready
                </button>
            `;
    case "ready":
      return `
                <button class="action-btn deliver" onclick="updateOrderStatus(${order.order_id}, 'Delivered')">
                    Mark as Delivered
                </button>
            `;
    default:
      return "";
  }
}

// Update order status
function updateOrderStatus(orderId, newStatus) {
  if (!confirm(`Are you sure you want to mark this order as ${newStatus}?`)) {
    return;
  }

  fetch("api/update_order_status.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      order_id: orderId,
      status: newStatus,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification({
          notification_type: "system",
          message: `Order status updated to ${newStatus}`,
          created_at: new Date().toISOString(),
        });
        closeOrderModal();
        updateOrdersGrid();
      }
    })
    .catch((error) => console.error("Error updating order status:", error));
}

// Close order modal
function closeOrderModal() {
  const modal = document.getElementById("orderModal");
  modal.classList.remove("active");
}
