// Logout functionality
document.addEventListener("DOMContentLoaded", function () {
  // Handle logout link clicks
  const logoutLinks = document.querySelectorAll(".logout-link");
  logoutLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      showLogoutModal();
    });
  });

  // Handle click outside modal to close
  document.addEventListener("click", function (e) {
    const modal = document.getElementById("logoutModal");
    if (e.target === modal) {
      hideLogoutModal();
    }
  });

  // Check for temporary messages
  checkForMessages();
});

// Show logout confirmation modal
function showLogoutModal() {
  const modal = document.getElementById("logoutModal");
  if (modal) {
    modal.style.display = "flex";
    modal.classList.add("show");
  } else {
    console.error("Logout modal not found!");
  }
}

// Hide logout confirmation modal
function hideLogoutModal() {
  const modal = document.getElementById("logoutModal");
  if (modal) {
    modal.style.display = "none";
    modal.classList.remove("show");
  } else {
    console.error("Logout modal not found!");
  }
}

// Confirm and process logout
function confirmLogout() {
  try {
    // Hide the modal first
    hideLogoutModal();

    // Save any necessary data before logout
    saveCartItems();

    // Redirect to logout script with timestamp to prevent caching
    window.location.href = "logout.php?t=" + Date.now();
  } catch (error) {
    console.error("Error during logout:", error);
    // Fallback - try direct redirect
    window.location.href = "logout.php";
  }
}

// Save cart items to localStorage before logout
function saveCartItems() {
  const cartItems = document.querySelectorAll(".cart-item");
  if (cartItems.length > 0) {
    const savedCart = Array.from(cartItems).map((item) => {
      return {
        id: item.dataset.id,
        name: item.dataset.name,
        price: item.dataset.price,
        quantity: item.dataset.quantity,
      };
    });
    localStorage.setItem("savedCart", JSON.stringify(savedCart));
  }
}

// Show temporary messages
function showMessage(message, type = "success") {
  const messageContainer = document.getElementById("messageContainer");
  const messageElement = document.createElement("div");
  messageElement.className = `message ${type}`;
  messageElement.innerHTML = `
        <i class="fas fa-${
          type === "success" ? "check-circle" : "exclamation-circle"
        }"></i>
        ${message}
    `;

  messageContainer.appendChild(messageElement);

  // Remove message after 5 seconds
  setTimeout(() => {
    messageElement.remove();
  }, 5000);
}

// Check for temporary messages (e.g., after logout)
function checkForMessages() {
  const urlParams = new URLSearchParams(window.location.search);
  const message = urlParams.get("message");
  const type = urlParams.get("type") || "success";

  if (message) {
    showMessage(decodeURIComponent(message), type);
    // Remove message from URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}
