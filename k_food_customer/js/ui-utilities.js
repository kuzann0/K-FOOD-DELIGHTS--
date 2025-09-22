/**
 * UIUtilities - Core UI functionality for KFood Delights
 */
var UIUtilities = {
  /**
   * Show a confirmation modal dialog
   * @param {Object} options Modal options
   * @param {string} [options.title="Confirm"] Modal title
   * @param {string} [options.message=""] Modal message/content
   * @param {string} [options.type="default"] Modal type (default, warning, error, success)
   * @param {string} [options.confirmText="Confirm"] Text for confirm button
   * @param {string} [options.cancelText="Cancel"] Text for cancel button
   * @param {Function} [options.onConfirm=null] Callback when confirmed
   * @param {Function} [options.onCancel=null] Callback when cancelled
   */
  showConfirmationModal: function (options) {
    options = options || {};
    var title = options.title || "Confirm";
    var message = options.message || "";
    var type = options.type || "default";
    var confirmText = options.confirmText || "Confirm";
    var cancelText = options.cancelText || "Cancel";
    var onConfirm = options.onConfirm || function () {};
    var onCancel = options.onCancel || function () {};

    var modal = document.createElement("div");
    modal.className = "kfood-modal";
    modal.innerHTML =
      '<div class="modal-content ' +
      type +
      '">' +
      '<div class="modal-header">' +
      "<h2>" +
      title +
      "</h2>" +
      '<button class="modal-close">&times;</button>' +
      "</div>" +
      '<div class="modal-body">' +
      message +
      "</div>" +
      '<div class="modal-footer">' +
      '<button class="btn-cancel">' +
      cancelText +
      "</button>" +
      '<button class="btn-confirm">' +
      confirmText +
      "</button>" +
      "</div>" +
      "</div>";

    document.body.appendChild(modal);
    document.body.classList.add("modal-open");

    var closeBtn = modal.querySelector(".modal-close");
    var confirmBtn = modal.querySelector(".btn-confirm");
    var cancelBtn = modal.querySelector(".btn-cancel");

    function closeModal() {
      modal.remove();
      document.body.classList.remove("modal-open");
    }

    closeBtn.onclick = function () {
      onCancel();
      closeModal();
    };

    confirmBtn.onclick = function () {
      onConfirm();
      closeModal();
    };

    cancelBtn.onclick = function () {
      onCancel();
      closeModal();
    };

    modal.onclick = function (e) {
      if (e.target === modal) {
        onCancel();
        closeModal();
      }
    };

    return modal;
  },

  /**
   * Show a loading overlay
   * @param {string} [message="Loading..."] Loading message to display
   */
  showLoadingOverlay: function (message) {
    message = message || "Loading...";
    var overlay = document.createElement("div");
    overlay.className = "loading-overlay";
    overlay.innerHTML =
      '<div class="loading-spinner"></div>' +
      '<div class="loading-message">' +
      message +
      "</div>";

    document.body.appendChild(overlay);
    document.body.classList.add("loading");

    return {
      update: function (newMessage) {
        overlay.querySelector(".loading-message").textContent = newMessage;
      },
      hide: function () {
        overlay.remove();
        document.body.classList.remove("loading");
      },
    };
  },

  /**
   * Show a toast notification
   * @param {string} message Toast message
   * @param {string} [type="info"] Toast type (info, success, warning, error)
   * @param {number} [duration=3000] Duration in milliseconds
   */
  toast: function (message, type, duration) {
    type = type || "info";
    duration = duration || 3000;

    var toast = document.createElement("div");
    toast.className = "kfood-toast toast-" + type;
    toast.innerHTML =
      '<div class="toast-content">' +
      '<i class="toast-icon fas ' +
      this.getToastIcon(type) +
      '"></i>' +
      '<div class="toast-message">' +
      message +
      "</div>" +
      "</div>";

    var container =
      document.querySelector(".toast-container") || this.createToastContainer();
    container.appendChild(toast);

    toast.offsetHeight; // Force reflow for animation
    toast.classList.add("show");

    setTimeout(function () {
      toast.classList.remove("show");
      setTimeout(function () {
        toast.remove();
      }, 300);
    }, duration);

    return toast;
  },

  /**
   * Get the appropriate Font Awesome icon for toast type
   * @private
   */
  getToastIcon: function (type) {
    switch (type) {
      case "success":
        return "fa-check-circle";
      case "error":
        return "fa-exclamation-circle";
      case "warning":
        return "fa-exclamation-triangle";
      default:
        return "fa-info-circle";
    }
  },

  /**
   * Create toast container if it doesn't exist
   * @private
   */
  createToastContainer: function () {
    var container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
    return container;
  },

  /**
   * Convenience methods for different toast types
   */
  showError: function (message) {
    return this.toast(message, "error");
  },

  showSuccess: function (message) {
    return this.toast(message, "success");
  },

  showWarning: function (message) {
    return this.toast(message, "warning");
  },

  showInfo: function (message) {
    return this.toast(message, "info");
  },

  /**
   * Validate a form
   * @param {HTMLFormElement} form Form element to validate
   * @param {Object} [options] Validation options
   * @param {boolean} [options.showErrors=true] Show error messages
   * @param {boolean} [options.scrollToError=true] Scroll to first error
   * @param {string} [options.errorClass="invalid"] CSS class for invalid fields
   */
  validateForm: function (form, options) {
    options = options || {};
    var showErrors = options.showErrors !== false;
    var scrollToError = options.scrollToError !== false;
    var errorClass = options.errorClass || "invalid";

    if (!(form instanceof HTMLFormElement)) {
      throw new Error("Invalid form element provided");
    }

    var errors = [];
    var firstErrorElement = null;

    // Clear previous errors
    var previousErrors = form.querySelectorAll("." + errorClass);
    for (var i = 0; i < previousErrors.length; i++) {
      previousErrors[i].classList.remove(errorClass);
    }

    var errorMessages = form.querySelectorAll(".error-message");
    for (var i = 0; i < errorMessages.length; i++) {
      errorMessages[i].remove();
    }

    // Validate each form element
    var elements = form.elements;
    for (var i = 0; i < elements.length; i++) {
      var element = elements[i];
      if (!element.name || element.disabled) continue;

      var value = element.value.trim();
      var required = element.hasAttribute("required");
      var pattern = element.pattern;
      var type = element.type;
      var min = element.min;
      var max = element.max;
      var minLength = element.minLength;
      var maxLength = element.maxLength;

      var error = null;

      // Required field validation
      if (required && !value) {
        error = "This field is required";
      }
      // Pattern validation
      else if (pattern && value && !new RegExp(pattern).test(value)) {
        error = element.title || "Please match the requested format";
      }
      // Type validation
      else if (
        type === "email" &&
        value &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
      ) {
        error = "Please enter a valid email address";
      } else if (type === "tel" && value && !/^\+?[\d\s-]{10,}$/.test(value)) {
        error = "Please enter a valid phone number";
      }
      // Range validation
      else if ((type === "number" || type === "range") && value) {
        var numValue = parseFloat(value);
        if (min && numValue < parseFloat(min)) {
          error = "Value must be greater than or equal to " + min;
        }
        if (max && numValue > parseFloat(max)) {
          error = "Value must be less than or equal to " + max;
        }
      }
      // Length validation
      else if (minLength && value.length < parseInt(minLength)) {
        error = "Please enter at least " + minLength + " characters";
      } else if (maxLength && value.length > parseInt(maxLength)) {
        error = "Please enter no more than " + maxLength + " characters";
      }

      if (error) {
        errors.push({ element: element, message: error });
        if (!firstErrorElement) {
          firstErrorElement = element;
        }

        if (showErrors) {
          element.classList.add(errorClass);
          var errorElement = document.createElement("div");
          errorElement.className = "error-message";
          errorElement.textContent = error;
          element.parentNode.appendChild(errorElement);
        }
      }
    }

    if (scrollToError && firstErrorElement) {
      firstErrorElement.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    return {
      valid: errors.length === 0,
      errors: errors,
      firstError: firstErrorElement,
    };
  },

  /**
   * Initialize UI utilities - adds required styles
   */
  init: function () {
    var style = document.createElement("style");
    style.textContent = this.getStyles();
    document.head.appendChild(style);
  },

  /**
   * Get required CSS styles
   * @private
   */
  getStyles: function () {
    return `
            .kfood-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }

            .kfood-modal .modal-content {
                background: white;
                border-radius: 8px;
                max-width: 500px;
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .kfood-modal .modal-header {
                padding: 15px 20px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .kfood-modal .modal-body {
                padding: 20px;
            }

            .kfood-modal .modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #eee;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }

            .kfood-modal .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                color: #666;
            }

            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }

            .loading-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #ff6666;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            .loading-message {
                margin-top: 10px;
                color: #333;
                font-size: 16px;
            }

            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1001;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .kfood-toast {
                background: white;
                border-radius: 4px;
                padding: 12px 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                gap: 10px;
                transform: translateX(120%);
                transition: transform 0.3s ease;
            }

            .kfood-toast.show {
                transform: translateX(0);
            }

            .kfood-toast.toast-success {
                border-left: 4px solid #28a745;
            }

            .kfood-toast.toast-error {
                border-left: 4px solid #dc3545;
            }

            .kfood-toast.toast-warning {
                border-left: 4px solid #ffc107;
            }

            .kfood-toast.toast-info {
                border-left: 4px solid #17a2b8;
            }

            .toast-icon {
                font-size: 18px;
            }

            .toast-success .toast-icon {
                color: #28a745;
            }

            .toast-error .toast-icon {
                color: #dc3545;
            }

            .toast-warning .toast-icon {
                color: #ffc107;
            }

            .toast-info .toast-icon {
                color: #17a2b8;
            }

            .error-message {
                color: #dc3545;
                font-size: 12px;
                margin-top: 4px;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            @media (max-width: 768px) {
                .toast-container {
                    top: auto;
                    bottom: 20px;
                    left: 20px;
                    right: 20px;
                }

                .kfood-toast {
                    width: 100%;
                }
            }
        `;
  },
};

// Initialize UIUtilities when loaded
document.addEventListener("DOMContentLoaded", function () {
  UIUtilities.init();
});
