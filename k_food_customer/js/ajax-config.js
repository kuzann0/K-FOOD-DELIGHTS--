/**
 * AJAX Configuration
 */
const AjaxConfig = {
  customer: {
    baseUrl: "",
    pollingInterval: 10000, // 10 seconds
    endpoints: {
      submitOrder: "/api/ajax/submit_order.php",
      checkStatus: "/api/ajax/check_order_status.php",
    },
  },

  crew: {
    baseUrl: "",
    pollingInterval: 5000, // 5 seconds
    endpoints: {
      fetchOrders: "/api/ajax/fetch_orders.php",
      updateStatus: "/api/ajax/update_order_status.php",
    },
  },

  admin: {
    baseUrl: "",
    pollingInterval: 15000, // 15 seconds
    endpoints: {
      monitorOrders: "/api/ajax/monitor_orders.php",
    },
  },

  /**
   * Get configuration for module type
   */
  getConfig(type = "customer") {
    const config = this[type];
    if (!config) {
      throw new Error(`Invalid module type: ${type}`);
    }
    return { ...config };
  },

  /**
   * Update base URL for all modules
   */
  setBaseUrl(url) {
    ["customer", "crew", "admin"].forEach((type) => {
      this[type].baseUrl = url;
    });
  },
};
