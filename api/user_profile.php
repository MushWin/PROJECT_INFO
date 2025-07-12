<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
$config = require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/middleware/auth.php';

$auth = new AuthMiddleware($config);

if ($auth->authenticate()) {
    try {
        $userId = $_SERVER['USER']->user_id ?? null;
        
        if (!$userId) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' OR username = 'Dwin' LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $userId = $user['id'];
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No user found']);
                exit;
            }
        }
        
        $userStmt = $conn->prepare("SELECT id, username, email, first_name, last_name, phone, created_at, last_login, is_active FROM users WHERE id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult && $userResult->num_rows > 0) {
            $user = $userResult->fetch_assoc();
            
            $portfolioStmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ?");
            $portfolioStmt->bind_param("i", $userId);
            $portfolioStmt->execute();
            $portfolioResult = $portfolioStmt->get_result();
            
            $portfolio = null;
            if ($portfolioResult && $portfolioResult->num_rows > 0) {
                $portfolio = $portfolioResult->fetch_assoc();
            }
            
            $response = [
                'success' => true,
                'user' => $user,
                'portfolio' => $portfolio
            ];
            
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $config['debug_mode'] ? $e->getMessage() : 'Server error']);
    }
    
    $conn->close();
}
?>
