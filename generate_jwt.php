<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;

if (php_sapi_name() === 'cli' || isset($_GET['generate'])) {
    $configFile = __DIR__ . '/config.php';
    
    if (!file_exists($configFile)) {
        die(json_encode(['error' => 'Configuration file not found']));
    }
    
    $config = require_once $configFile;
    
    if (!is_array($config) || !isset($config['jwt_secret'])) {
        die(json_encode(['error' => 'Invalid configuration']));
    }
    
    $userId = $_GET['user_id'] ?? 1;
    $username = $_GET['username'] ?? 'test_user';
    $role = $_GET['role'] ?? 'user';
    $expiry = $_GET['expiry'] ?? 3600;
    
    $payload = [
        "iss" => "http://localhost",
        "iat" => time(),
        "exp" => time() + $expiry,
        "user_id" => $userId,
        "username" => $username,
        "role" => $role
    ];

    try {
        $jwt = JWT::encode($payload, $config['jwt_secret'], 'HS256');
        echo json_encode(["token" => $jwt]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    
} else {
    function generateJWT($userId, $username, $role = 'user', $expiry = 3600) {
        $configFile = __DIR__ . '/config.php';
        if (!file_exists($configFile)) {
            throw new Exception("Configuration file not found");
        }
        
        $config = require $configFile;
        if (!is_array($config) || !isset($config['jwt_secret'])) {
            throw new Exception("JWT secret not configured");
        }
        
        $payload = [
            "iss" => "http://localhost",
            "iat" => time(),
            "exp" => time() + $expiry,
            "user_id" => $userId,
            "username" => $username,
            "role" => $role
        ];
        
        return JWT::encode($payload, $config['jwt_secret'], 'HS256');
    }
}
