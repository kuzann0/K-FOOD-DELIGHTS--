// Product and Order Management
class KFoodManager {
  constructor() {
    this.baseUrl = "/k_food_customer/api/product_operations.php";
    this.cart = {
      items: [],
      total: 0
    };
    this.loadCart();
  }

  // Fetch products with pagination and filtering
  async getProducts(page = 1, limit = 10, category = null) {
    try {
      let url = `${this.baseUrl}?action=list_products&page=${page}&limit=${limit}`;
      if (category) {
        url += `&category=${category}`;
      }

      const response = await fetch(url);
      const data = await response.json();

      if (data.status === "error") {
        throw new Error(data.message);
      }

      return data;
    } catch (error) {
      console.error("Error fetching products:", error);
      throw error;
    }
  }

  // Process a new order
  async processOrder(items, deliveryAddress, paymentMethod) {
    try {
      const response = await fetch(`${this.baseUrl}?action=process_order`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          items: items,
          delivery_address: deliveryAddress,
          payment_method: paymentMethod,
        }),
      });

      const data = await response.json();

      if (data.status === "error") {
        throw new Error(data.message);
      }

      return data;
    } catch (error) {
      console.error("Error processing order:", error);
      throw error;
    }
  }

  // Check inventory levels (admin/crew only)
  async checkInventory(threshold = 10) {
    try {
      const response = await fetch(
        `${this.baseUrl}?action=check_inventory&threshold=${threshold}`
      );
      const data = await response.json();

      if (data.status === "error") {
        throw new Error(data.message);
      }

      return data;
    } catch (error) {
      console.error("Error checking inventory:", error);
      throw error;
    }
  }
}

