// Constants
const SENIOR_DISCOUNT = 0.2;
const PWD_DISCOUNT = 0.15;

// Cart state
let cart = [];
let orderState = {
  subtotal: 0,
  discounts: {
    senior: {
      active: false,
      amount: 0,
    },
    pwd: {
      active: false,
      amount: 0,
    },
    promo: {
      active: false,
      code: null,
      amount: 0,
    },
  },
  total: 0,
};

// Function to display order items
function displayOrderItems() {
  const orderItemsContainer = document.getElementById("orderItems");
  orderItemsContainer.innerHTML = "";

  if (!cart || cart.length === 0) {
    orderItemsContainer.innerHTML =
      '<div class="empty-cart">Your cart is empty</div>';
    return;
  }

  cart.forEach((item) => {
    const itemElement = document.createElement("div");
    itemElement.className = "order-item";
    itemElement.innerHTML = `
            <img src="${item.image}" alt="${item.name}">
            <div class="order-item-details">
                <div>${item.name}</div>
                <div>Qty: ${item.quantity}</div>
            </div>
            <div class="order-item-price">₱${(
              item.price * item.quantity
            ).toFixed(2)}</div>
        `;
    orderItemsContainer.appendChild(itemElement);
  });

  // Check for Buy 3 Get 1 promo eligibility
  checkBuy3Get1Eligibility();
}

// Load cart items and initialize functionality when page loads
document.addEventListener("DOMContentLoaded", function () {
  // Load cart from localStorage
  try {
    const savedCart = localStorage.getItem("cart");
    if (!savedCart) {
      throw new Error("Cart is empty");
    }

    cart = JSON.parse(savedCart);
    if (!Array.isArray(cart) || cart.length === 0) {
      throw new Error("Cart is empty");
    }

    // Validate cart item structure
    cart = cart
      .filter((item) => {
        const isValid =
          item &&
          item.id && // Accept items with ID from cart.js
          typeof item.name === "string" &&
          !isNaN(parseFloat(item.price)) &&
          !isNaN(parseInt(item.quantity)) &&
          typeof item.image === "string";

        if (!isValid) {
          console.warn("Removing invalid cart item:", item);
        }
        return isValid;
      })
      .map((item) => ({
        id: item.id, // Preserve the ID
        name: item.name,
        price: parseFloat(item.price),
        quantity: parseInt(item.quantity),
        image: item.image,
      }));

    // Check if we still have valid items after filtering
    if (cart.length === 0) {
      throw new Error("No valid items in cart");
    }
  } catch (error) {
    console.error("Cart validation error:", error);
    alert("Your cart is empty or invalid!");
    window.location.href = "menu.php";
    return;
  }

  // Display items and initialize components
  displayOrderItems();
  attachEventListeners();
  calculateSubtotal();
  updateTotals();
});

// Attach event listeners to form elements
function attachEventListeners() {
  // Senior Citizen Checkbox
  document
    .getElementById("seniorDiscount")
    ?.addEventListener("change", function () {
      const seniorIdInput = document.getElementById("seniorIdInput");
      if (seniorIdInput) {
        seniorIdInput.style.display = this.checked ? "block" : "none";
        if (!this.checked) {
          document.getElementById("seniorId").value = "";
        }
        updateDiscounts();
      }
    });

  // PWD Checkbox
  document
    .getElementById("pwdDiscount")
    ?.addEventListener("change", function () {
      const pwdIdInput = document.getElementById("pwdIdInput");
      if (pwdIdInput) {
        pwdIdInput.style.display = this.checked ? "block" : "none";
        if (!this.checked) {
          document.getElementById("pwdId").value = "";
        }
        updateDiscounts();
      }
    });

  // Payment method selection
  document.querySelectorAll('input[name="paymentMethod"]').forEach((radio) => {
    radio.addEventListener("change", function () {
      const gcashDetails = document.getElementById("gcashDetails");
      const gcashNumber = document.getElementById("gcashNumber");
      const gcashReference = document.getElementById("gcashReference");

      if (this.value === "gcash") {
        gcashDetails.style.display = "block";
        // Only make these required when GCash is selected
        gcashNumber.setAttribute("required", "required");
        gcashReference.setAttribute("required", "required");
      } else {
        gcashDetails.style.display = "none";
        // Remove required attribute when not using GCash
        gcashNumber.removeAttribute("required");
        gcashReference.removeAttribute("required");
        // Clear values
        gcashNumber.value = "";
        gcashReference.value = "";
      }
    });
  });

  // Place order button
  document
    .getElementById("placeOrderBtn")
    ?.addEventListener("click", function (e) {
      e.preventDefault();
      placeOrder(this);
    });
}

// Calculate and update subtotal
function calculateSubtotal() {
  orderState.subtotal = cart.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  );
  document.getElementById(
    "subtotal"
  ).textContent = `₱${orderState.subtotal.toFixed(2)}`;
  return orderState.subtotal;
}

