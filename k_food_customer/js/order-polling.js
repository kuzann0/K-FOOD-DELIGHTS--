/**
 * OrderPolling module for handling order status updates via AJAX
 * ES5-compatible implementation
 */
var OrderPolling = (function () {
  "use strict";

  var STATUS_ENDPOINT = "check_order_status.php";
  var ORDERS_ENDPOINT = "fetch_orders.php";
  var UPDATE_ENDPOINT = "update_order_status.php";

  var activePolls = {};

  /**
   * Creates a new order status poller
   * @param {string} orderId - The order ID to track
   * @param {Function} onUpdate - Callback when order status changes
   * @returns {Object} Polling control methods
   */
  function pollOrderStatus(orderId, onUpdate) {
    return AjaxPolling.create(
      "order_" + orderId,
      {
        interval: 10000, // 10 seconds
        maxAttempts: 3,
      },
      function () {
        return new Promise(function (resolve, reject) {
          AjaxManager.get(STATUS_ENDPOINT, { order_id: orderId })
            .then(function (response) {
              if (response && response.status) {
                onUpdate(response);
                resolve(response);
              } else {
                reject(new Error("Invalid response format"));
              }
            })
            .catch(reject);
        });
      }
    );
  }

  /**
   * Creates a new orders list poller for crew/admin
   * @param {Object} filters - Filters for orders (status, date range, etc.)
   * @param {Function} onUpdate - Callback when orders list changes
   * @returns {Object} Polling control methods
   */
  function pollOrdersList(filters, onUpdate) {
    return AjaxPolling.create(
      "orders_list",
      {
        interval: 5000, // 5 seconds for crew/admin
        maxAttempts: 5,
      },
      function () {
        return new Promise(function (resolve, reject) {
          AjaxManager.get(ORDERS_ENDPOINT, filters)
            .then(function (response) {
              if (response && Array.isArray(response.orders)) {
                onUpdate(response.orders);
                resolve(response);
              } else {
                reject(new Error("Invalid response format"));
              }
            })
            .catch(reject);
        });
      }
    );
  }

  /**
   * Updates an order's status
   * @param {string} orderId - The order ID to update
   * @param {string} newStatus - The new status to set
   * @returns {Promise} Promise resolving with the update result
   */
  function updateOrderStatus(orderId, newStatus) {
    return AjaxManager.post(UPDATE_ENDPOINT, {
      order_id: orderId,
      status: newStatus,
    });
  }

  /**
   * Stops polling for a specific order
   * @param {string} orderId - The order ID to stop polling for
   */
  function stopOrderPolling(orderId) {
    AjaxPolling.stop("order_" + orderId);
  }

  /**
   * Stops polling the orders list
   */
  function stopOrdersListPolling() {
    AjaxPolling.stop("orders_list");
  }

  /**
   * Stops all active polling
   */
  function stopAllPolling() {
    AjaxPolling.stopAll();
  }

  // Public API
  return {
    pollOrderStatus: pollOrderStatus,
    pollOrdersList: pollOrdersList,
    updateOrderStatus: updateOrderStatus,
    stopOrderPolling: stopOrderPolling,
    stopOrdersListPolling: stopOrdersListPolling,
    stopAllPolling: stopAllPolling,
  };
})();