// Usage Example
document.addEventListener("DOMContentLoaded", () => {
  const kfoodManager = new KFoodManager();

  // Load products when page loads
  async function loadProducts(page = 1) {
    try {
      const productList = document.getElementById("product-list");
      const pagination = document.getElementById("pagination");

      // Show loading state
      productList.innerHTML = '<div class="loading">Loading products...</div>';

      const result = await kfoodManager.getProducts(page);

      // Clear loading state
      productList.innerHTML = "";

      // Display products
      result.data.forEach((product) => {
        const productCard = document.createElement("div");
        productCard.className = "product-card";
        productCard.innerHTML = `
                    <img src="${
                      product.primary_image || "default-product.jpg"
                    }" alt="${product.product_name}">
                    <h3>${product.product_name}</h3>
                    <p class="price">₱${product.price.toFixed(2)}</p>
                    <button onclick="addToCart(${
                      product.product_id
                    })">Add to Cart</button>
                `;
        productList.appendChild(productCard);
      });

      // Update pagination
      updatePagination(result.pagination);
    } catch (error) {
      console.error("Error:", error);
      showError("Failed to load products. Please try again later.");
    }
  }

  // Handle order submission
  async function submitOrder(event) {
    event.preventDefault();

    try {
      const cart = getCartItems(); // Implement this based on your cart management
      const address = document.getElementById("delivery-address").value;
      const paymentMethod = document.querySelector(
        'input[name="payment"]:checked'
      ).value;

      const result = await kfoodManager.processOrder(
        cart,
        address,
        paymentMethod
      );

      // Show success message
      showSuccess("Order placed successfully! Order ID: " + result.order_id);

      // Clear cart
      clearCart(); // Implement this based on your cart management

      // Redirect to order confirmation
      window.location.href = `/order-confirmation.php?id=${result.order_id}`;
    } catch (error) {
      console.error("Error:", error);
      showError("Failed to process order. Please try again.");
    }
  }

  // For admin/crew: Check inventory
  async function checkInventory() {
    try {
      const result = await kfoodManager.checkInventory();

      const inventoryList = document.getElementById("inventory-list");
      inventoryList.innerHTML = "";

      result.data.forEach((item) => {
        const row = document.createElement("tr");
        row.innerHTML = `
                    <td>${item.product_name}</td>
                    <td>${item.category_name}</td>
                    <td>${item.stock_quantity}</td>
                    <td>${item.supplier_name}</td>
                    <td>
                        <button onclick="restockProduct(${item.product_id})">
                            Restock
                        </button>
                    </td>
                `;
        inventoryList.appendChild(row);
      });
    } catch (error) {
      console.error("Error:", error);
      showError("Failed to load inventory data.");
    }
  }

  // Utility functions
  function showError(message) {
    const alert = document.createElement("div");
    alert.className = "alert alert-danger";
    alert.textContent = message;
    document.body.insertBefore(alert, document.body.firstChild);
    setTimeout(() => alert.remove(), 5000);
  }

  function showSuccess(message) {
    const alert = document.createElement("div");
    alert.className = "alert alert-success";
    alert.textContent = message;
    document.body.insertBefore(alert, document.body.firstChild);
    setTimeout(() => alert.remove(), 5000);
  }

  function updatePagination(pagination) {
    const paginationElement = document.getElementById("pagination");
    paginationElement.innerHTML = "";

    for (let i = 1; i <= pagination.total_pages; i++) {
      const button = document.createElement("button");
      button.textContent = i;
      button.className = i === pagination.current_page ? "active" : "";
      button.onclick = () => loadProducts(i);
      paginationElement.appendChild(button);
    }
  }

  // Cart Management Methods
  loadCart = function() {
    const savedCart = localStorage.getItem('kfoodCart');
    if (savedCart) {
      try {
        const parsedCart = JSON.parse(savedCart);
        this.cart = parsedCart;
        this.updateCartDisplay();
      } catch (e) {
        console.error('Error loading cart:', e);
        this.clearCart();
      }
    }
  };

  saveCart = function() {
    localStorage.setItem('kfoodCart', JSON.stringify(this.cart));
    this.updateCartDisplay();
  };

  clearCart = function() {
    this.cart = {
      items: [],
      total: 0
    };
    this.saveCart();
  };

  addToCart = function(item) {
    const existingItem = this.cart.items.find(i => i.product_id === item.product_id);
    
    if (existingItem) {
      existingItem.quantity += item.quantity;
    } else {
      this.cart.items.push(item);
    }
    
    this.updateCartTotal();
    this.saveCart();
    showSuccess(`Added ${item.quantity} ${item.product_name} to cart`);
  };

  removeFromCart = function(productId) {
    this.cart.items = this.cart.items.filter(item => item.product_id !== productId);
    this.updateCartTotal();
    this.saveCart();
  };

  updateCartQuantity = function(productId, newQuantity) {
    const item = this.cart.items.find(i => i.product_id === productId);
    if (item) {
      if (newQuantity < 1) {
        this.removeFromCart(productId);
      } else {
        item.quantity = newQuantity;
        this.updateCartTotal();
        this.saveCart();
      }
    }
  };

  updateCartTotal = function() {
    this.cart.total = this.cart.items.reduce((sum, item) => 
      sum + (parseFloat(item.price) * item.quantity), 0);
  };

  updateCartDisplay = function() {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
      const totalItems = this.cart.items.reduce((sum, item) => sum + item.quantity, 0);
      cartCount.textContent = totalItems;
      cartCount.style.display = totalItems ? 'block' : 'none';
    }
    
    const cartTotal = document.getElementById('cartTotal');
    if (cartTotal) {
      cartTotal.textContent = `₱${this.cart.total.toFixed(2)}`;
    }
    
    const cartItemsList = document.getElementById('cartItems');
    if (cartItemsList) {
      cartItemsList.innerHTML = this.cart.items.map(item => `
        <div class="cart-item" data-id="${item.product_id}">
          <div class="cart-item-image">
            <img src="${item.primary_image || 'resources/images/default-food.png'}" 
                 alt="${item.product_name}"
                 onerror="this.src='resources/images/default-food.png'">
          </div>
          <div class="cart-item-details">
            <h4>${item.product_name}</h4>
            <div class="cart-item-price">₱${parseFloat(item.price).toFixed(2)}</div>
            <div class="cart-item-controls">
              <button onclick="kfoodManager.updateCartQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
              <span>${item.quantity}</span>
              <button onclick="kfoodManager.updateCartQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
              <button class="remove-item" onclick="kfoodManager.removeFromCart(${item.product_id})">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      `).join('') || '<div class="empty-cart">Your cart is empty</div>';
    }
  };

  proceedToCheckout = async function() {
    if (!this.cart.items.length) {
      showError('Your cart is empty');
      return;
    }
    
    try {
      const sessionResponse = await fetch('api/check_session.php');
      const sessionData = await sessionResponse.json();
      
      if (sessionData.loggedIn) {
        // Save cart to session and redirect to checkout
        const response = await fetch('process_cart.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.cart)
        });
        
        const data = await response.json();
        if (data.success) {
          window.location.href = 'checkout.php';
        } else {
          showError(data.message || 'Error processing cart');
        }
      } else {
        // Save cart and redirect to login
        localStorage.setItem('checkoutPending', 'true');
        window.location.href = 'login.php';
      }
    } catch (error) {
      console.error('Error:', error);
      showError('Failed to process checkout');
    }
  };

  // Initialize page
  if (document.getElementById("product-list")) {
    loadProducts();
  }

  if (document.getElementById("order-form")) {
    document
      .getElementById("order-form")
      .addEventListener("submit", submitOrder);
  }

  if (
    document.getElementById("inventory-list") &&
    (userRole === "administrator" || userRole === "crew")
  ) {
    checkInventory();
  }

  // Initialize checkout button
  const checkoutBtn = document.getElementById('checkoutBtn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => this.proceedToCheckout());
  }
});
