<div id="orderModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2 style="margin-bottom: 20px;">Place Your Order</h2>
    <form id="orderForm">
      <div class="form-group">
        <label for="customerName">Full Name:</label>
        <input type="text" id="customerName" name="customerName" required>
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone" required>
      </div>
      <div class="form-group">
        <label for="address">Delivery Address:</label>
        <textarea id="address" name="address" required></textarea>
      </div>
      <div class="form-group">
        <label for="product">Product:</label>
        <input type="text" id="product" name="product" readonly>
      </div>
      <div class="form-group">
        <label for="quantity">Quantity:</label>
        <input type="number" id="quantity" name="quantity" min="1" value="1" required>
      </div>
      <div class="form-group">
        <label for="deliveryDate">Preferred Delivery Date:</label>
        <input type="date" id="deliveryDate" name="deliveryDate" required>
      </div>
      <div class="form-group">
        <label for="totalPrice">Total Price:</label>
        <input type="text" id="totalPrice" name="totalPrice" readonly>
      </div>
      <button type="submit" class="submit-btn">Place Order</button>
    </form>
  </div>
</div>
