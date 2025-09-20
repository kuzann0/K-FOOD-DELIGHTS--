<?php
class OTPHandler {
    private $conn;
    private $semaphore_api_key;
    private $table_name = "otp_verification";

    public function __construct($db) {
        $this->conn = $db;
        // Replace with your actual Semaphore API key
        $this->semaphore_api_key = "your_semaphore_api_key";
        $this->createOTPTable();
    }

    private function createOTPTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT PRIMARY KEY AUTO_INCREMENT,
            phone_number VARCHAR(20) NOT NULL,
            otp_code VARCHAR(6) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP,
            is_verified BOOLEAN DEFAULT FALSE,
            attempts INT DEFAULT 0
        )";
        
        $this->conn->query($query);
    }

    private function generateOTP() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function formatPhoneNumber($phone) {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if number starts with 0
        if (substr($phone, 0, 1) === '0') {
            $phone = '63' . substr($phone, 1);
        }
        // Check if number starts with neither 0 nor 63
        elseif (substr($phone, 0, 2) !== '63') {
            $phone = '63' . $phone;
        }
        
        return $phone;
    }

    private function isValidPhoneNumber($phone) {
        $phone = $this->formatPhoneNumber($phone);
        return preg_match('/^63[0-9]{10}$/', $phone);
    }

    private function sendSMS($phone, $message) {
        $phone = $this->formatPhoneNumber($phone);
        
        $ch = curl_init();
        $parameters = array(
            'apikey' => $this->semaphore_api_key,
            'number' => $phone,
            'message' => $message,
            'sendername' => 'KFOOD'
        );
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }

    public function sendOTP($phone) {
        if (!$this->isValidPhoneNumber($phone)) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format'
            ];
        }

        $phone = $this->formatPhoneNumber($phone);
        $otp = $this->generateOTP();
        
        // Delete any existing OTP for this number
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE phone_number = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        
        // Insert new OTP
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table_name . " 
            (phone_number, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
        $stmt->bind_param("ss", $phone, $otp);
        
        if ($stmt->execute()) {
            $message = "Your K-Food Delights verification code is: {$otp}. Valid for 5 minutes.";
            if ($this->sendSMS($phone, $message)) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send SMS'
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Failed to generate OTP'
        ];
    }

    public function verifyOTP($phone, $otp) {
        $phone = $this->formatPhoneNumber($phone);
        
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " 
            WHERE phone_number = ? AND otp_code = ? AND expires_at > NOW() AND is_verified = FALSE 
            AND attempts < 3");
        $stmt->bind_param("ss", $phone, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Mark OTP as verified
            $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " 
                SET is_verified = TRUE WHERE phone_number = ? AND otp_code = ?");
            $stmt->bind_param("ss", $phone, $otp);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'OTP verified successfully'
            ];
        } else {
            // Increment attempts
            $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " 
                SET attempts = attempts + 1 WHERE phone_number = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            
            return [
                'success' => false,
                'message' => 'Invalid OTP or OTP expired'
            ];
        }
    }
}
