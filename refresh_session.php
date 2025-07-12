<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Session refreshed successfully']);
} else {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
}
?>