// Update discounts
function updateDiscounts(event) {
  const seniorChecked =
    document.getElementById("seniorDiscount")?.checked || false;
  const pwdChecked = document.getElementById("pwdDiscount")?.checked || false;

  // Only one discount can be applied
  if (seniorChecked && pwdChecked) {
    alert("Only one discount can be applied: Senior Citizen or PWD");
    if (event?.target.id === "seniorDiscount") {
      document.getElementById("pwdDiscount").checked = false;
      document.getElementById("pwdId").value = "";
      document.getElementById("pwdIdInput").style.display = "none";
    } else {
      document.getElementById("seniorDiscount").checked = false;
      document.getElementById("seniorId").value = "";
      document.getElementById("seniorIdInput").style.display = "none";
    }
  }

  updateTotals();
}

// Update all totals and discounts
function updateTotals() {
  // Reset order state
  orderState.subtotal = cart.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  );
  orderState.deliveryFee = DELIVERY_FEE;

  // Reset all discount amounts
  orderState.discounts.senior.amount = 0;
  orderState.discounts.pwd.amount = 0;
  orderState.discounts.promo.amount = 0;

  let discountableAmount = orderState.subtotal;

  // Apply promo code discount if valid
  if (activePromoCode && orderState.subtotal > 0) {
    orderState.discounts.promo.active = true;
    orderState.discounts.promo.code = activePromoCode.code;
    orderState.discounts.promo.amount =
      activePromoCode.type === "percentage"
        ? orderState.subtotal * activePromoCode.discount
        : Math.min(activePromoCode.discount, orderState.subtotal);

    const promoRow = document.querySelector(".total-row.discount-row");
    if (promoRow) {
      promoRow.style.display = "flex";
      const promoElement = document.getElementById("promoDiscount");
      if (promoElement) {
        promoElement.textContent = `-₱${orderState.discounts.promo.amount.toFixed(
          2
        )}`;
      }
    }

    discountableAmount -= orderState.discounts.promo.amount;
  } else {
    orderState.discounts.promo.active = false;
  }

  // Calculate Senior Citizen discount
  const seniorChecked =
    document.getElementById("seniorDiscount")?.checked || false;
  if (seniorChecked) {
    orderState.discounts.senior.active = true;
    orderState.discounts.senior.amount = discountableAmount * SENIOR_DISCOUNT;
    discountableAmount -= orderState.discounts.senior.amount;

    const discountRows = document.querySelectorAll(".total-row.discount-row");
    if (discountRows[1]) {
      discountRows[1].style.display = "flex";
      const seniorDiscountElement =
        document.getElementById("seniorPwdDiscount");
      if (seniorDiscountElement) {
        seniorDiscountElement.textContent = `-₱${orderState.discounts.senior.amount.toFixed(
          2
        )}`;
      }
    }
  } else {
    orderState.discounts.senior.active = false;
  }

  // Calculate PWD discount (only if senior discount not applied)
  const pwdChecked = document.getElementById("pwdDiscount")?.checked || false;
  if (!orderState.discounts.senior.active && pwdChecked) {
    orderState.discounts.pwd.active = true;
    orderState.discounts.pwd.amount = discountableAmount * PWD_DISCOUNT;
    discountableAmount -= orderState.discounts.pwd.amount;

    const discountRows = document.querySelectorAll(".total-row.discount-row");
    if (discountRows[1]) {
      discountRows[1].style.display = "flex";
      const pwdDiscountElement = document.getElementById("seniorPwdDiscount");
      if (pwdDiscountElement) {
        pwdDiscountElement.textContent = `-₱${orderState.discounts.pwd.amount.toFixed(
          2
        )}`;
      }
    }
  } else {
    orderState.discounts.pwd.active = false;
  }

  // Calculate final total
  const totalDiscount =
    orderState.discounts.promo.amount +
    orderState.discounts.senior.amount +
    orderState.discounts.pwd.amount;

  orderState.total = Math.max(
    0,
    orderState.subtotal - totalDiscount + orderState.deliveryFee
  );

  // Update display
  document.getElementById(
    "deliveryFee"
  ).textContent = `₱${orderState.deliveryFee.toFixed(2)}`;
  document.getElementById("total").textContent = `₱${orderState.total.toFixed(
    2
  )}`;

  return {
    subtotalAmount: orderState.subtotal,
    promoDiscount: orderState.discounts.promo.amount,
    seniorPwdDiscount:
      orderState.discounts.senior.amount || orderState.discounts.pwd.amount,
    totalDiscount,
    finalAmount: orderState.total,
  };
}

