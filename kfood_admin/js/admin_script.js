// Admin Panel Main JavaScript

// Dashboard State
const dashboardState = {
  orders: [],
  pagination: {
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 1,
  },
  filters: {
    search: "",
    status: "all",
    dateRange: "today",
  }
};

// Application State
const appState = {
  currentPage: "dashboard",
  currentUser: null,
  notifications: {
    unread: 0,
    items: [],
  },
  search: {
    results: null,
    isSearching: false,
  },
  pageData: {
    orders: [],
    products: [],
    customers: [],
    inventory: [],
  },
  pagination: {
    currentPage: 1,
    itemsPerPage: 10,
    totalPages: 1,
  },
  filters: {
    search: "",
    status: "all",
    dateRange: "today",
    category: "all",
  },
  theme: localStorage.getItem("admin_theme") || "light",
};

// DOM Ready
document.addEventListener("DOMContentLoaded", function () {
  // User roles navigation temporarily disabled
  /* 
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
      link.addEventListener('click', function(e) {
          if (this.textContent.trim() === 'User Roles') {
              e.preventDefault();
              showUserRolesSection();
          }
      });
  });
  */
  // Apply saved theme
  applyTheme(appState.theme);

  // Initialize all components
  initializeComponents();

  // Set up global event listeners
  setupGlobalListeners();

  // Handle page-specific initialization
  initializeCurrentPage();
});

// Component Initialization
function initializeComponents() {
  // Initialize sidebar
  initSidebar();

  // Initialize header components
  initHeader();

  // Initialize notifications
  initNotifications();

  // Initialize search
  initSearch();

  // Initialize theme switcher
  initThemeSwitcher();
}

// Global Event Listeners
function setupGlobalListeners() {
  // Handle keyboard shortcuts
  document.addEventListener("keydown", handleKeyboardShortcuts);

  // Handle click outside dropdowns
  document.addEventListener("click", handleClickOutside);

  // Handle online/offline status
  window.addEventListener("online", handleOnlineStatus);
  window.addEventListener("offline", handleOnlineStatus);

  // Handle visibility changes
  document.addEventListener("visibilitychange", handleVisibilityChange);
}

// Page-specific initialization
function initializeCurrentPage() {
  const currentPage = document.body.dataset.page;

  switch (currentPage) {
    case "dashboard":
      initDashboard();
      break;
    case "inventory":
      initInventory();
      break;
    case "orders":
      initOrders();
      break;
    case "users":
      initUsers();
      break;
    case "reports":
      initReports();
      break;
  }
}

// Theme Management
function applyTheme(theme) {
  document.documentElement.setAttribute("data-theme", theme);
  localStorage.setItem("admin_theme", theme);
  appState.theme = theme;
}

function initThemeSwitcher() {
  const themeSwitcher = document.getElementById("themeSwitcher");
  if (!themeSwitcher) return;

  themeSwitcher.addEventListener("change", (e) => {
    const newTheme = e.target.checked ? "dark" : "light";
    applyTheme(newTheme);
  });

  // Set initial state
  themeSwitcher.checked = appState.theme === "dark";
}

// Utility Functions
const utils = {
  formatCurrency: (amount) => {
    return new Intl.NumberFormat("en-PH", {
      style: "currency",
      currency: "PHP",
    }).format(amount);
  },

  formatDate: (date) => {
    return new Intl.DateTimeFormat("en-PH", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }).format(new Date(date));
  },

  formatNumber: (number) => {
    return new Intl.NumberFormat("en").format(number);
  },

  truncateText: (text, length = 50) => {
    if (text.length <= length) return text;
    return text.substring(0, length) + "...";
  },

  debounce: (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  throttle: (func, limit) => {
    let inThrottle;
    return function executedFunction(...args) {
      if (!inThrottle) {
        func(...args);
        inThrottle = true;
        setTimeout(() => (inThrottle = false), limit);
      }
    };
  },
};

