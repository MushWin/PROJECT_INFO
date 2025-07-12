<?php

/**
 * Log user activity
 * 
 * @param mysqli $conn
 * @param int|null $userId
 * @param string $action
 * @return bool
 */
function logActivity($conn, $userId, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("isss", $userId, $action, $ipAddress, $userAgent);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}


function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header('Location: ../login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Sanitizes user input while preserving appropriate characters like apostrophes
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    $input = trim($input);
    
    $input = htmlspecialchars($input, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);
    
    $input = stripslashes($input);
    
    return $input;
}

/**
 * Validate an email address
 * 
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a random token
 * 
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Upload and process an image file
 * 
 * @param array $file
 * @param string $uploadDir
 * @param array $allowedTypes
 * @param int $maxSize
 * @return array
 */
function uploadImage($file, $uploadDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($fileType, $allowedTypes)) {
        return ['status' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    if ($file['size'] > $maxSize) {
        return ['status' => false, 'message' => 'File is too large. Maximum size: ' . ($maxSize / 1024 / 1024) . 'MB'];
    }
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['status' => false, 'message' => 'Failed to move uploaded file'];
    }
    
    return ['status' => true, 'path' => $destination];
}
?>