// Validate and prepare order data
async function validateAndPrepareOrderData() {
  try {
    // Get payment method
    const paymentMethod = document.querySelector(
      'input[name="paymentMethod"]:checked'
    )?.value;
    if (!paymentMethod) {
      throw new Error("Please select a payment method");
    }

    // Required fields validation with proper error messages
    const requiredFields = {
      fullName: "Full Name",
      email: "Email",
      phone: "Phone Number",
      address: "Delivery Address",
    };

    const orderData = {
      customerInfo: {},
      payment: {},
      cartItems: [],
      amounts: {},
    };

    // Validate and collect customer info
    for (const [fieldId, fieldName] of Object.entries(requiredFields)) {
      const element = document.getElementById(fieldId);
      const value = element?.value.trim();
      if (!value) {
        element?.focus();
        throw new Error(`${fieldName} is required`);
      }
      orderData.customerInfo[fieldId] = value;
    }

    // Add delivery instructions if any
    const deliveryInstructions =
      document.getElementById("deliveryInstructions")?.value.trim() || "";
    orderData.customerInfo.deliveryInstructions = deliveryInstructions;

    // Set up payment info
    orderData.payment = {
      method: paymentMethod,
      gcashReference:
        paymentMethod === "gcash"
          ? document.getElementById("gcashReference")?.value.trim()
          : "",
    };

    if (paymentMethod === "gcash" && !orderData.payment.gcashReference) {
      throw new Error("GCash reference number is required");
    }

    // Validate cart items
    if (!Array.isArray(cart) || cart.length === 0) {
      throw new Error("Your cart is empty");
    }

    // Prepare cart items
    orderData.cartItems = cart.map((item) => ({
      name: item.name,
      price: parseFloat(item.price),
      quantity: parseInt(item.quantity),
      image: item.image,
    }));

    // Update totals and validate amounts
    const amounts = updateTotals();
    if (!amounts || amounts.finalAmount <= 0) {
      throw new Error("Invalid order amount");
    }

    orderData.amounts = {
      subtotal: orderState.subtotal,
      deliveryFee: orderState.deliveryFee,
      discounts: {
        promo: orderState.discounts.promo.amount,
        senior: orderState.discounts.senior.amount,
        pwd: orderState.discounts.pwd.amount,
      },
      totalDiscount:
        orderState.discounts.promo.amount +
        orderState.discounts.senior.amount +
        orderState.discounts.pwd.amount,
      total: orderState.total,
    };

    // Add discount information
    orderData.discounts = {
      promoCode: activePromoCode?.code || null,
      seniorDiscount:
        document.getElementById("seniorDiscount")?.checked || false,
      seniorId: document.getElementById("seniorId")?.value.trim() || "",
      pwdDiscount: document.getElementById("pwdDiscount")?.checked || false,
      pwdId: document.getElementById("pwdId")?.value.trim() || "",
    };

    // Validate discount IDs if discounts are applied
    if (orderData.discounts.seniorDiscount && !orderData.discounts.seniorId) {
      throw new Error(
        "Senior Citizen ID is required when applying senior discount"
      );
    }
    if (orderData.discounts.pwdDiscount && !orderData.discounts.pwdId) {
      throw new Error("PWD ID is required when applying PWD discount");
    }

    return orderData;
  } catch (error) {
    alert(error.message);
    console.error("Order data validation error:", error);
    return null;
  }
}

// Place order function
async function placeOrder(orderButton) {
  if (!orderButton) return;

  const originalText = orderButton.textContent;
  orderButton.textContent = "Processing...";
  orderButton.disabled = true;

  try {
    // Get validated order data
    const validatedOrderData = await validateAndPrepareOrderData();
    if (!validatedOrderData) {
      throw new Error("Failed to validate order data");
    }

    console.log("Sending validated order data:", validatedOrderData);

    const response = await fetch("process_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(validatedOrderData),
    });

    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`);
    }

    const responseText = await response.text();
    console.log("Raw server response:", responseText);

    let result;
    try {
      result = JSON.parse(responseText);
    } catch (e) {
      console.error("Failed to parse server response:", e);
      throw new Error("Invalid server response format");
    }

    if (!result.success) {
      throw new Error(result.message || "Failed to place order");
    }

    // Clear cart and redirect on success
    localStorage.removeItem("cart");
    window.location.href = `order_confirmation.php?order_id=${result.orderId}`;
  } catch (error) {
    console.error("Error placing order:", error);

    // Determine if we have a structured error message
    let errorMessage = error.message || "Failed to place order";

    // Check if the error message contains multiple validation errors
    if (errorMessage.includes("\n- ")) {
      // Split the message into individual errors
      const errors = errorMessage.split("\n- ");
      const title = errors.shift(); // Remove and store the first line

      // Create a formatted error message
      errorMessage = `
        <div class="error-details">
          <h3>${title}</h3>
          <ul>
            ${errors.map((err) => `<li>${err}</li>`).join("")}
          </ul>
        </div>
      `;

      // Show error in a modal
      showErrorModal(errorMessage);
    } else {
      // Show simple error notification
      showMessage(errorMessage, "error");
    }
  } finally {
    orderButton.textContent = originalText;
    orderButton.disabled = false;
  }
}
