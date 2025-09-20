class OrderSystem {
  constructor() {
    // Product data
    this.productPrices = {
      1: 170.0, // Pastil
      2: 250.0, // Sushi
      3: 300.0, // Lasagna
    };

    this.productNames = {
      1: "Pastil",
      2: "Sushi",
      3: "Lasagna",
    };

    // Initialize system when DOM is loaded
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.initialize());
    } else {
      this.initialize();
    }
  }

  initialize() {
    // Get required elements
    this.modal = document.getElementById("orderModal");
    this.orderForm = document.getElementById("orderForm");

    // Early return if required elements don't exist
    if (!this.modal || !this.orderForm) {
      console.warn("Order system not initialized: Required elements missing");
      return;
    }

    this.closeBtn = this.modal.querySelector(".close");
    this.submitBtn = document.getElementById("place-order-submit");
    this.quantityInput = document.getElementById("quantity");
    this.deliveryDateInput = document.getElementById("deliveryDate");
    this.productInput = document.getElementById("product");
    this.totalPriceInput = document.getElementById("totalPrice");

    // Set up event listeners
    this.setupEventListeners();
  }

  setupEventListeners() {
    // Order buttons
    document.querySelectorAll(".order-btn").forEach((button) => {
      button.addEventListener("click", (e) => this.handleOrderButtonClick(e));
    });

    // Modal close button
    if (this.closeBtn) {
      this.closeBtn.addEventListener("click", () => this.closeModal());
    }

    // Click outside modal
    window.addEventListener("click", (e) => {
      if (e.target === this.modal) {
        this.closeModal();
      }
    });

    // Quantity changes
    if (this.quantityInput) {
      this.quantityInput.addEventListener("change", (e) =>
        this.handleQuantityChange(e)
      );
    }

    // Form submission
    if (this.orderForm) {
      this.orderForm.addEventListener("submit", (e) => this.handleSubmit(e));
    }
  }

  async handleOrderButtonClick(event) {
    try {
      const loginStatus = await this.checkLoginStatus();

      if (!loginStatus.isLoggedIn) {
        window.location.href = "login.php";
        return;
      }

      const button = event.currentTarget;
      const productId = button.getAttribute("data-product-id");
      const productName =
        this.productNames[productId] ||
        button.parentElement.querySelector("h4")?.textContent;
      const price = this.productPrices[productId];

      if (!productName || !price) {
        throw new Error("Invalid product information");
      }

      this.setDeliveryDateMin();
      this.setFormValues(productName, price, productId);
      this.showModal();
    } catch (error) {
      console.error("Error handling order button click:", error);
      this.showNotification(
        "error",
        "Failed to process order. Please try again."
      );
    }
  }

  setDeliveryDateMin() {
    if (this.deliveryDateInput) {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      this.deliveryDateInput.min = tomorrow.toISOString().split("T")[0];
    }
  }

  setFormValues(productName, price, productId) {
    if (this.productInput) {
      this.productInput.value = productName;
    }
    if (this.totalPriceInput) {
      this.totalPriceInput.value = `₱${price.toFixed(2)}`;
    }
    if (this.orderForm) {
      this.orderForm.setAttribute("data-product-id", productId);
      this.orderForm.setAttribute("data-price", price);
    }
  }

  handleQuantityChange(event) {
    try {
      const price = parseFloat(this.orderForm.getAttribute("data-price"));
      const quantity = parseInt(event.target.value);
      if (!isNaN(price) && !isNaN(quantity)) {
        const totalPrice = price * quantity;
        if (this.totalPriceInput) {
          this.totalPriceInput.value = `₱${totalPrice.toFixed(2)}`;
        }
      }
    } catch (error) {
      console.error("Error updating quantity:", error);
    }
  }

  async handleSubmit(event) {
    event.preventDefault();

    try {
      const loginStatus = await this.checkLoginStatus();
      if (!loginStatus.isLoggedIn) {
        window.location.href = "login.php";
        return;
      }

      const formData = new FormData(this.orderForm);
      const response = await fetch("process_order.php", {
        method: "POST",
        headers: {
          Accept: "application/json",
        },
        body: formData,
      });

      const contentType = response.headers.get("content-type");
      let data;

      if (contentType && contentType.includes("application/json")) {
        data = await response.json();
      } else {
        const text = await response.text();
        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error("Server returned invalid JSON: " + text);
        }
      }

      if (data.success) {
        this.showNotification("success", "Order placed successfully!");
        this.closeModal();
        setTimeout(() => {
          window.location.href = `order_confirmation.php?order_id=${data.orderId}`;
        }, 1500);
      } else {
        throw new Error(data.message || "Failed to place order");
      }
    } catch (error) {
      console.error("Error submitting order:", error);
      this.showNotification(
        "error",
        error.message || "Failed to place order. Please try again."
      );
    }
  }

  async checkLoginStatus() {
    try {
      const response = await fetch("api/check_session.php");
      const contentType = response.headers.get("content-type");

      if (contentType && contentType.includes("application/json")) {
        return await response.json();
      } else {
        const text = await response.text();
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error("Invalid JSON response from server:", text);
          return { isLoggedIn: false };
        }
      }
    } catch (error) {
      console.error("Error checking login status:", error);
      return { isLoggedIn: false };
    }
  }

  showModal() {
    if (this.modal) {
      this.modal.style.display = "block";
    }
  }

  closeModal() {
    if (this.modal) {
      this.modal.style.display = "none";
    }
  }

  showNotification(type, message) {
    if (typeof window.showNotification === "function") {
      window.showNotification(type, message);
    } else {
      alert(message);
    }
  }
}

// Initialize the order system
const orderSystem = new OrderSystem();
