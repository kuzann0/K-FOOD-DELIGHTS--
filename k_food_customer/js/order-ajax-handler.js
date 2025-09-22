/**
 * OrderAjaxHandler - Handles AJAX requests for order submission and status updates
 */
class OrderAjaxHandler {
  constructor() {
    this.baseUrl = window.location.origin;
    this.endpoints = {
      submitOrder: "/k_food_customer/process_order.php",
      checkStatus: "/k_food_customer/check_order_status.php",
    };
  }

  /**
   * Submit an order via AJAX
   * @param {Object} orderData - The complete order data
   * @returns {Promise} - The order submission response
   */
  async submitOrder(orderData) {
    try {
      const response = await window.ajaxHandler.post(
        this.endpoints.submitOrder,
        orderData
      );

      if (response.success) {
        return {
          success: true,
          orderId: response.orderId,
          message: response.message || "Order placed successfully!",
        };
      } else {
        throw new Error(response.message || "Failed to submit order");
      }
    } catch (error) {
      console.error("Order submission failed:", error);
      throw new Error(
        error.message || "Failed to submit order. Please try again."
      );
    }
  }

  /**
   * Check the status of an order
   * @param {string} orderId - The ID of the order to check
   * @returns {Promise} - The order status response
   */
  async checkOrderStatus(orderId) {
    try {
      return await window.ajaxHandler.get(
        `${this.endpoints.checkStatus}?orderId=${orderId}`
      );
    } catch (error) {
      console.error("Status check failed:", error);
      throw error;
    }
  }
}

// Initialize and expose globally
window.orderAjaxHandler = new OrderAjaxHandler();
