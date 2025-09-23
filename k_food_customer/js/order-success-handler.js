// Order Success Handler
class OrderSuccessHandler {
  constructor() {
    // Find modal element
    this.successModal = document.getElementById("successModal");
    if (!this.successModal) {
      console.error("Success modal element not found!");
      return;
    }
    this.bindEvents();
  }

  bindEvents() {
    try {
      // Listen for order success event
      window.addEventListener("orderSuccess", (e) => {
        this.handleOrderSuccess(e.detail);
      });

      // Close button in success modal
      const closeBtn = this.successModal.querySelector(".close-modal");
      if (closeBtn) {
        closeBtn.addEventListener("click", () => {
          this.hideSuccessModal();
        });
      }

      // View order button
      const viewOrderBtn = this.successModal.querySelector(".view-order-btn");
      if (viewOrderBtn) {
        viewOrderBtn.addEventListener("click", (e) => {
          this.handleViewOrder(e);
        });
      }

      // Close on outside click
      window.addEventListener("click", (e) => {
        if (e.target === this.successModal) {
          this.hideSuccessModal();
        }
      });
    } catch (error) {
      console.error("Error binding events:", error);
    }
  }

  handleOrderSuccess(data) {
    try {
      console.log("Order placed successfully:", data);

      if (!this.successModal) {
        console.error("Success modal not available");
        return;
      }

      // Update order number in modal
      const orderNumber = this.successModal.querySelector(".order-number");
      if (orderNumber) {
        orderNumber.textContent = `Order #${data.orderId}`;
      }

      // Show success notification
      if (
        window.notifications &&
        typeof window.notifications.success === "function"
      ) {
        window.notifications.success("Order placed successfully!");
      }

      // Show success modal
      this.showSuccessModal();

      // Clear cart
      if (
        window.cartManager &&
        typeof window.cartManager.clearCart === "function"
      ) {
        window.cartManager.clearCart();
      }
    } catch (error) {
      console.error("Error handling order success:", error);
    }
  }

  showSuccessModal() {
    try {
      if (!this.successModal) return;

      this.successModal.style.display = "block";
      // Add animation class
      setTimeout(() => {
        this.successModal.classList.add("active");
      }, 10);
    } catch (error) {
      console.error("Error showing success modal:", error);
    }
  }

  hideSuccessModal() {
    try {
      if (!this.successModal) return;

      this.successModal.classList.remove("active");
      setTimeout(() => {
        this.successModal.style.display = "none";
      }, 300);
    } catch (error) {
      console.error("Error hiding success modal:", error);
    }
  }

  handleViewOrder(e) {
    try {
      e.preventDefault();
      if (!this.successModal) return;

      const orderNumberElement =
        this.successModal.querySelector(".order-number");
      if (!orderNumberElement) return;

      const match = orderNumberElement.textContent.match(/\d+/);
      if (!match) return;

      const orderId = match[0];
      window.location.href = `order_confirmation.php?order_id=${orderId}`;
    } catch (error) {
      console.error("Error handling view order:", error);
    }
  }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  window.orderSuccessHandler = new OrderSuccessHandler();
});
