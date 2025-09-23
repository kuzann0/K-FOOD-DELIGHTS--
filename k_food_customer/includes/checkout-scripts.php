<!-- Notification Container -->
<div id="notification-container"></div>

<!-- Hidden Input for Cart Data -->
<input type="hidden" id="cart-data" value="<?php echo htmlspecialchars(json_encode($cartItems)); ?>">
<input type="hidden" id="order-amounts">

<!-- Core Scripts -->
<script src="js/notification-manager.js"></script>
<script src="js/cart-manager.js"></script>
<script src="js/utils.js"></script>
<script src="js/order-confirmation-handler.js"></script>

<!-- Initialize Features -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize managers
    const notificationManager = NotificationManager.getInstance();
    const cartManager = CartManager.getInstance();
    
    // Initialize order handler
    try {
        window.orderConfirmationHandler = new OrderConfirmationHandler();
    } catch (error) {
        console.error('Failed to initialize OrderConfirmationHandler:', error);
        notificationManager.show('System initialization failed. Please refresh the page.', 'error');
    }

    // Initialize amount handling
    const cartItems = JSON.parse(document.getElementById('cart-data')?.value || '[]');
    const initialAmounts = recalculateAmounts(cartItems);
    updateOrderSummary(initialAmounts);
    
    // Listen for cart updates
    document.addEventListener('cartUpdated', (e) => {
        const updatedAmounts = recalculateAmounts(e.detail.items);
        updateOrderSummary(updatedAmounts);
    });

    // Initialize payment method handling
    const paymentInputs = document.querySelectorAll('input[name="paymentMethod"]');
    const gcashDetails = document.getElementById('gcash-details');
    
    paymentInputs.forEach(input => {
        input.addEventListener('change', () => {
            if (gcashDetails) {
                gcashDetails.style.display = input.value === 'gcash' ? 'block' : 'none';
            }
        });
    });

    // Initialize discount section
    const seniorCheckbox = document.getElementById('seniorDiscount');
    const pwdCheckbox = document.getElementById('pwdDiscount');
    const seniorInput = document.querySelector('input[name="seniorId"]')?.parentElement;
    const pwdInput = document.querySelector('input[name="pwdId"]')?.parentElement;

    seniorCheckbox?.addEventListener('change', () => {
        if (seniorInput) {
            seniorInput.style.display = seniorCheckbox.checked ? 'block' : 'none';
        }
        updateAmounts();
    });

    pwdCheckbox?.addEventListener('change', () => {
        if (pwdInput) {
            pwdInput.style.display = pwdCheckbox.checked ? 'block' : 'none';
        }
        updateAmounts();
    });

    // Helper function to update amounts
    function updateAmounts() {
        const cartItems = JSON.parse(document.getElementById('cart-data')?.value || '[]');
        const amounts = recalculateAmounts(cartItems);
        updateOrderSummary(amounts);
    }

    // Handle form submission
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitButton = checkoutForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            try {
                // Show loading state
                submitButton.disabled = true;
                submitButton.textContent = 'Processing Order...';

                // Validate and prepare order data
                const orderData = await prepareOrderData(checkoutForm);

                // Show confirmation modal
                if (!window.orderConfirmationHandler) {
                    throw new Error('Order confirmation handler not initialized');
                }

                await window.orderConfirmationHandler.showConfirmation(orderData);

            } catch (error) {
                console.error('Error processing order:', error);
                notificationManager.show(error.message || 'Failed to process order. Please try again.', 'error');
            } finally {
                // Reset button state
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    }
});
</script>