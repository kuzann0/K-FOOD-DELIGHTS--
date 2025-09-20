<?php
class OrderCalculator {
    private $conn;
    private $userId;
    private $errors = [];
    
    // Constants for business rules
    const MIN_ORDER_AMOUNT = 100.00;
    const MAX_ORDER_AMOUNT = 10000.00;
    const BASE_DELIVERY_FEE = 50.00;
    const FREE_DELIVERY_THRESHOLD = 1000.00;
    const MAX_DISCOUNT_PERCENTAGE = 25.00;

    public function __construct($conn, $userId) {
        $this->conn = $conn;
        $this->userId = $userId;
    }

    public function calculateOrderAmounts($items, $promoCode = null) {
        try {
            $amounts = [
                'subtotal' => 0,
                'delivery_fee' => 0,
                'discount' => 0,
                'total' => 0
            ];

            // Calculate subtotal
            foreach ($items as $item) {
                // Validate item structure
                if (!isset($item['price']) || !isset($item['quantity'])) {
                    throw new Exception("Invalid item structure");
                }

                // Validate price and quantity
                $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
                $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);

                if ($price === false || $price < 0) {
                    throw new Exception("Invalid price for item: {$item['name']}");
                }
                if ($quantity === false || $quantity < 1) {
                    throw new Exception("Invalid quantity for item: {$item['name']}");
                }

                $amounts['subtotal'] += $price * $quantity;
            }

            // Round subtotal to 2 decimal places
            $amounts['subtotal'] = round($amounts['subtotal'], 2);

            // Validate minimum order amount
            if ($amounts['subtotal'] < self::MIN_ORDER_AMOUNT) {
                throw new Exception("Minimum order amount is ₱" . number_format(self::MIN_ORDER_AMOUNT, 2));
            }

            // Validate maximum order amount
            if ($amounts['subtotal'] > self::MAX_ORDER_AMOUNT) {
                throw new Exception("Maximum order amount is ₱" . number_format(self::MAX_ORDER_AMOUNT, 2));
            }

            // Calculate delivery fee
            $amounts['delivery_fee'] = $amounts['subtotal'] >= self::FREE_DELIVERY_THRESHOLD ? 
                0 : self::BASE_DELIVERY_FEE;

            // Apply promo code if provided
            if ($promoCode) {
                $discount = $this->applyPromoCode($promoCode, $amounts['subtotal']);
                if ($discount !== false) {
                    $amounts['discount'] = $discount;
                }
            }

            // Calculate final total
            $amounts['total'] = $amounts['subtotal'] + $amounts['delivery_fee'] - $amounts['discount'];

            // Final validation
            if ($amounts['total'] < 0) {
                throw new Exception("Invalid total amount calculation");
            }

            return $amounts;

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    private function applyPromoCode($code, $subtotal) {
        try {
            // Validate promo code
            $stmt = $this->conn->prepare("
                SELECT discount_type, discount_value, min_purchase, max_discount
                FROM promo_codes
                WHERE code = ?
                AND start_date <= CURRENT_TIMESTAMP
                AND end_date >= CURRENT_TIMESTAMP
                AND is_active = 1
                AND (usage_limit = 0 OR uses < usage_limit)
            ");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $result = $stmt->get_result();
            $promo = $result->fetch_assoc();
            $stmt->close();

            if (!$promo) {
                throw new Exception("Invalid or expired promo code");
            }

            // Check minimum purchase requirement
            if ($subtotal < $promo['min_purchase']) {
                throw new Exception("Minimum purchase of ₱" . number_format($promo['min_purchase'], 2) . " required for this promo code");
            }

            // Calculate discount
            $discount = 0;
            if ($promo['discount_type'] === 'percentage') {
                $discount = $subtotal * ($promo['discount_value'] / 100);
                // Cap percentage discount
                $maxDiscount = $subtotal * (self::MAX_DISCOUNT_PERCENTAGE / 100);
                $discount = min($discount, $maxDiscount);
            } else { // fixed amount
                $discount = $promo['discount_value'];
            }

            // Apply maximum discount cap if set
            if ($promo['max_discount'] > 0) {
                $discount = min($discount, $promo['max_discount']);
            }

            // Round discount to 2 decimal places
            return round($discount, 2);

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }
}