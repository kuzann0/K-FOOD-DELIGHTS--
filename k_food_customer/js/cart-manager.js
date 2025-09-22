var CartManager = {
  items: [],

  /**
   * Initialize the cart manager
   */
  init: function () {
    this.items = this.loadCart();
    this.bindEvents();
    this.updateCartDisplay();
  },

  /**
   * Load cart data from localStorage
   * @returns {Array} Cart items
   */
  loadCart: function () {
    try {
      return JSON.parse(localStorage.getItem("cartItems")) || [];
    } catch (e) {
      console.error("Error loading cart:", e);
      return [];
    }
  },

  /**
   * Save cart data to localStorage
   */
  saveCart: function () {
    try {
      localStorage.setItem("cartItems", JSON.stringify(this.items));
      this.notifyCartUpdated();
    } catch (e) {
      console.error("Error saving cart:", e);
    }
  },

  /**
   * Add item to cart
   * @param {Object} item Item to add
   */
  addItem: function (item) {
    var existingItem = this.findItem(item.id);
    if (existingItem) {
      existingItem.quantity += item.quantity || 1;
    } else {
      this.items.push(
        Object.assign({}, item, { quantity: item.quantity || 1 })
      );
    }
    this.saveCart();
  },

  /**
   * Remove item from cart
   * @param {number} itemId ID of item to remove
   */
  removeItem: function (itemId) {
    this.items = this.items.filter(function (item) {
      return item.id !== itemId;
    });
    this.saveCart();
  },

  /**
   * Update item quantity
   * @param {number} itemId ID of item to update
   * @param {number} quantity New quantity
   */
  updateQuantity: function (itemId, quantity) {
    var item = this.findItem(itemId);
    if (item) {
      item.quantity = Math.max(0, quantity);
      if (item.quantity === 0) {
        this.removeItem(itemId);
      } else {
        this.saveCart();
      }
    }
  },

  /**
   * Find item in cart
   * @param {number} itemId ID of item to find
   * @returns {Object|null} Found item or null
   */
  findItem: function (itemId) {
    return this.items.find(function (item) {
      return item.id === itemId;
    });
  },

  /**
   * Clear all items from cart
   */
  clearCart: function () {
    this.items = [];
    this.saveCart();
  },

  /**
   * Calculate cart total
   * @returns {number} Cart total
   */
  getTotal: function () {
    return this.items.reduce(function (total, item) {
      return total + item.price * item.quantity;
    }, 0);
  },

  /**
   * Notify cart updated
   */
  notifyCartUpdated: function () {
    document.dispatchEvent(
      new CustomEvent("cartUpdated", {
        detail: {
          items: this.items,
          total: this.getTotal(),
        },
      })
    );
  },

  /**
   * Bind DOM events
   */
  bindEvents: function () {
    var self = this;

    // Update quantities when changed
    document.querySelectorAll(".quantity-input").forEach(function (input) {
      input.addEventListener("change", function () {
        var itemId = parseInt(this.dataset.itemId, 10);
        var quantity = parseInt(this.value, 10);
        self.updateQuantity(itemId, quantity);
      });
    });

    // Remove items when clicked
    document.querySelectorAll(".remove-item").forEach(function (button) {
      button.addEventListener("click", function () {
        var itemId = parseInt(this.dataset.itemId, 10);
        self.removeItem(itemId);
      });
    });
  },

  /**
   * Update cart display in DOM
   */
  updateCartDisplay: function () {
    var cartCount = document.getElementById("cartCount");
    var cartTotal = document.getElementById("cartTotal");

    if (cartCount) {
      cartCount.textContent = this.items.reduce(function (total, item) {
        return total + item.quantity;
      }, 0);
    }

    if (cartTotal) {
      cartTotal.textContent = "₱" + this.getTotal().toFixed(2);
    }

    // Update cart items display if it exists
    var cartItemsContainer = document.getElementById("cartItems");
    if (cartItemsContainer) {
      this.renderCartItems(cartItemsContainer);
    }
  },

  /**
   * Render cart items to container
   * @param {HTMLElement} container Container element
   */
  renderCartItems: function (container) {
    container.innerHTML = this.items.length
      ? this.items
          .map(function (item) {
            return (
              '<div class="cart-item" data-item-id="' +
              item.id +
              '">' +
              '<span class="item-name">' +
              item.name +
              "</span>" +
              '<input type="number" class="quantity-input" data-item-id="' +
              item.id +
              '" value="' +
              item.quantity +
              '" min="1">' +
              '<span class="item-price">₱' +
              (item.price * item.quantity).toFixed(2) +
              "</span>" +
              '<button class="remove-item" data-item-id="' +
              item.id +
              '">Remove</button>' +
              "</div>"
            );
          })
          .join("")
      : "<p>Your cart is empty</p>";
  },
};

// Initialize cart manager when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  window.cartManager = CartManager;
  CartManager.init();
});
