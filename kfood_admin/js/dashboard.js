// Dashboard specific JavaScript functionality

// Load dashboard data and update UI
async function loadDashboardData() {
  try {
    const response = await fetchAPI("api/dashboard-data.php");
    updateDashboardUI(response);
  } catch (error) {
    console.error("Error loading dashboard data:", error);
    showErrorMessage("Failed to load dashboard data");
  }
}

// Update all dashboard UI elements
function updateDashboardUI(data) {
  const { stats, charts, alerts, recentOrders } = data;

  // Update stats with animations
  animateNumber("todayOrders", stats.orders.today_orders);
  animateNumber("monthOrders", stats.orders.month_orders);
  animateNumber("pendingOrders", stats.orders.pending_orders);
  animateNumber("activeUsers", stats.users.active_users);

  // Update revenue values
  document.getElementById("todayRevenue").textContent = formatCurrency(
    stats.revenue.today_revenue
  );
  document.getElementById("monthRevenue").textContent = formatCurrency(
    stats.revenue.month_revenue
  );
  document.getElementById("avgOrderValue").textContent = formatCurrency(
    stats.revenue.avg_order_value
  );

  // Update growth indicators
  updateGrowthIndicator("revenueGrowth", stats.revenue.growth);

  // Update low stock and inventory
  document.getElementById("lowStockCount").textContent =
    stats.inventory.low_stock_count;
  document.getElementById("availableItems").textContent =
    stats.inventory.available_items;

  updateCharts(charts);
  updateAlerts(alerts);
  updateRecentOrders(recentOrders);
}

// Update statistics cards
function updateStats(stats) {
  document.getElementById("todayOrders").textContent = stats.todayOrders;
  document.getElementById("todayRevenue").textContent = formatCurrency(
    stats.todayRevenue
  );
  document.getElementById("lowStockCount").textContent = stats.lowStockCount;
  document.getElementById("activeUsers").textContent = stats.activeUsers;
}

// Update dashboard charts
// Animate number changes
function animateNumber(elementId, targetValue) {
  const element = document.getElementById(elementId);
  const start = parseInt(element.textContent) || 0;
  const duration = 1000; // 1 second
  const steps = 20;
  const increment = (targetValue - start) / steps;
  let current = start;
  let step = 0;

  const timer = setInterval(() => {
    current += increment;
    step++;

    if (step === steps) {
      current = targetValue;
      clearInterval(timer);
    }

    element.textContent = Math.round(current);
  }, duration / steps);
}

// Update growth indicator
function updateGrowthIndicator(elementId, value) {
  const element = document.getElementById(elementId);
  if (element) {
    const isPositive = value >= 0;
    element.className = `stat-change ${isPositive ? "positive" : "negative"}`;
    element.innerHTML = `
            <i class="fas fa-${isPositive ? "arrow-up" : "arrow-down"}"></i>
            ${Math.abs(value).toFixed(1)}%
        `;
  }
}

function updateCharts(chartData) {
  // Sales Chart
  if (window.salesChart) {
    window.salesChart.data.labels = chartData.sales.labels;
    window.salesChart.data.datasets = [
      {
        label: "Revenue",
        data: chartData.sales.revenue,
        borderColor: "#1a237e",
        backgroundColor: "rgba(26, 35, 126, 0.1)",
        tension: 0.4,
        fill: true,
      },
      {
        label: "Orders",
        data: chartData.sales.orders,
        borderColor: "#c62828",
        backgroundColor: "transparent",
        tension: 0.4,
        yAxisID: "ordersAxis",
      },
    ];
    window.salesChart.update();
  } else {
    initSalesChart(chartData.sales);
  }

  // Popular Items Chart
  if (window.itemsChart) {
    window.itemsChart.data.labels = chartData.popularItems.labels;
    window.itemsChart.data.datasets[0].data = chartData.popularItems.data;
    window.itemsChart.update();
  } else {
    initPopularItemsChart(chartData.popularItems);
  }
}

// Initialize Sales Chart
function initSalesChart(data) {
  const ctx = document.getElementById("salesChart").getContext("2d");
  window.salesChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Daily Sales",
          data: data.data,
          borderColor: "#1a237e",
          tension: 0.1,
          fill: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => formatCurrency(value),
          },
        },
      },
      plugins: {
        tooltip: {
          callbacks: {
            label: (context) => formatCurrency(context.raw),
          },
        },
      },
    },
  });
}