// Keyboard Shortcuts
const shortcuts = {
  KeyS: {
    ctrl: true,
    action: () => document.getElementById("globalSearch").focus(),
    description: "Focus search",
  },
  KeyN: {
    ctrl: true,
    action: () => document.getElementById("notificationBtn").click(),
    description: "Toggle notifications",
  },
  KeyD: {
    ctrl: true,
    action: () => (window.location.href = "dashboard.php"),
    description: "Go to dashboard",
  },
  KeyI: {
    ctrl: true,
    action: () => (window.location.href = "modules/inventory.php"),
    description: "Go to inventory",
  },
  KeyO: {
    ctrl: true,
    action: () => (window.location.href = "modules/orders.php"),
    description: "Go to orders",
  },
  KeyR: {
    ctrl: true,
    action: () => (window.location.href = "modules/reports.php"),
    description: "Go to reports",
  },
  KeyT: {
    ctrl: true,
    action: () => document.getElementById("themeSwitcher").click(),
    description: "Toggle theme",
  },
  KeyH: {
    ctrl: true,
    action: () => showShortcutsHelp(),
    description: "Show this help",
  },
};

function handleKeyboardShortcuts(e) {
  // Ignore shortcuts when typing in input fields, textareas, or contenteditable elements
  if (e.target.matches("input, textarea") || e.target.isContentEditable || e.target.closest('form')) return;

  const shortcut = shortcuts[e.code];
  if (shortcut && (!shortcut.ctrl || (shortcut.ctrl && e.ctrlKey))) {
    e.preventDefault();
    shortcut.action();
  }
}

