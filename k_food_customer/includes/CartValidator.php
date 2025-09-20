<?php
class CartValidator {
    private $conn;
    private $userId;
    private $errors = [];

    public function __construct($conn, $userId) {
        $this->conn = $conn;
        $this->userId = $userId;
    }

    public function validateCart($items) {
        if (!is_array($items) || empty($items)) {
            $this->errors[] = "Cart is empty";
            return false;
        }

        $valid = true;
        $seenProducts = [];
        $total = 0;

        foreach ($items as $item) {
            // Check for required fields
            if (!isset($item['product_id']) || !isset($item['quantity'])) {
                $this->errors[] = "Invalid item structure";
                $valid = false;
                continue;
            }

            // Validate product existence and get current price
            $stmt = $this->conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND active = 1");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();

            if (!$product) {
                $this->errors[] = "Product '{$item['product_id']}' not found or inactive";
                $valid = false;
                continue;
            }

            // Check for duplicate products
            if (isset($seenProducts[$item['product_id']])) {
                $this->errors[] = "Duplicate product: {$product['name']}";
                $valid = false;
                continue;
            }
            $seenProducts[$item['product_id']] = true;

            // Validate quantity
            if (!is_numeric($item['quantity']) || $item['quantity'] < 1) {
                $this->errors[] = "Invalid quantity for {$product['name']}";
                $valid = false;
                continue;
            }

            // Check stock availability
            if ($item['quantity'] > $product['stock']) {
                $this->errors[] = "Insufficient stock for {$product['name']}";
                $valid = false;
                continue;
            }

            // Calculate item total
            $itemTotal = $product['price'] * $item['quantity'];
            $total += $itemTotal;
        }

        // Validate cart total
        if ($valid && (!isset($items[0]['total']) || abs($items[0]['total'] - $total) > 0.01)) {
            $this->errors[] = "Cart total mismatch";
            $valid = false;
        }

        return $valid;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function reserveStock($items) {
        if (empty($items)) return false;

        $this->conn->begin_transaction();
        try {
            foreach ($items as $item) {
                // Update stock
                $stmt = $this->conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stmt->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update stock for product {$item['product_id']}");
                }
                $stmt->close();

                // Create stock movement record
                $stmt = $this->conn->prepare("INSERT INTO stock_movements (product_id, quantity, movement_type, user_id) VALUES (?, ?, 'checkout', ?)");
                $quantity = -$item['quantity']; // negative for checkout
                $stmt->bind_param("iii", $item['product_id'], $quantity, $this->userId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to record stock movement for product {$item['product_id']}");
                }
                $stmt->close();
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    public function clearCart() {
        $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $this->userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}