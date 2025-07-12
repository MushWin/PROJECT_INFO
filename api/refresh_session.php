<?php
session_start();
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

$_SESSION['last_activity'] = time();

echo json_encode([
    'success' => true,
    'message' => 'Session refreshed',
    'expires' => time() + 1800 // 30 minutes from now
]);
?>