function showShortcutsHelp() {
  const modal = document.createElement("div");
  modal.className = "modal shortcuts-help";
  modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Keyboard Shortcuts</h2>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <table class="shortcuts-table">
                    <thead>
                        <tr>
                            <th>Shortcut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${Object.entries(shortcuts)
                          .map(
                            ([key, { ctrl, description }]) => `
                            <tr>
                                <td><kbd>${ctrl ? "Ctrl" : ""}${
                              ctrl ? " + " : ""
                            }${key.replace("Key", "")}</kbd></td>
                                <td>${description}</td>
                            </tr>
                        `
                          )
                          .join("")}
                    </tbody>
                </table>
            </div>
        </div>
    `;

  document.body.appendChild(modal);

  const closeBtn = modal.querySelector(".close-modal");
  closeBtn.addEventListener("click", () => modal.remove());

  modal.addEventListener("click", (e) => {
    if (e.target === modal) modal.remove();
  });
}

// Initialize Sidebar
function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    // Add event listeners for sidebar toggle on mobile
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Add active class to current page link
    const currentPage = document.body.dataset.page;
    if (currentPage) {
        const activeLink = sidebar.querySelector(`[data-page="${currentPage}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }

    // Setup collapsible menu items
    const collapsibleItems = sidebar.querySelectorAll('.has-submenu');
    collapsibleItems.forEach(item => {
        const submenuToggle = item.querySelector('.submenu-toggle');
        if (submenuToggle) {
            submenuToggle.addEventListener('click', (e) => {
                e.preventDefault();
                item.classList.toggle('expanded');
            });
        }
    });
}

// UI Components
class DashboardUI {
  static updateStatistics(stats) {
    const formatCurrency = new Intl.NumberFormat("en-PH", {
      style: "currency",
      currency: "PHP",
    });

    document.querySelector(".stat-card:nth-child(1) .stat-value").textContent =
      formatCurrency.format(stats.totalSales);
    document.querySelector(".stat-card:nth-child(2) .stat-value").textContent =
      stats.orderCount.toLocaleString();
    document.querySelector(".stat-card:nth-child(3) .stat-value").textContent =
      stats.customerCount.toLocaleString();
    document.querySelector(".stat-card:nth-child(4) .stat-value").textContent =
      stats.productCount.toLocaleString();
  }

  static updateOrdersTable(orders) {
    // Event Handlers
    function handleOnlineStatus(e) {
      const status = e.type === "online";
      const statusBar = document.getElementById("connectionStatus");

      if (!statusBar) {
        const bar = document.createElement("div");
        bar.id = "connectionStatus";
        bar.className = `connection-status ${status ? "online" : "offline"}`;
        bar.textContent = status ? "Back Online" : "Connection Lost";
        document.body.appendChild(bar);

        setTimeout(() => bar.remove(), 3000);
      } else {
        statusBar.className = `connection-status ${
          status ? "online" : "offline"
        }`;
        statusBar.textContent = status ? "Back Online" : "Connection Lost";
      }
    }

    function handleVisibilityChange() {
      if (!document.hidden) {
        // Refresh data when tab becomes visible
        if (appState.currentPage === "dashboard") {
          refreshDashboardData();
        }
        // Check for new notifications
        loadNotifications();
      }
    }

    function handleClickOutside(e) {
      // Close dropdowns when clicking outside
      const dropdowns = document.querySelectorAll(".dropdown.show");
      dropdowns.forEach((dropdown) => {
        if (!dropdown.contains(e.target)) {
          dropdown.classList.remove("show");
        }
      });
    }

    // UI Enhancements
    function initHeader() {
      // Add loading progress bar
      const progressBar = document.createElement("div");
      progressBar.className = "progress-bar";
      document.body.appendChild(progressBar);

      // Add theme switcher
      const header = document.querySelector(".main-header .header-user");
      if (header) {
        const themeSwitcher = document.createElement("div");
        themeSwitcher.className = "theme-switcher";
        themeSwitcher.innerHTML = `
            <label class="switch">
                <input type="checkbox" id="themeSwitcher">
                <span class="slider round"></span>
                <i class="fas fa-sun light-icon"></i>
                <i class="fas fa-moon dark-icon"></i>
            </label>
        `;
        header.insertBefore(themeSwitcher, header.firstChild);
      }
    }

    // Show loading progress
    function showLoading() {
      const progressBar = document.querySelector(".progress-bar");
      if (progressBar) {
        progressBar.style.width = "90%";
        progressBar.style.opacity = "1";
      }
    }

    // Hide loading progress
    function hideLoading() {
      const progressBar = document.querySelector(".progress-bar");
      if (progressBar) {
        progressBar.style.width = "100%";
        setTimeout(() => {
          progressBar.style.opacity = "0";
          setTimeout(() => {
            progressBar.style.width = "0";
          }, 200);
        }, 200);
      }
    }

    // Show toast notification
    function showToast(message, type = "info") {
      const toast = document.createElement("div");
      toast.className = `toast toast-${type}`;
      toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${
              type === "success"
                ? "check-circle"
                : type === "error"
                ? "times-circle"
                : type === "warning"
                ? "exclamation-circle"
                : "info-circle"
            }"></i>
        </div>
        <div class="toast-message">${message}</div>
    `;

      document.body.appendChild(toast);

      // Trigger animation
      setTimeout(() => toast.classList.add("show"), 100);

      // Auto dismiss
      setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }

    // Data table pagination
    function initDataTable(table) {
      if (!table) return;

      const wrapper = document.createElement("div");
      wrapper.className = "data-table-wrapper";

      paginatedOrders.forEach((order) => {
        const row = document.createElement("tr");
        row.innerHTML = `
              <td>${order.id}</td>
              <td>${order.customer}</td>
              <td>${order.product}</td>
              <td>₱${order.amount.toLocaleString()}</td>
              <td><span class="status-badge ${order.status.toLowerCase()}">${
          order.status
        }</span></td>
              <td>${order.date}</td>
              <td>
                  <div class="action-buttons">
                      <button class="action-button view-btn" data-id="${
                        order.id
                      }">
                          <i class="material-icons">visibility</i>
                      </button>
                      <button class="action-button edit-btn" data-id="${
                        order.id
                      }">
                          <i class="material-icons">edit</i>
                      </button>
                      <button class="action-button delete-btn" data-id="${
                        order.id
                      }">
                          <i class="material-icons">delete</i>
                      </button>
                  </div>
              </td>
          `;
        tableBody.appendChild(row);
      });

      this.updatePagination(orders.length);
    }

    // Data Table Functionality
    function initDataTable(table, options = {}) {
      const defaults = {
        perPage: 10,
        search: true,
        sort: true,
        pagination: true,
        export: true,
      };

      const settings = { ...defaults, ...options };
      const wrapper = table.parentElement;

      if (settings.search) {
        addTableSearch(wrapper, table);
      }

      if (settings.sort) {
        addTableSort(table);
      }

      if (settings.pagination) {
        addTablePagination(wrapper, table, settings.perPage);
      }

      if (settings.export) {
        addTableExport(wrapper, table);
      }
    }

    function addTableSearch(wrapper, table) {
      const searchWrapper = document.createElement("div");
      searchWrapper.className = "table-search";
      searchWrapper.innerHTML = `
        <input type="text" placeholder="Search table..." class="form-control">
    `;

      const searchInput = searchWrapper.querySelector("input");
      searchInput.addEventListener(
        "input",
        utils.debounce((e) => {
          const searchTerm = e.target.value.toLowerCase();
          const rows = Array.from(table.querySelectorAll("tbody tr"));

          rows.forEach((row) => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? "" : "none";
          });
        }, 300)
      );

      wrapper.insertBefore(searchWrapper, table);
    }

    function addTableSort(table) {
      const headers = table.querySelectorAll("th");
      headers.forEach((header) => {
        if (header.dataset.sort !== "false") {
          header.style.cursor = "pointer";
          header.innerHTML += '<i class="fas fa-sort ml-2"></i>';

          header.addEventListener("click", () => {
            const index = Array.from(header.parentElement.children).indexOf(
              header
            );
            const currentSort = header.dataset.currentSort || "none";
            const newSort = currentSort === "asc" ? "desc" : "asc";

            // Reset other headers
            headers.forEach((h) => {
              h.dataset.currentSort = "none";
              h.querySelector("i").className = "fas fa-sort ml-2";
            });

            // Update current header
            header.dataset.currentSort = newSort;
            header.querySelector("i").className = `fas fa-sort-${newSort} ml-2`;

            // Sort rows
            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll("tr"));

            rows.sort((a, b) => {
              const aVal = a.children[index].textContent;
              const bVal = b.children[index].textContent;

              if (isNaN(aVal)) {
                return newSort === "asc"
                  ? aVal.localeCompare(bVal)
                  : bVal.localeCompare(aVal);
              } else {
                return newSort === "asc"
                  ? Number(aVal) - Number(bVal)
                  : Number(bVal) - Number(aVal);
              }
            });

            tbody.append(...rows);
          });
        }
      });
    }

    function addTablePagination(wrapper, table, perPage) {
      const rows = Array.from(table.querySelectorAll("tbody tr"));
      const pageCount = Math.ceil(rows.length / perPage);

      const paginationWrapper = document.createElement("div");
      paginationWrapper.className = "table-pagination";

      let currentPage = 1;

      function showPage(page) {
        const start = (page - 1) * perPage;
        const end = start + perPage;

        rows.forEach((row, index) => {
          row.style.display = index >= start && index < end ? "" : "none";
        });

        updatePaginationButtons();
      }

      function updatePaginationButtons() {
        paginationWrapper.innerHTML = `
            <button class="btn btn-sm" ${
              currentPage === 1 ? "disabled" : ""
            } data-page="prev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="pagination-info">${currentPage} / ${pageCount}</span>
            <button class="btn btn-sm" ${
              currentPage === pageCount ? "disabled" : ""
            } data-page="next">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;

        const buttons = paginationWrapper.querySelectorAll("button");
        buttons.forEach((button) => {
          button.addEventListener("click", () => {
            if (button.dataset.page === "prev" && currentPage > 1) {
              currentPage--;
            } else if (
              button.dataset.page === "next" &&
              currentPage < pageCount
            ) {
              currentPage++;
            }
            showPage(currentPage);
          });
        });
      }

      wrapper.appendChild(paginationWrapper);
      showPage(1);
    }

    function addTableExport(wrapper, table) {
      const exportWrapper = document.createElement("div");
      exportWrapper.className = "table-export";
      exportWrapper.innerHTML = `
        <button class="btn btn-sm" data-format="csv">
            <i class="fas fa-file-csv"></i> Export CSV
        </button>
        <button class="btn btn-sm" data-format="excel">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
        <button class="btn btn-sm" data-format="pdf">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
    `;

      exportWrapper.addEventListener("click", (e) => {
        const button = e.target.closest("button");
        if (!button) return;

        const format = button.dataset.format;
        exportTable(table, format);
      });

      wrapper.insertBefore(exportWrapper, table);
    }

    // Export table data
    function exportTable(table, format) {
      const headers = Array.from(table.querySelectorAll("th")).map((th) =>
        th.textContent.trim()
      );
      const rows = Array.from(table.querySelectorAll("tbody tr")).map((tr) =>
        Array.from(tr.querySelectorAll("td")).map((td) => td.textContent.trim())
      );

      switch (format) {
        case "csv":
          exportCSV(headers, rows);
          break;
        case "excel":
          exportExcel(headers, rows);
          break;
        case "pdf":
          exportPDF(headers, rows);
          break;
      }
    }

    // Export helpers
    function exportCSV(headers, rows) {
      const csv = [headers.join(","), ...rows.map((row) => row.join(","))].join(
        "\n"
      );

      downloadFile(csv, "export.csv", "text/csv");
    }

    function exportExcel(headers, rows) {
      // Using a library like SheetJS would be better
      const csv = [
        headers.join("\t"),
        ...rows.map((row) => row.join("\t")),
      ].join("\n");

      downloadFile(csv, "export.xls", "application/vnd.ms-excel");
    }

    function exportPDF(headers, rows) {
      // Using a library like jsPDF would be better
      alert(
        "PDF export requires additional library. Please implement with jsPDF."
      );
    }

    function downloadFile(content, filename, type) {
      const blob = new Blob([content], { type });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    }

    // Export utility functions
    window.adminUtils = {
      ...utils,
      showToast,
      showLoading,
      hideLoading,
      initDataTable,
      formatTimeAgo: (timestamp) => {
        const now = new Date();
        const past = new Date(timestamp);
        const diffMs = now - past;
        const diffSec = Math.round(diffMs / 1000);
        const diffMin = Math.round(diffSec / 60);
        const diffHour = Math.round(diffMin / 60);
        const diffDay = Math.round(diffHour / 24);

        if (diffSec < 60) return `${diffSec}s ago`;
        if (diffMin < 60) return `${diffMin}m ago`;
        if (diffHour < 24) return `${diffHour}h ago`;
        if (diffDay === 1) return "Yesterday";
        if (diffDay < 7) return `${diffDay}d ago`;

        return past.toLocaleDateString();
      },
    };
    dashboardState.pagination.totalPages = Math.ceil(
      totalItems / dashboardState.pagination.itemsPerPage
    );

    const pagination = document.querySelector(".pagination");
    const currentPage = document.querySelector(".current-page");
    const totalPages = document.querySelector(".total-pages");
    const prevBtn = document.querySelector(".prev-page");
    const nextBtn = document.querySelector(".next-page");

    currentPage.textContent = dashboardState.pagination.currentPage;
    totalPages.textContent = dashboardState.pagination.totalPages;
    prevBtn.disabled = dashboardState.pagination.currentPage === 1;
    nextBtn.disabled =
      dashboardState.pagination.currentPage ===
      dashboardState.pagination.totalPages;
  }

  static showNotification(message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
      notification.classList.add("show");
      setTimeout(() => {
        notification.classList.remove("show");
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }, 100);
  }

  static showModal(title, content) {
    const modal = document.getElementById("orderModal");
    const modalTitle = modal.querySelector(".modal-header h2");
    const modalBody = modal.querySelector(".modal-body");

    modalTitle.textContent = title;
    modalBody.innerHTML = content;
    modal.style.display = "flex";
  }
}

