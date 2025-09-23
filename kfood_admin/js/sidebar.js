// Sidebar Interaction Handler
document.addEventListener("DOMContentLoaded", function () {
  // Get all nav sections
  const navSections = document.querySelectorAll(".nav-section");

  // Add click listeners to section titles
  navSections.forEach((section) => {
    const title = section.querySelector(".nav-section-title");
    const submenu = section.querySelector(".submenu");

    title.addEventListener("click", () => {
      // Close other open sections
      navSections.forEach((otherSection) => {
        if (
          otherSection !== section &&
          otherSection.classList.contains("active")
        ) {
          otherSection.classList.remove("active");
        }
      });

      // Toggle current section
      section.classList.toggle("active");
    });

    // If this section contains the active page, open it by default
    if (submenu.querySelector(".active")) {
      section.classList.add("active");
    }
  });

  // Handle mobile sidebar
  const sidebar = document.querySelector(".sidebar");
  const content = document.querySelector(".content");

  // Add touch events for mobile
  let touchStartX = 0;
  let touchEndX = 0;

  document.addEventListener(
    "touchstart",
    (e) => {
      touchStartX = e.changedTouches[0].screenX;
    },
    false
  );

  document.addEventListener(
    "touchend",
    (e) => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    },
    false
  );

  function handleSwipe() {
    const SWIPE_THRESHOLD = 50;
    const diff = touchEndX - touchStartX;

    // Swipe right to open
    if (diff > SWIPE_THRESHOLD && touchStartX < 30) {
      sidebar.classList.add("expanded");
    }
    // Swipe left to close
    else if (diff < -SWIPE_THRESHOLD) {
      sidebar.classList.remove("expanded");
    }
  }

  // Close sidebar when clicking outside
  content.addEventListener("click", () => {
    if (window.innerWidth <= 768) {
      sidebar.classList.remove("expanded");
    }
  });

  // Update sidebar state on window resize
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (window.innerWidth > 768) {
        sidebar.classList.remove("expanded");
      }
    }, 250);
  });

  // Handle notifications
  function updateNotifications() {
    // Fetch pending orders count
    fetch(BASE_URL + "/api/get_pending_orders_count.php")
      .then((response) => response.json())
      .then((data) => {
        const pendingOrdersLink = document.querySelector(
          '[href*="pending_orders.php"]'
        );
        if (pendingOrdersLink && data.count > 0) {
          const badge =
            pendingOrdersLink.querySelector(".notification-badge") ||
            document.createElement("span");
          badge.className = "notification-badge";
          badge.textContent = data.count;
          if (!pendingOrdersLink.querySelector(".notification-badge")) {
            pendingOrdersLink.appendChild(badge);
          }
        }
      });

    // Fetch expiring items count
    fetch(BASE_URL + "/api/get_expiring_items_count.php")
      .then((response) => response.json())
      .then((data) => {
        const expirationLink = document.querySelector(
          '[href*="expiration_tracking.php"]'
        );
        if (expirationLink && data.count > 0) {
          const badge =
            expirationLink.querySelector(".notification-badge") ||
            document.createElement("span");
          badge.className = "notification-badge";
          badge.textContent = data.count;
          if (!expirationLink.querySelector(".notification-badge")) {
            expirationLink.appendChild(badge);
          }
        }
      });
  }

  // Update notifications every minute
  updateNotifications();
  setInterval(updateNotifications, 60000);
});
