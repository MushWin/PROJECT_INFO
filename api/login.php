<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

$config = require_once __DIR__ . '/../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit;
}

if ($config['debug_mode']) {
    error_log('API Login attempt: ' . print_r($data, true));
}

$username_email = trim($data['username']);
$password = $data['password'];


try {
    $is_email = filter_var($username_email, FILTER_VALIDATE_EMAIL);
    
    if ($is_email) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    }
    
    $stmt->bind_param("s", $username_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            require_once __DIR__ . '/../generate_jwt.php';
            
            $role = 'user';
            
            try {
                $column_check_query = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
                
                if ($column_check_query && $column_check_query->num_rows > 0) {
                    $role_query = $conn->prepare("SELECT role FROM users WHERE id = ?");
                    $role_query->bind_param("i", $user['id']);
                    $role_query->execute();
                    $role_result = $role_query->get_result();
                    
                    if ($role_result && $role_result->num_rows === 1) {
                        $role_row = $role_result->fetch_assoc();
                        $role = $role_row['role'] ?? 'user';
                    }
                }
            } catch (Exception $e) {
                error_log("Error checking role: " . $e->getMessage());
            }
            
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();

            logActivity($conn, $user['id'], 'API Login successful');

            $token = generateJWT($user['id'], $user['username'], $role, $config['token_expiration']);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'expires' => time() + $config['token_expiration'],
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $role
                ]
            ]);
            
        } else {
            logActivity($conn, null, 'API Failed login attempt for: ' . $username_email);
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid username/email or password']);
        }
    } else {
        logActivity($conn, null, 'API Failed login attempt for non-existent user: ' . $username_email);
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid username/email or password']);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . ($config['debug_mode'] ? $e->getMessage() : 'Please try again later')]);
}
exit;
