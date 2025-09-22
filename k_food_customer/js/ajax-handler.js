/**
 * Ajax Handler for K-Food Delights
 * Handles all AJAX requests and responses for the customer module
 */
class AjaxHandler {
  constructor() {
    this.baseUrl = window.location.origin;
    this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  }

  /**
   * Send a POST request
   * @param {string} url - The endpoint URL
   * @param {Object} data - The data to send
   * @returns {Promise} - The response promise
   */
  async post(url, data) {
    try {
      const response = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-Token": this.csrfToken,
        },
        body: JSON.stringify(data),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error("Ajax request failed:", error);
      throw error;
    }
  }

  /**
   * Send a GET request
   * @param {string} url - The endpoint URL
   * @returns {Promise} - The response promise
   */
  async get(url) {
    try {
      const response = await fetch(url, {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-Token": this.csrfToken,
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error("Ajax request failed:", error);
      throw error;
    }
  }
}

// Create global instance
window.ajaxHandler = new AjaxHandler();