// Event Handlers
class EventHandlers {
  static init() {
    // Mobile menu toggle
    document
      .querySelector(".mobile-menu-toggle")
      .addEventListener("click", () => {
        document.querySelector(".sidebar").classList.add("show");
      });

    document.querySelector(".mobile-close").addEventListener("click", () => {
      document.querySelector(".sidebar").classList.remove("show");
    });

    // Navigation
    document.querySelectorAll(".nav-item").forEach((item) => {
      item.addEventListener("click", () => {
        this.handleNavigation(item.dataset.page);
      });
    });

    // Modal close buttons
    document.querySelectorAll(".close-modal, .modal-close").forEach((btn) => {
      btn.addEventListener("click", () => {
        document.getElementById("orderModal").style.display = "none";
      });
    });

    // Pagination
    document.querySelector(".prev-page").addEventListener("click", () => {
      if (dashboardState.pagination.currentPage > 1) {
        dashboardState.pagination.currentPage--;
        this.refreshDashboard();
      }
    });

    document.querySelector(".next-page").addEventListener("click", () => {
      if (
        dashboardState.pagination.currentPage <
        dashboardState.pagination.totalPages
      ) {
        dashboardState.pagination.currentPage++;
        this.refreshDashboard();
      }
    });

    // Filters
    document.querySelector(".search-input").addEventListener(
      "input",
      this.debounce((e) => {
        dashboardState.filters.search = e.target.value.toLowerCase();
        this.refreshDashboard();
      }, 300)
    );

    document.querySelector(".status-filter").addEventListener("change", (e) => {
      dashboardState.filters.status = e.target.value;
      this.refreshDashboard();
    });

    document.querySelector(".date-filter").addEventListener("change", (e) => {
      dashboardState.filters.dateRange = e.target.value;
      this.refreshDashboard();
    });

    // Generate Report
    document
      .querySelector(".generate-report-btn")
      .addEventListener("click", this.handleReportGeneration);

    // Table Actions
    document.querySelector(".data-table").addEventListener("click", (e) => {
      const btn = e.target.closest(".action-button");
      if (!btn) return;

      const orderId = btn.dataset.id;
      if (btn.classList.contains("view-btn")) {
        this.handleViewOrder(orderId);
      } else if (btn.classList.contains("edit-btn")) {
        this.handleEditOrder(orderId);
      } else if (btn.classList.contains("delete-btn")) {
        this.handleDeleteOrder(orderId);
      }
    });
  }

