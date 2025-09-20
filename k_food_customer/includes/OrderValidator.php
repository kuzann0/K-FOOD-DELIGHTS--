<?php
class OrderValidationException extends Exception {
    private $validationErrors;

    public function __construct($message, array $validationErrors = []) {
        parent::__construct($message);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors() {
        return $this->validationErrors;
    }
}

class PaymentException extends Exception {
    private $paymentDetails;

    public function __construct($message, array $paymentDetails = []) {
        parent::__construct($message);
        $this->paymentDetails = $paymentDetails;
    }

    public function getPaymentDetails() {
        return $this->paymentDetails;
    }
}

class OrderValidator {
    private $conn;
    private $errorHandler;

    public function __construct($conn, $errorHandler) {
        $this->conn = $conn;
        $this->errorHandler = $errorHandler;
    }

    public function validateOrder($orderData) {
        $errors = [];

        // Validate customer information
        if (!$this->validateCustomerInfo($orderData['customerInfo'] ?? [], $errors)) {
            throw new OrderValidationException('Invalid customer information', $errors);
        }

        // Validate cart items
        if (!$this->validateCartItems($orderData['items'] ?? [], $errors)) {
            throw new OrderValidationException('Invalid cart items', $errors);
        }

        // Validate payment information
        if (!$this->validatePaymentInfo($orderData['payment'] ?? [], $errors)) {
            throw new OrderValidationException('Invalid payment information', $errors);
        }

        // Validate amounts
        if (!$this->validateAmounts($orderData['amounts'] ?? [], $orderData['items'] ?? [], $errors)) {
            throw new OrderValidationException('Invalid order amounts', $errors);
        }

        return true;
    }

    private function validateCustomerInfo($customerInfo, &$errors) {
        $required = ['name', 'email', 'phone', 'address'];
        foreach ($required as $field) {
            if (empty($customerInfo[$field])) {
                $errors['customerInfo'][] = ucfirst($field) . ' is required';
                return false;
            }
        }

        // Validate email format
        if (!filter_var($customerInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['customerInfo'][] = 'Invalid email format';
            return false;
        }

        // Validate phone format (assumes Philippine format)
        if (!preg_match('/^09[0-9]{9}$/', $customerInfo['phone'])) {
            $errors['customerInfo'][] = 'Invalid phone number format';
            return false;
        }

        return true;
    }

    private function validateCartItems($items, &$errors) {
        if (!is_array($items) || empty($items)) {
            $errors['items'][] = 'Cart is empty';
            return false;
        }

        foreach ($items as $index => $item) {
            if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                $errors['items'][] = 'Invalid item structure';
                return false;
            }

            // Validate product existence and stock
            $stmt = $this->conn->prepare("SELECT stock, price FROM products WHERE id = ? AND active = 1");
            $stmt->bind_param('i', $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            if (!$product) {
                $errors['items'][] = "Product {$item['product_id']} not found or inactive";
                return false;
            }

            if ($item['quantity'] > $product['stock']) {
                $errors['items'][] = "Insufficient stock for product {$item['product_id']}";
                return false;
            }

            if (abs($item['price'] - $product['price']) > 0.01) {
                $errors['items'][] = "Price mismatch for product {$item['product_id']}";
                return false;
            }
        }

        return true;
    }

    private function validatePaymentInfo($payment, &$errors) {
        if (!isset($payment['method'])) {
            $errors['payment'][] = 'Payment method is required';
            return false;
        }

        if (!in_array($payment['method'], ['cash', 'gcash'])) {
            $errors['payment'][] = 'Invalid payment method';
            return false;
        }

        if ($payment['method'] === 'gcash') {
            if (!isset($payment['gcashNumber']) || !preg_match('/^09[0-9]{9}$/', $payment['gcashNumber'])) {
                $errors['payment'][] = 'Invalid GCash number';
                return false;
            }
            if (!isset($payment['gcashReference']) || !preg_match('/^[A-Za-z0-9]{6,15}$/', $payment['gcashReference'])) {
                $errors['payment'][] = 'Invalid GCash reference number';
                return false;
            }
        }

        return true;
    }

    private function validateAmounts($amounts, $items, &$errors) {
        // Constants
        $MIN_ORDER = 100.00;
        $MAX_ORDER = 10000.00;
        $DELIVERY_FEE = 50.00;
        $FREE_DELIVERY_THRESHOLD = 1000.00;

        // Calculate expected subtotal
        $calculatedSubtotal = array_reduce($items, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0.0);

        if (abs($calculatedSubtotal - ($amounts['subtotal'] ?? 0)) > 0.01) {
            $errors['amounts'][] = 'Subtotal calculation mismatch';
            return false;
        }

        // Validate subtotal range
        if ($calculatedSubtotal < $MIN_ORDER) {
            $errors['amounts'][] = "Minimum order amount is ₱{$MIN_ORDER}";
            return false;
        }
        if ($calculatedSubtotal > $MAX_ORDER) {
            $errors['amounts'][] = "Maximum order amount is ₱{$MAX_ORDER}";
            return false;
        }

        // Validate delivery fee
        $expectedDeliveryFee = $calculatedSubtotal >= $FREE_DELIVERY_THRESHOLD ? 0 : $DELIVERY_FEE;
        if (abs(($amounts['delivery_fee'] ?? 0) - $expectedDeliveryFee) > 0.01) {
            $errors['amounts'][] = 'Invalid delivery fee';
            return false;
        }

        // Validate total
        $expectedTotal = $calculatedSubtotal + $expectedDeliveryFee - ($amounts['discount'] ?? 0);
        if (abs(($amounts['total'] ?? 0) - $expectedTotal) > 0.01) {
            $errors['amounts'][] = 'Total amount calculation mismatch';
            return false;
        }

        return true;
    }
}