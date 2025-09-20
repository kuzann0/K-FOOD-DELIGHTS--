// Utility functions
function showToast(message, type = "success") {
  // Implementation for showing toast messages
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString();
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "PHP",
  }).format(amount);
}

function showModal(modalId) {
  document.getElementById(modalId).style.display = "block";
}

function closeModal(modalId) {
  document.getElementById(modalId).style.display = "none";
}

// API calls
async function apiRequest(type, action, data = null) {
  try {
    const options = {
      method: data ? "POST" : "GET",
      headers: {
        "Content-Type": "application/json",
      },
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    const response = await fetch(
      `api.php?type=${type}&action=${action}`,
      options
    );
    const result = await response.json();

    if (result.error) {
      throw new Error(result.error);
    }

    return result;
  } catch (error) {
    showToast(error.message, "error");
    throw error;
  }
}

// Load data functions
async function loadRawMaterials() {
  try {
    const result = await apiRequest("raw_materials", "get");
    const tbody = document.querySelector("#rawMaterialsTable tbody");
    tbody.innerHTML = "";

    result.data.forEach((material) => {
      tbody.innerHTML += `
                <tr>
                    <td>${material.material_name}</td>
                    <td>${material.category_name || "-"}</td>
                    <td>${material.current_stock} ${material.unit}</td>
                    <td>${material.unit}</td>
                    <td>${material.supplier_name || "-"}</td>
                    <td>${formatDate(material.expiration_date)}</td>
                    <td>
                        <span class="status-badge ${
                          material.is_active ? "active" : "inactive"
                        }">
                            ${material.is_active ? "Active" : "Inactive"}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editRawMaterial(${
                          material.material_id
                        })">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteRawMaterial(${
                          material.material_id
                        })">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="showTransactionModal('raw_material', ${
                          material.material_id
                        })">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
    });
  } catch (error) {
    console.error("Error loading raw materials:", error);
  }
}

async function loadFinishedProducts() {
  try {
    const result = await apiRequest("finished_products", "get");
    const tbody = document.querySelector("#productsTable tbody");
    tbody.innerHTML = "";

    result.data.forEach((product) => {
      tbody.innerHTML += `
                <tr>
                    <td>${product.product_name}</td>
                    <td>${product.category_name || "-"}</td>
                    <td>${product.current_stock} ${product.unit}</td>
                    <td>${product.unit}</td>
                    <td>${formatCurrency(product.selling_price)}</td>
                    <td>
                        <span class="status-badge ${
                          product.is_active ? "active" : "inactive"
                        }">
                            ${product.is_active ? "Active" : "Inactive"}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editProduct(${
                          product.product_id
                        })">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(${
                          product.product_id
                        })">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="showTransactionModal('finished_product', ${
                          product.product_id
                        })">
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
    });
  } catch (error) {
    console.error("Error loading finished products:", error);
  }
}