  static handleNavigation(page) {
    document.querySelectorAll(".nav-item").forEach((item) => {
      item.classList.toggle("active", item.dataset.page === page);
    });
    dashboardState.currentPage = page;
    this.refreshDashboard();
  }

  static async refreshDashboard() {
    const filteredOrders = this.getFilteredOrders();
    DashboardUI.updateOrdersTable(filteredOrders);
    DashboardUI.updateStatistics(
      MockDataGenerator.generateStatistics(filteredOrders)
    );
  }

  static getFilteredOrders() {
    let filtered = [...dashboardState.orders];

    // Apply search filter
    if (dashboardState.filters.search) {
      filtered = filtered.filter(
        (order) =>
          order.id.toLowerCase().includes(dashboardState.filters.search) ||
          order.customer
            .toLowerCase()
            .includes(dashboardState.filters.search) ||
          order.product.toLowerCase().includes(dashboardState.filters.search)
      );
    }

    // Apply status filter
    if (dashboardState.filters.status !== "all") {
      filtered = filtered.filter(
        (order) => order.status === dashboardState.filters.status
      );
    }

    // Apply date filter
    const today = new Date();
    const startOfDay = new Date(today.setHours(0, 0, 0, 0));

    switch (dashboardState.filters.dateRange) {
      case "today":
        filtered = filtered.filter(
          (order) => new Date(order.date) >= startOfDay
        );
        break;
      case "week":
        const weekAgo = new Date(startOfDay - 7 * 24 * 60 * 60 * 1000);
        filtered = filtered.filter((order) => new Date(order.date) >= weekAgo);
        break;
      case "month":
        const monthAgo = new Date(
          today.getFullYear(),
          today.getMonth() - 1,
          today.getDate()
        );
        filtered = filtered.filter((order) => new Date(order.date) >= monthAgo);
        break;
    }

    return filtered;
  }

