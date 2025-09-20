class CartManager {
    private $items = [];
    private $total = 0;
    private $subtotal = 0;
    private $deliveryFee = 50;
    private $discounts = [];
    
    public function __construct() {
        $this->loadCartItems();
    }
    
    public function loadCartItems() {
        if (isset($_SESSION['cart_items'])) {
            $this->items = $_SESSION['cart_items'];
        }
        $this->calculateTotals();
    }
    
    public function calculateTotals() {
        $this->subtotal = 0;
        foreach ($this->items as $item) {
            $this->subtotal += $item['price'] * $item['quantity'];
        }
        
        // Apply any active discounts
        $this->calculateDiscounts();
        
        // Add delivery fee and calculate final total
        $this->total = $this->subtotal - $this->getDiscountAmount() + $this->deliveryFee;
    }
    
    private function calculateDiscounts() {
        $this->discounts = [];
        
        // Senior Citizen Discount (20%)
        if (isset($_POST['seniorDiscount']) && !empty($_POST['seniorId'])) {
            $this->discounts['senior'] = [
                'type' => 'percentage',
                'amount' => 0.20,
                'value' => $this->subtotal * 0.20
            ];
        }
        
        // PWD Discount (15%)
        if (isset($_POST['pwdDiscount']) && !empty($_POST['pwdId'])) {
            $this->discounts['pwd'] = [
                'type' => 'percentage',
                'amount' => 0.15,
                'value' => $this->subtotal * 0.15
            ];
        }
    }
    
    public function getDiscountAmount() {
        $total = 0;
        foreach ($this->discounts as $discount) {
            $total += $discount['value'];
        }
        return $total;
    }
    
    public function validateCart() {
        if (empty($this->items)) {
            throw new Exception('Your cart is empty');
        }
        
        foreach ($this->items as $item) {
            if (!isset($item['id']) || !isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
                throw new Exception('Invalid cart item format');
            }
            
            if ($item['price'] <= 0) {
                throw new Exception('Invalid item price');
            }
            
            if ($item['quantity'] <= 0) {
                throw new Exception('Invalid item quantity');
            }
        }
        
        if ($this->total <= 0) {
            throw new Exception('Invalid order total');
        }
        
        return true;
    }
    
    public function getTotal() {
        return $this->total;
    }
    
    public function getSubtotal() {
        return $this->subtotal;
    }
    
    public function getDeliveryFee() {
        return $this->deliveryFee;
    }
    
    public function getItems() {
        return $this->items;
    }
    
    public function getDiscounts() {
        return $this->discounts;
    }
    
    public function clear() {
        $this->items = [];
        $this->total = 0;
        $this->subtotal = 0;
        $this->discounts = [];
        unset($_SESSION['cart_items']);
    }
}