<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;

class UserController {
    
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['username']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required']);
            return;
        }

        $username = $data['username'];
        $password = $data['password'];
        
        if ($username === 'admin' && $password === 'password') {
            $config = require_once __DIR__ . '/../../config.php';

            $payload = [
                'iss' => 'http://localhost',
                'aud' => 'http://localhost',
                'iat' => time(),
                'exp' => time() + (60 * 60),
                'user_id' => 1,
                'username' => $username,
                'role' => 'admin'
            ];
            
            $jwt = JWT::encode($payload, $config['jwt_secret'], 'HS256');
            
            echo json_encode([
                'success' => true,
                'token' => $jwt,
                'expires' => $payload['exp'],
                'user' => [
                    'id' => 1,
                    'username' => $username,
                    'role' => 'admin'
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }
    
    public function getUser() {
        $user = $_SERVER['USER'];
        
        echo json_encode([
            'id' => $user->user_id,
            'username' => $user->username,
            'role' => $user->role
        ]);
    }
}
