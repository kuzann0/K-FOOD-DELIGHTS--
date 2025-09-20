<?php
require_once '../config.php';
require_once '../includes/auth.php';

class UserSecurity {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Password Reset via Email
    public function initiatePasswordReset($email) {
        $stmt = $this->conn->prepare("SELECT user_id, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            throw new Exception("Email not found");
        }

        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token
        $stmt = $this->conn->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $user['user_id'], $token, $expiry);
        $stmt->execute();

        // Send email
        $resetLink = BASE_URL . "/reset-password.php?token=" . $token;
        $subject = "Password Reset Request - K-Food Delights";
        $message = "Hi " . $user['first_name'] . ",\n\n"
                . "We received a request to reset your password. "
                . "Click the link below to set a new password:\n\n"
                . $resetLink . "\n\n"
                . "This link will expire in 1 hour.\n\n"
                . "If you didn't request this, please ignore this email.\n\n"
                . "Best regards,\nK-Food Delights Team";

        mail($email, $subject, $message);
        return true;
    }

    // Security Questions
    public function setSecurityQuestions($userId, $answers) {
        foreach ($answers as $questionId => $answer) {
            $answerHash = password_hash($answer, PASSWORD_DEFAULT);
            
            $stmt = $this->conn->prepare("
                INSERT INTO user_security_answers (user_id, question_id, answer_hash)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE answer_hash = VALUES(answer_hash)
            ");
            $stmt->bind_param("iis", $userId, $questionId, $answerHash);
            $stmt->execute();
        }
        return true;
    }

    public function verifySecurityAnswers($userId, $answers) {
        foreach ($answers as $questionId => $answer) {
            $stmt = $this->conn->prepare("
                SELECT answer_hash 
                FROM user_security_answers 
                WHERE user_id = ? AND question_id = ?
            ");
            $stmt->bind_param("ii", $userId, $questionId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result || !password_verify($answer, $result['answer_hash'])) {
                return false;
            }
        }
        return true;
    }

    // Two-Factor Authentication
    public function setup2FA($userId) {
        require_once '../vendor/autoload.php'; // For PHPGangsta_GoogleAuthenticator
        $ga = new PHPGangsta_GoogleAuthenticator();
        
        // Generate secret key
        $secret = $ga->createSecret();
        
        // Generate backup codes
        $backupCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $backupCodes[] = bin2hex(random_bytes(4));
        }

        $stmt = $this->conn->prepare("
            INSERT INTO user_2fa (user_id, secret_key, backup_codes)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                secret_key = VALUES(secret_key),
                backup_codes = VALUES(backup_codes),
                is_enabled = 0
        ");
        $codes = json_encode($backupCodes);
        $stmt->bind_param("iss", $userId, $secret, $codes);
        $stmt->execute();

        return [
            'secret' => $secret,
            'qr_code' => $ga->getQRCodeGoogleUrl(
                'K-Food Delights',
                $secret
            ),
            'backup_codes' => $backupCodes
        ];
    }

    public function verify2FA($userId, $code) {
        require_once '../vendor/autoload.php';
        $ga = new PHPGangsta_GoogleAuthenticator();

        $stmt = $this->conn->prepare("
            SELECT secret_key, backup_codes 
            FROM user_2fa 
            WHERE user_id = ? AND is_enabled = 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result) {
            throw new Exception("2FA not enabled");
        }

        // Check if it's a backup code
        $backupCodes = json_decode($result['backup_codes'], true);
        if (in_array($code, $backupCodes)) {
            // Remove used backup code
            $backupCodes = array_diff($backupCodes, [$code]);
            $stmt = $this->conn->prepare("
                UPDATE user_2fa 
                SET backup_codes = ?, last_used_at = CURRENT_TIMESTAMP 
                WHERE user_id = ?
            ");
            $codes = json_encode(array_values($backupCodes));
            $stmt->bind_param("si", $codes, $userId);
            $stmt->execute();
            return true;
        }

        // Verify TOTP code
        return $ga->verifyCode($result['secret_key'], $code, 2);
    }

    public function enable2FA($userId, $code) {
        if ($this->verify2FA($userId, $code)) {
            $stmt = $this->conn->prepare("
                UPDATE user_2fa 
                SET is_enabled = 1, enabled_at = CURRENT_TIMESTAMP 
                WHERE user_id = ?
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return true;
        }
        return false;
    }
}

// API endpoint handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $security = new UserSecurity($conn);

        switch ($data['action']) {
            case 'reset_password':
                $security->initiatePasswordReset($data['email']);
                echo json_encode(['success' => true, 'message' => 'Reset email sent']);
                break;

            case 'setup_2fa':
                $result = $security->setup2FA($data['user_id']);
                echo json_encode(['success' => true, 'data' => $result]);
                break;

            case 'verify_2fa':
                $result = $security->verify2FA($data['user_id'], $data['code']);
                echo json_encode(['success' => true, 'verified' => $result]);
                break;

            case 'enable_2fa':
                $result = $security->enable2FA($data['user_id'], $data['code']);
                echo json_encode(['success' => true, 'enabled' => $result]);
                break;

            case 'set_security_questions':
                $security->setSecurityQuestions($data['user_id'], $data['answers']);
                echo json_encode(['success' => true]);
                break;

            case 'verify_security_answers':
                $result = $security->verifySecurityAnswers($data['user_id'], $data['answers']);
                echo json_encode(['success' => true, 'verified' => $result]);
                break;

            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