  static handleViewOrder(orderId) {
    const order = dashboardState.orders.find((o) => o.id === orderId);
    if (!order) return;

    const content = `
          <div class="order-details">
              <p><strong>Order ID:</strong> ${order.id}</p>
              <p><strong>Customer:</strong> ${order.customer}</p>
              <p><strong>Product:</strong> ${order.product}</p>
              <p><strong>Amount:</strong> ₱${order.amount.toLocaleString()}</p>
              <p><strong>Status:</strong> 
                  <span class="status-badge ${order.status}">${
      order.status
    }</span>
              </p>
              <p><strong>Date:</strong> ${order.date}</p>
          </div>
      `;

    DashboardUI.showModal("Order Details", content);
  }

  static handleEditOrder(orderId) {
    const order = dashboardState.orders.find((o) => o.id === orderId);
    if (!order) return;

    const content = `
          <form id="editOrderForm" class="edit-form">
              <div class="form-group">
                  <label>Customer Name</label>
                  <input type="text" value="${order.customer}" required>
              </div>
              <div class="form-group">
                  <label>Product</label>
                  <input type="text" value="${order.product}" required>
              </div>
              <div class="form-group">
                  <label>Amount</label>
                  <input type="number" value="${order.amount}" required>
              </div>
              <div class="form-group">
                  <label>Status</label>
                  <select>
                      <option value="pending" ${
                        order.status === "pending" ? "selected" : ""
                      }>Pending</option>
                      <option value="processing" ${
                        order.status === "processing" ? "selected" : ""
                      }>Processing</option>
                      <option value="completed" ${
                        order.status === "completed" ? "selected" : ""
                      }>Completed</option>
                      <option value="cancelled" ${
                        order.status === "cancelled" ? "selected" : ""
                      }>Cancelled</option>
                  </select>
              </div>
              <button type="submit" class="save-btn">Save Changes</button>
          </form>
      `;

    DashboardUI.showModal("Edit Order", content);

    document.getElementById("editOrderForm").addEventListener("submit", (e) => {
      e.preventDefault();
      // Handle form submission here
      DashboardUI.showNotification("Order updated successfully", "success");
      document.getElementById("orderModal").style.display = "none";
    });
  }