async function loadTransactions() {
  try {
    const result = await apiRequest("transactions", "get");
    const tbody = document.querySelector("#transactionsTable tbody");
    tbody.innerHTML = "";

    result.data.forEach((transaction) => {
      tbody.innerHTML += `
                <tr>
                    <td>${formatDate(transaction.created_at)}</td>
                    <td>
                        <span class="badge ${transaction.transaction_type}">
                            ${transaction.transaction_type
                              .replace("_", " ")
                              .toUpperCase()}
                        </span>
                    </td>
                    <td>${transaction.item_name}</td>
                    <td>${transaction.quantity}</td>
                    <td>${transaction.reference_no || "-"}</td>
                    <td>${transaction.created_by_name}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewTransaction(${
                          transaction.transaction_id
                        })">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
    });
  } catch (error) {
    console.error("Error loading transactions:", error);
  }
}

async function loadAlerts() {
  try {
    const result = await apiRequest("alerts", "get");
    const container = document.querySelector(".alerts-container");
    container.innerHTML = "";

    result.data.forEach((alert) => {
      container.innerHTML += `
                <div class="alert-card ${alert.alert_type}">
                    <div class="alert-header">
                        <span class="alert-type">${alert.alert_type
                          .replace("_", " ")
                          .toUpperCase()}</span>
                        <span class="alert-date">${formatDate(
                          alert.created_at
                        )}</span>
                    </div>
                    <div class="alert-body">
                        <p>${alert.message}</p>
                        <p class="alert-item">Item: ${alert.item_name}</p>
                    </div>
                    <div class="alert-footer">
                        <button class="btn btn-sm btn-primary" onclick="resolveAlert(${
                          alert.alert_id
                        })">
                            Mark as Resolved
                        </button>
                    </div>
                </div>
            `;
    });
  } catch (error) {
    console.error("Error loading alerts:", error);
  }
}

// Form handlers
async function handleRawMaterialForm(event) {
  event.preventDefault();

  const formData = {
    material_id: document.getElementById("rawMaterialId").value,
    name: document.getElementById("materialName").value,
    category_id: document.getElementById("materialCategory").value,
    supplier_id: document.getElementById("materialSupplier").value,
    unit: document.getElementById("materialUnit").value,
    current_stock: parseFloat(document.getElementById("materialStock").value),
    minimum_stock: parseFloat(
      document.getElementById("materialMinStock").value
    ),
    cost_per_unit: parseFloat(document.getElementById("materialCost").value),
    expiration_date: document.getElementById("materialExpiration").value,
    storage_location: document.getElementById("materialLocation").value,
  };

  try {
    const action = formData.material_id ? "update" : "create";
    await apiRequest("raw_materials", action, formData);
    showToast("Raw material saved successfully");
    closeModal("rawMaterialModal");
    loadRawMaterials();
  } catch (error) {
    console.error("Error saving raw material:", error);
  }
}

async function handleProductForm(event) {
  event.preventDefault();

  const formData = {
    product_id: document.getElementById("productId").value,
    name: document.getElementById("productName").value,
    category_id: document.getElementById("productCategory").value,
    unit: document.getElementById("productUnit").value,
    current_stock: parseFloat(document.getElementById("productStock").value),
    minimum_stock: parseFloat(document.getElementById("productMinStock").value),
    selling_price: parseFloat(document.getElementById("productPrice").value),
  };

  try {
    const action = formData.product_id ? "update" : "create";
    await apiRequest("finished_products", action, formData);
    showToast("Product saved successfully");
    closeModal("productModal");
    loadFinishedProducts();
  } catch (error) {
    console.error("Error saving product:", error);
  }
}

async function handleTransactionForm(event) {
  event.preventDefault();

  const formData = {
    transaction_type: document.getElementById("transactionType").value,
    item_type: document.getElementById("itemType").value,
    item_id: document.getElementById("itemId").value,
    quantity: parseFloat(document.getElementById("quantity").value),
    unit_price: parseFloat(document.getElementById("unitPrice").value),
    reference: document.getElementById("reference").value,
    notes: document.getElementById("notes").value,
  };

  try {
    await apiRequest("transactions", "create", formData);
    showToast("Transaction recorded successfully");
    closeModal("transactionModal");
    loadTransactions();

    // Reload inventory data
    if (formData.item_type === "raw_material") {
      loadRawMaterials();
    } else {
      loadFinishedProducts();
    }
  } catch (error) {
    console.error("Error recording transaction:", error);
  }
}

// Event listeners
document.addEventListener("DOMContentLoaded", () => {
  // Initialize tab functionality
  const tabs = document.querySelectorAll(".tab-btn");
  tabs.forEach((tab) => {
    tab.addEventListener("click", (e) => {
      tabs.forEach((t) => t.classList.remove("active"));
      e.target.classList.add("active");

      document.querySelectorAll(".tab-pane").forEach((pane) => {
        pane.classList.remove("active");
      });
      document.getElementById(e.target.dataset.tab).classList.add("active");
    });
  });

  // Initialize form submissions
  document
    .getElementById("rawMaterialForm")
    .addEventListener("submit", handleRawMaterialForm);
  document
    .getElementById("productForm")
    .addEventListener("submit", handleProductForm);
  document
    .getElementById("transactionForm")
    .addEventListener("submit", handleTransactionForm);

  // Load initial data
  loadRawMaterials();
  loadFinishedProducts();
  loadTransactions();
  loadAlerts();
});

// Update item select options when item type changes
document
  .getElementById("itemType")
  .addEventListener("change", async function () {
    const itemId = document.getElementById("itemId");
    itemId.innerHTML = '<option value="">Loading...</option>';

    try {
      const type =
        this.value === "raw_material" ? "raw_materials" : "finished_products";
      const result = await apiRequest(type, "get");

      itemId.innerHTML =
        '<option value="">Select Item</option>' +
        result.data
          .map(
            (item) => `
                <option value="${
                  item[type === "raw_materials" ? "material_id" : "product_id"]
                }">
                    ${
                      item[
                        type === "raw_materials"
                          ? "material_name"
                          : "product_name"
                      ]
                    }
                </option>
            `
          )
          .join("");
    } catch (error) {
      console.error("Error loading items:", error);
      itemId.innerHTML = '<option value="">Error loading items</option>';
    }
  });

// Search and filter functionality
document
  .getElementById("rawMaterialSearch")
  .addEventListener("input", function () {
    filterTable("rawMaterialsTable", this.value);
  });

document.getElementById("productSearch").addEventListener("input", function () {
  filterTable("productsTable", this.value);
});

function filterTable(tableId, searchText) {
  const table = document.getElementById(tableId);
  const rows = table.getElementsByTagName("tr");

  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchText.toLowerCase()) ? "" : "none";
  }
}

// Alert handling
async function resolveAlert(alertId) {
  try {
    await apiRequest("alerts", "resolve", { alert_id: alertId });
    showToast("Alert marked as resolved");
    loadAlerts();
  } catch (error) {
    console.error("Error resolving alert:", error);
  }
}

// Deletion handlers
async function deleteRawMaterial(materialId) {
  if (confirm("Are you sure you want to delete this raw material?")) {
    try {
      await apiRequest("raw_materials", "delete", { material_id: materialId });
      showToast("Raw material deleted successfully");
      loadRawMaterials();
    } catch (error) {
      console.error("Error deleting raw material:", error);
    }
  }
}

async function deleteProduct(productId) {
  if (confirm("Are you sure you want to delete this product?")) {
    try {
      await apiRequest("finished_products", "delete", {
        product_id: productId,
      });
      showToast("Product deleted successfully");
      loadFinishedProducts();
    } catch (error) {
      console.error("Error deleting product:", error);
    }
  }
}
