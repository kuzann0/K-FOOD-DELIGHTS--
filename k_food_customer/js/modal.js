// Utility functions for handling modals
const modalUtils = {
  init() {
    this.setupModalCloseBehavior();
    this.setupAccessibility();
  },

  setupModalCloseBehavior() {
    // Close button functionality for all modals
    document.querySelectorAll(".modal .close").forEach((closeBtn) => {
      closeBtn.addEventListener("click", (e) => {
        e.preventDefault();
        const modal = closeBtn.closest(".modal");
        if (modal) {
          this.closeModal(modal);
        }
      });
    });

    // Close on outside click for all modals
    document.querySelectorAll(".modal").forEach((modal) => {
      modal.addEventListener("click", (e) => {
        if (e.target === modal) {
          this.closeModal(modal);
        }
      });
    });

    // Close on Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        const visibleModal = document.querySelector(
          '.modal[style*="display: block"]'
        );
        if (visibleModal) {
          this.closeModal(visibleModal);
        }
      }
    });
  },

  setupAccessibility() {
    // Add proper ARIA attributes to all modals
    document.querySelectorAll(".modal").forEach((modal) => {
      modal.setAttribute("role", "dialog");
      modal.setAttribute("aria-modal", "true");

      // Set aria-label based on modal title if present
      const title = modal.querySelector("h2");
      if (title) {
        modal.setAttribute("aria-labelledby", title.id || "modal-title");
        if (!title.id) {
          title.id = "modal-title";
        }
      }

      // Ensure close buttons are properly labeled
      const closeBtn = modal.querySelector(".close");
      if (closeBtn) {
        closeBtn.setAttribute("aria-label", "Close modal");
      }
    });
  },

  showModal(modal) {
    if (typeof modal === "string") {
      modal = document.getElementById(modal);
    }

    if (modal) {
      modal.style.display = "block";
      document.body.style.overflow = "hidden"; // Prevent background scrolling

      // Set focus to the first focusable element
      const focusable = modal.querySelector(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      if (focusable) {
        focusable.focus();
      }
    }
  },

  closeModal(modal) {
    if (typeof modal === "string") {
      modal = document.getElementById(modal);
    }

    if (modal) {
      modal.style.display = "none";
      document.body.style.overflow = ""; // Restore scrolling
    }
  },
};

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  modalUtils.init();
});

// Export for use in other files
window.modalUtils = modalUtils;
