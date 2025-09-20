// Cart functionality
let cart = {
  items: [],
  total: 0,
  itemsInCart: [],
  defaultQuantity: 1, // Default quantity for new items

  init: function () {
    // Load cart from localStorage first
    this.loadFromLocalStorage();
    // Bind events before attempting to display or load items
    this.bindEvents();
    // Only try to load from server if localStorage is empty
    if (!this.itemsInCart || this.itemsInCart.length === 0) {
      this.loadCartItems();
    } else {
      this.updateCartDisplay();
    }
  },

  loadFromLocalStorage: function () {
    try {
      const savedCart = localStorage.getItem("cart");
      if (savedCart) {
        this.itemsInCart = JSON.parse(savedCart);
      }
    } catch (error) {
      console.error("Error loading cart from localStorage:", error);
      this.itemsInCart = [];
    }
  },

  saveToLocalStorage: function () {
    try {
      localStorage.setItem("cart", JSON.stringify(this.itemsInCart));
    } catch (error) {
      console.error("Error saving cart to localStorage:", error);
    }
  },

  bindEvents: function () {
    // Add to cart button click
    document.querySelectorAll(".add-to-cart-btn").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        const productId = btn.dataset.productId;
        const quantity = 1; // Start with 1 item
        this.addItem(productId, quantity);
      });
    });

    // Cart icon click - show cart modal
    document.getElementById("cart-icon").addEventListener("click", () => {
      this.showCartModal();
    });

    // Close modal when clicking on close button or outside the modal
    const modal = document.getElementById("cart-modal");
    if (modal) {
      // Close button click
      modal.querySelector(".close")?.addEventListener("click", () => {
        this.hideCartModal();
      });

      // Click outside modal
      modal.addEventListener("click", (e) => {
        if (e.target === modal) {
          this.hideCartModal();
        }
      });
    }

    // Event delegation for cart item controls
    document.getElementById("cart-items")?.addEventListener("click", (e) => {
      const button = e.target.closest("button"); // Find closest button if clicked on an icon or span
      if (!button || !button.dataset.id) return;

      // Prevent event bubbling
      e.stopPropagation();

      const itemId = button.dataset.id;
      const isProcessing = button.dataset.processing === "true";

      // Prevent multiple rapid clicks
      if (isProcessing) return;

      // Set processing flag
      button.dataset.processing = "true";

      try {
        if (button.classList.contains("minus")) {
          this.updateItemQuantity(itemId, -1);
        } else if (button.classList.contains("plus")) {
          this.updateItemQuantity(itemId, 1);
        } else if (button.classList.contains("cart-remove-btn")) {
          this.removeItem(itemId);
        }
      } finally {
        // Remove processing flag after a short delay
        setTimeout(() => {
          button.dataset.processing = "false";
        }, 250);
      }
    });
  },

  addItem: function (productId, quantity = 1) {
    const productButton = document.querySelector(
      `.product-button-group[data-product-id="${productId}"]`
    );
    const name = productButton.querySelector("h4").textContent;
    const slideWithPrice = document
      .querySelector(`.slide img[alt="${name}"]`)
      .closest(".slide");
    const price = parseFloat(
      slideWithPrice.querySelector(".price").textContent.replace("₱", "")
    );
    const image = slideWithPrice.querySelector("img").src;
    const existingItem = this.itemsInCart.find((item) => item.id === productId);

    if (existingItem) {
      existingItem.quantity = existingItem.quantity + quantity;
    } else {
      this.itemsInCart.push({
        id: productId,
        name: name,
        price: price,
        image: image,
        quantity: Math.max(1, quantity), // Ensure minimum quantity is 1
      });
    }

    // Save to localStorage after adding item
    this.saveToLocalStorage();
    this.updateCartDisplay();
    this.showCartModal();
    showNotification("Item added to cart");
  },

  updateQuantity: function (cartId, quantity) {
    fetch("cart_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=update&cart_id=${cartId}&quantity=${quantity}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateCartDisplay();
        }
      });
  },

  removeItem: function (cartId) {
    fetch("cart_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=remove&cart_id=${cartId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.updateCartDisplay();
          showNotification("Item removed from cart");
        }
      });
  },

  loadCartItems: function () {
    // Don't load from server if we have items in localStorage
    if (this.itemsInCart && this.itemsInCart.length > 0) {
      this.updateCartDisplay();
      return;
    }

    if (!isLoggedIn()) return;

    fetch("cart_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "action=get",
    })
      .then((response) => response.json())
      .then((data) => {
        if (
          data.success &&
          (!this.itemsInCart || this.itemsInCart.length === 0)
        ) {
          // Only load from server if localStorage is empty
          this.itemsInCart = data.items || [];
          this.saveToLocalStorage();
          this.updateCartDisplay();
        }
      });
  },

  updateCartDisplay: function () {
    const cartCount = document.getElementById("cart-count");
    const cartItems = document.getElementById("cart-items");
    const cartTotal = document.getElementById("cart-total");

    if (cartCount) {
      cartCount.textContent = this.itemsInCart.reduce(
        (sum, item) => sum + item.quantity,
        0
      );
    }

    if (cartItems) {
      cartItems.innerHTML = "";

      if (this.itemsInCart.length === 0) {
        cartItems.innerHTML = '<p class="empty-cart">Your cart is empty</p>';
        return;
      }

      const itemsContainer = document.createElement("div");
      itemsContainer.className = "cart-items-container";

      this.itemsInCart.forEach((item) => {
        const itemElement = document.createElement("div");
        itemElement.className = "cart-item";
        itemElement.innerHTML = `
          <img src="${item.image}" alt="${item.name}">
          <div class="cart-item-details">
            <span class="cart-item-name">${item.name}</span>
            <span class="cart-item-price">₱${item.price.toFixed(2)}</span>
          </div>
          <div class="cart-quantity">
            <button class="quantity-btn minus" data-id="${item.id}">-</button>
            <span>${item.quantity}</span>
            <button class="quantity-btn plus" data-id="${item.id}">+</button>
          </div>
          <button class="cart-remove-btn" data-id="${item.id}">×</button>
        `;

        itemsContainer.appendChild(itemElement);
      });

      cartItems.appendChild(itemsContainer);
    }

    if (cartTotal) {
      const total = this.itemsInCart.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0
      );
      cartTotal.textContent = "₱" + total.toFixed(2);
    }
  },

  showCartModal: function () {
    const modal = document.getElementById("cart-modal");
    if (modal) {
      modal.style.display = "block";
      // Add show class for animation
      setTimeout(() => modal.classList.add("show"), 10);
    }
  },

  hideCartModal: function () {
    const modal = document.getElementById("cart-modal");
    if (modal) {
      // Remove show class to trigger fade out animation
      modal.classList.remove("show");
      // Wait for animation to complete before hiding
      setTimeout(() => {
        modal.style.display = "none";
      }, 300);
    }
  },

  updateItemQuantity: function (itemId, change) {
    const item = this.itemsInCart.find((item) => item.id === itemId);
    if (item) {
      const newQuantity = item.quantity + change;
      if (newQuantity < 1) {
        // If quantity would go below 1, ask if user wants to remove item
        if (confirm("Remove item from cart?")) {
          this.removeItem(itemId);
        }
      } else {
        item.quantity = newQuantity;
        this.updateCartDisplay();
        this.saveToLocalStorage();
        showNotification("Quantity updated");
      }
    }
  },

  removeItem: function (itemId) {
    this.itemsInCart = this.itemsInCart.filter((item) => item.id !== itemId);
    this.updateCartDisplay();
    this.saveToLocalStorage();
    showNotification("Item removed from cart");
  },

  // These functions are defined earlier in the code
  /*saveToLocalStorage: function () {
    try {
      localStorage.setItem("cart", JSON.stringify(this.itemsInCart));
    } catch (error) {
      console.error("Error saving cart to localStorage:", error);
    }
  },

  loadFromLocalStorage: function () {
    try {
      const savedCart = localStorage.getItem("cart");
      if (savedCart) {
        this.itemsInCart = JSON.parse(savedCart);
        this.updateCartDisplay();
      }
    } catch (error) {
      console.error("Error loading cart from localStorage:", error);
      this.itemsInCart = [];
    }
  },*/

  checkout: async function () {
    const loggedIn = await isLoggedIn();
    if (!loggedIn) {
      window.location.href = "login.php";
      return;
    }

    if (this.itemsInCart.length === 0) {
      showNotification("Your cart is empty", "error");
      return;
    }

    // Save current cart to localStorage before redirecting
    this.saveToLocalStorage();

    // Store a flag to indicate checkout is in progress
    sessionStorage.setItem("checkoutInProgress", "true");

    // Redirect to checkout page
    window.location.href = "checkout.php";
  },

  // Clear cart after successful order
  clearCart: function () {
    this.itemsInCart = [];
    this.updateCartDisplay();
    localStorage.removeItem("cartItems");
    showNotification("Cart cleared");
  },
};

// Initialize cart when document is ready
document.addEventListener("DOMContentLoaded", () => {
  cart.init();
  // No need to call loadCart() separately as it's now part of init()
  cart.bindEvents();
});

async function isLoggedIn() {
  try {
    const response = await fetch("api/check_session.php");
    const data = await response.json();
    return data.isLoggedIn;
  } catch (error) {
    console.error("Error checking login status:", error);
    return false;
  }
}

function showLoginPrompt() {
  window.location.href = "login.php";
}

function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.remove();
  }, 3000);
}
