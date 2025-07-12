<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    try {
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            logActivity($conn, $userId, 'User logged out');
        }
        $checkStmt->close();
    } catch (Exception $e) {
        error_log("Error logging logout: " . $e->getMessage());
    }
}

$timeout = isset($_GET['timeout']) && $_GET['timeout'] == 1;

// Redirect to login page with message if timeout
if ($timeout) {
    header('Location: login.php?timeout=1');
} else {
    header('Location: index.php');
}
exit;
?>