  static async handleDeleteOrder(orderId) {
    if (confirm("Are you sure you want to delete this order?")) {
      dashboardState.orders = dashboardState.orders.filter(
        (o) => o.id !== orderId
      );
      await this.refreshDashboard();
      DashboardUI.showNotification("Order deleted successfully", "success");
    }
  }

  static handleReportGeneration() {
    const filteredOrders = this.getFilteredOrders();
    const csv = this.generateCSV(filteredOrders);
    this.downloadCSV(
      csv,
      `orders_report_${new Date().toISOString().split("T")[0]}.csv`
    );
    DashboardUI.showNotification("Report generated successfully", "success");
  }

  static generateCSV(orders) {
    const headers = [
      "Order ID",
      "Customer",
      "Product",
      "Amount",
      "Status",
      "Date",
    ];
    const rows = orders.map((order) => [
      order.id,
      order.customer,
      order.product,
      order.amount,
      order.status,
      order.date,
    ]);

    return [headers, ...rows].map((row) => row.join(",")).join("\n");
  }

  static downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
  }

  static debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
}

// Show User Roles Section - Temporarily disabled
/* 
function showUserRolesSection() {
    const dashboardContent = document.querySelectorAll('.dashboard-content > div:not(#userRolesSection)');
    const userRolesSection = document.getElementById('userRolesSection');
    
    // Hide other sections
    dashboardContent.forEach(section => {
        section.style.display = 'none';
    });
    
    // Show user roles section
    userRolesSection.style.display = 'block';
    
    // Load user roles data
    loadUsersByRole();
}
*/

// Initialize Dashboard
document.addEventListener("DOMContentLoaded", async () => {
  // Load mock data
  dashboardState.orders = MockDataGenerator.generateOrders();

  // Initialize event handlers
  EventHandlers.init();

  // User Roles module is currently disabled
  // To re-enable, uncomment the following lines:
  /*
  import UserRolesModule from './modules/user-roles.js';
  UserRolesModule.enable();
  */

  // Initial dashboard refresh
  await EventHandlers.refreshDashboard();
});
