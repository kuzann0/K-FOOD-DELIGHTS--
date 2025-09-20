<?php<?php

require_once '../config.php';require_once '../config.php';

require_once '../includes/auth.php';require_once '../includes/auth.php';

// Process and optimize the image

// Set secure headers    $image = null;

header('Content-Type: application/json');    

header('X-Content-Type-Options: nosniff');    // Get image info

header('X-Frame-Options: DENY');    list($origWidth, $origHeight, $type) = getimagesize($file['tmp_name']);

    

// Verify login status    // Calculate new dimensions while maintaining aspect ratio

if (!isLoggedIn()) {    $maxDim = 800;

    http_response_code(401);    $ratio = min($maxDim / $origWidth, $maxDim / $origHeight);

    echo json_encode(['success' => false, 'message' => 'Not logged in']);    $newWidth = round($origWidth * $ratio);

    exit();    $newHeight = round($origHeight * $ratio);

}    

    // Create new image with correct orientation

// Verify CSRF token    $exif = @exif_read_data($file['tmp_name']);

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {    $orientation = isset($exif['Orientation']) ? $exif['Orientation'] : 1;

    http_response_code(403);    

    echo json_encode(['success' => false, 'message' => 'Invalid request']);    switch($detectedType) {

    exit();        case 'image/jpeg':

}            $image = imagecreatefromjpeg($file['tmp_name']);

            break;

try {        case 'image/png':

    if (!isset($_FILES['profile_picture'])) {            $image = imagecreatefrompng($file['tmp_name']);

        throw new Exception('No file uploaded');            break;

    }    }

    

    $file = $_FILES['profile_picture'];    // Fix orientation based on EXIF data

        if ($image && $orientation > 1) {

    // More strict MIME type checking        switch ($orientation) {

    $allowedTypes = ['image/jpeg', 'image/png'];            case 3: $image = imagerotate($image, 180, 0); break;

    $maxFileSize = 5 * 1024 * 1024; // 5MB            case 6: $image = imagerotate($image, -90, 0); break;

                case 8: $image = imagerotate($image, 90, 0); break;

    // Double check MIME type with fileinfo        }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);    }

    $detectedType = finfo_file($finfo, $file['tmp_name']);    

    finfo_close($finfo);    // Create new image with correct size

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

    if (!in_array($detectedType, $allowedTypes)) {    

        throw new Exception('Invalid file type. Only JPG and PNG images are allowed.');    // Preserve transparency for PNG

    }    if ($detectedType === 'image/png') {

        imagealphablending($newImage, false);

    // Validate file size        imagesavealpha($newImage, true);

    if ($file['size'] > $maxFileSize) {        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);

        throw new Exception('File size too large. Maximum size is 5MB.');        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);

    }    }ontent-Type: application/json');



    $uploadDir = '../uploads/profile/';if (!isLoggedIn()) {

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {    http_response_code(401);

        throw new Exception('Failed to create upload directory');    echo json_encode(['success' => false, 'message' => 'Not logged in']);

    }    exit();

}

    // Generate unique filename

    $extension = $detectedType === 'image/jpeg' ? 'jpg' : 'png';// Verify CSRF token

    $filename = uniqid('profile_') . '_' . time() . '.' . $extension;if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {

    $targetPath = $uploadDir . $filename;    http_response_code(403);

    echo json_encode(['success' => false, 'message' => 'Invalid request']);

    // Get image info    exit();

    list($origWidth, $origHeight, $type) = getimagesize($file['tmp_name']);}

    

    // Calculate new dimensions while maintaining aspect ratiotry {

    $maxDim = 800;    if (!isset($_FILES['profile_picture'])) {

    $ratio = min($maxDim / $origWidth, $maxDim / $origHeight);        throw new Exception('No file uploaded');

    $newWidth = round($origWidth * $ratio);    }

    $newHeight = round($origHeight * $ratio);

        $file = $_FILES['profile_picture'];

    // Create new image with correct orientation    

    $exif = @exif_read_data($file['tmp_name']);    // More strict MIME type checking

    $orientation = isset($exif['Orientation']) ? $exif['Orientation'] : 1;    $allowedTypes = ['image/jpeg', 'image/png'];

        $maxFileSize = 5 * 1024 * 1024; // 5MB

    $image = null;    

    switch($detectedType) {    // Double check MIME type with fileinfo

        case 'image/jpeg':    $finfo = finfo_open(FILEINFO_MIME_TYPE);

            $image = imagecreatefromjpeg($file['tmp_name']);    $detectedType = finfo_file($finfo, $file['tmp_name']);

            break;    finfo_close($finfo);

        case 'image/png':    

            $image = imagecreatefrompng($file['tmp_name']);    if (!in_array($detectedType, $allowedTypes)) {

            break;        throw new Exception('Invalid file type. Only JPG and PNG images are allowed.');

        default:    }

            throw new Exception('Unsupported image type');

    }    // Validate MIME type against what browser reported

        if ($detectedType !== $file['type']) {

    if (!$image) {        throw new Exception('File type mismatch detected.');

        throw new Exception('Failed to process image');    }

    }    

        // Validate file type

    // Fix orientation based on EXIF data    if (!in_array($file['type'], $allowedTypes)) {

    if ($orientation > 1) {        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');

        switch ($orientation) {    }

            case 3: $image = imagerotate($image, 180, 0); break;

            case 6: $image = imagerotate($image, -90, 0); break;    // Validate file size

            case 8: $image = imagerotate($image, 90, 0); break;    if ($file['size'] > $maxFileSize) {

        }        throw new Exception('File size too large. Maximum size is 5MB.');

    }    }

    

    // Create new image with correct size    $uploadDir = '../uploads/profile/';

    $newImage = imagecreatetruecolor($newWidth, $newHeight);    if (!file_exists($uploadDir)) {

            mkdir($uploadDir, 0777, true);

    // Preserve transparency for PNG    }

    if ($detectedType === 'image/png') {

        imagealphablending($newImage, false);    // Generate unique filename

        imagesavealpha($newImage, true);    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);    $filename = uniqid('profile_') . '_' . time() . '.' . $extension;

        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);    $targetPath = $uploadDir . $filename;

    }

        // Get current user's info to clean up old image

    // Copy and resize the image with high quality    $user = getCurrentUser($conn);

    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);    if ($user && !empty($user['profile_picture'])) {

            $oldFile = '../uploads/profile/' . $user['profile_picture'];

    // Apply subtle sharpening        if (file_exists($oldFile) && is_file($oldFile)) {

    $sharpen = array(            unlink($oldFile);

        array(0.0, -1.0, 0.0),        }

        array(-1.0, 5.0, -1.0),    }

        array(0.0, -1.0, 0.0)

    );    // Process and optimize the image

    imageconvolution($newImage, $sharpen, 1, 0);    $image = null;

    switch($detectedType) {

    // Clean up old profile picture        case 'image/jpeg':

    $user = getCurrentUser($conn);            $image = @imagecreatefromjpeg($file['tmp_name']);

    if ($user && !empty($user['profile_picture']) && $user['profile_picture'] !== 'default.png') {            break;

        $oldFile = $uploadDir . $user['profile_picture'];        case 'image/png':

        if (file_exists($oldFile) && is_file($oldFile)) {            $image = @imagecreatefrompng($file['tmp_name']);

            @unlink($oldFile);            break;

        }    }

    }

        // Verify image creation was successful

    // Save the optimized image    if (!$image) {

    $success = false;        throw new Exception('Failed to process image. The file might be corrupted.');

    switch($detectedType) {

        case 'image/jpeg':    if (!$image) {

            $success = imagejpeg($newImage, $targetPath, 90);        throw new Exception('Failed to process image.');

            break;    }

        case 'image/png':

            $success = imagepng($newImage, $targetPath, 8);    // Resize if too large

            break;    $maxDimension = 500;

    }    $width = imagesx($image);

        $height = imagesy($image);

    // Clean up resources

    imagedestroy($image);    if ($width > $maxDimension || $height > $maxDimension) {

    imagedestroy($newImage);        if ($width > $height) {

                $newWidth = $maxDimension;

    if (!$success) {            $newHeight = ($height / $width) * $maxDimension;

        throw new Exception('Failed to save optimized image');        } else {

    }            $newHeight = $maxDimension;

            $newWidth = ($width / $height) * $maxDimension;

    // Update database with new filename        }

    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");

    $stmt->bind_param("si", $filename, $_SESSION['user_id']);        $resized = imagecreatetruecolor($newWidth, $newHeight);

            

    if (!$stmt->execute()) {        // Preserve transparency for PNG images

        // Remove the new file if DB update failed        if ($file['type'] === 'image/png') {

        @unlink($targetPath);            imagealphablending($resized, false);

        throw new Exception('Failed to update database');            imagesavealpha($resized, true);

    }        }

    

    echo json_encode([        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        'success' => true,         $image = $resized;

        'filename' => $filename,    }

        'dimensions' => [

            'width' => $newWidth,    // Copy and resize the image with high quality

            'height' => $newHeight    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        ]    

    ]);    // Apply subtle sharpening

    $sharpen = array(

} catch (Exception $e) {        array(0.0, -1.0, 0.0),

    error_log("Profile picture upload error: " . $e->getMessage());        array(-1.0, 5.0, -1.0),

    http_response_code(500);        array(0.0, -1.0, 0.0)

    echo json_encode([    );

        'success' => false,    imageconvolution($newImage, $sharpen, 1, 0);

        'message' => $e->getMessage()    

    ]);    // Save the optimized image with high quality

}    switch($detectedType) {
        case 'image/jpeg':
            imagejpeg($newImage, $targetPath, 90);
            break;
        case 'image/png':
            imagepng($newImage, $targetPath, 8);
            break;
            break;
    }

    imagedestroy($image);

    // Fetch old profile picture before updating
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $oldPicture = $stmt->get_result()->fetch_assoc()['profile_picture'];

    // Update database with new filename
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
    $stmt->bind_param("si", $filename, $_SESSION['user_id']);
    if ($stmt->execute()) {
        // Delete old profile picture if it exists and is not the default
        if ($oldPicture && $oldPicture !== 'default.png' && file_exists($uploadDir . $oldPicture)) {
            unlink($uploadDir . $oldPicture);
        }
        echo json_encode(['success' => true, 'filename' => $filename]);
    } else {
        // Remove the new file if DB update failed
        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
        echo json_encode(['success' => false, 'message' => 'Error updating database']);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
