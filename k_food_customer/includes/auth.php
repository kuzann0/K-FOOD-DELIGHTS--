<?php
// Set secure session parameters before session starts
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    session_start();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        // Store the requested URL in the session
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

// Function to get current user data
function getCurrentUser($conn) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // First, check if last_login column exists
    $checkColumn = $conn->query("
        SELECT COUNT(*) as exists_count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'last_login'
    ");
    
    $columnExists = $checkColumn->fetch_assoc()['exists_count'] > 0;
    
    // Prepare the SQL based on whether the column exists
    if ($columnExists) {
        $sql = "SELECT user_id, username, email, first_name, last_name, phone, address, profile_picture, last_login FROM users WHERE user_id = ?";
    } else {
        $sql = "SELECT user_id, username, email, first_name, last_name, phone, address, profile_picture FROM users WHERE user_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // If last_login doesn't exist or is null, set it to current timestamp
    if (!isset($user['last_login']) || !$user['last_login']) {
        $user['last_login'] = date('Y-m-d H:i:s');
    }
    
    return $user;
}

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number
function isValidPhone($phone) {
    return preg_match("/^[0-9+\-\s()]*$/", $phone);
}

// Function to validate password strength
function isValidPassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 &&
           preg_match("/[A-Z]/", $password) &&
           preg_match("/[a-z]/", $password) &&
           preg_match("/[0-9]/", $password);
}

// Function to handle file upload
function handleFileUpload($file, $targetDir, $oldFile = null) {
    // Validate directory
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return ["success" => false, "message" => "Upload directory error."];
    }

    // Basic file checks
    if (!isset($file["tmp_name"]) || !is_uploaded_file($file["tmp_name"])) {
        return ["success" => false, "message" => "Invalid file upload."];
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    // Verify MIME type
    $allowedMimes = ['image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        return ["success" => false, "message" => "Invalid file type."];
    }
    
    // Check if image file is actual image
    $check = @getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File is too large (max 5MB)."];
    }
    
    // Generate unique filename with timestamp
    $newFilename = time() . '_' . uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFilename;
    
    // Delete old file if exists
    if ($oldFile && file_exists($targetDir . $oldFile)) {
        @unlink($targetDir . $oldFile);
    }
    
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Optimize image
        optimizeImage($targetFile, $imageFileType);
        return ["success" => true, "filename" => $newFilename];
    } else {
        return ["success" => false, "message" => "Error uploading file."];
    }
}

// Function to optimize uploaded images
function optimizeImage($filepath, $type) {
    if (!function_exists('imagecreatefromjpeg')) {
        return false;
    }

    $maxWidth = 1200;
    $maxHeight = 1200;
    
    list($width, $height) = getimagesize($filepath);
    
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true;
    }
    
    $ratio = min($maxWidth/$width, $maxHeight/$height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    switch($type) {
        case 'jpeg':
        case 'jpg':
            $source = imagecreatefromjpeg($filepath);
            imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagejpeg($newImage, $filepath, 80);
            break;
        case 'png':
            $source = imagecreatefrompng($filepath);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagepng($newImage, $filepath, 8);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($newImage);
    return true;
}
?>