// Initialize Popular Items Chart
function initPopularItemsChart(data) {
  const ctx = document.getElementById("popularItemsChart").getContext("2d");
  window.itemsChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Orders",
          data: data.data,
          backgroundColor: "#1a237e",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
          },
        },
      },
    },
  });
}

// Update alerts section
function updateAlerts(alerts) {
  const alertsList = document.getElementById("alertsList");
  alertsList.innerHTML = alerts
    .map(
      (alert) => `
        <div class="alert alert-${alert.type}">
            <div class="alert-content">
                <strong>${alert.title}</strong>
                <p>${alert.message}</p>
            </div>
            <div class="alert-time">${formatTimeAgo(alert.timestamp)}</div>
        </div>
    `
    )
    .join("");
}

// Update recent orders table
function updateRecentOrders(orders) {
  const tbody = document.querySelector(".recent-orders tbody");
  if (tbody) {
    tbody.innerHTML = orders
      .map(
        (order) => `
            <tr>
                <td>${order.order_id}</td>
                <td>${order.customer_name}</td>
                <td>${formatCurrency(order.total_amount)}</td>
                <td>
                    <span class="status-badge ${order.status.toLowerCase()}">
                        ${order.status}
                    </span>
                </td>
                <td>${formatTimeAgo(order.order_time)}</td>
            </tr>
        `
      )
      .join("");
  }
}

// Format time ago
function formatTimeAgo(timestamp) {
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
}

// Auto-refresh dashboard data
let refreshInterval;

function startAutoRefresh() {
  // Refresh every 5 minutes
  refreshInterval = setInterval(loadDashboardData, 5 * 60 * 1000);
}

function stopAutoRefresh() {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
}

// Format currency values
function formatCurrency(value) {
  return (
    "₱" +
    parseFloat(value).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

// Initialize dashboard
function initDashboard() {
  loadDashboardData();
  startAutoRefresh();

  // Stop refresh when page is hidden
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      stopAutoRefresh();
    } else {
      loadDashboardData();
      startAutoRefresh();
    }
  });
}

// Clean up on page unload
window.addEventListener("unload", () => {
  stopAutoRefresh();
});

// Panel interactions
document.addEventListener("DOMContentLoaded", function () {
  // Handle User Roles Panel Link
  const userRolesPanelLink = document.getElementById("userRolesPanelLink");
  if (userRolesPanelLink) {
    userRolesPanelLink.addEventListener("click", function (e) {
      e.preventDefault();
      const userRolesSection = document.getElementById("userRolesSection");
      if (userRolesSection) {
        // Hide all other sections
        document.querySelectorAll(".module-section").forEach((section) => {
          if (!section.classList.contains("user-roles-section")) {
            section.style.display = "none";
          }
        });
        // Show user roles section
        userRolesSection.style.display = "block";
        // Update active state
        document
          .querySelectorAll(".panel")
          .forEach((panel) => panel.classList.remove("active"));
        this.closest(".panel").classList.add("active");
      }
    });
  }

  // Handle Menu Creation Panel Link
  const menuCreationPanelLink = document.getElementById(
    "menuCreationPanelLink"
  );
  if (menuCreationPanelLink) {
    menuCreationPanelLink.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = "modules/menu-creation.php";
    });
  }

  // Add panel hover effects
  const panels = document.querySelectorAll(".panel");
  panels.forEach((panel) => {
    panel.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-4px)";
    });

    panel.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
    });
  });

  // Add panel link click animation
  const panelLinks = document.querySelectorAll(".panel-link");
  panelLinks.forEach((link) => {
    link.addEventListener("click", function () {
      this.style.transform = "scale(0.95)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 100);
    });
  });

  // Handle Main Dashboard View
  const dashboardLink = document.querySelector('a[href="dashboard.php"]');
  if (dashboardLink) {
    dashboardLink.addEventListener("click", function (e) {
      if (window.location.pathname.endsWith("dashboard.php")) {
        e.preventDefault();
        // Show all module sections
        document.querySelectorAll(".module-section").forEach((section) => {
          if (!section.classList.contains("user-roles-section")) {
            section.style.display = "block";
          }
        });
        // Hide user roles section
        const userRolesSection = document.getElementById("userRolesSection");
        if (userRolesSection) {
          userRolesSection.style.display = "none";
        }
        // Reset active states
        document
          .querySelectorAll(".panel")
          .forEach((panel) => panel.classList.remove("active"));
      }
    });
  }
});
