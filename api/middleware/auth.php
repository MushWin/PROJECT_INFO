<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class AuthMiddleware {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function authenticate() {
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'];
            
            if ($apiKey === $this->config['api_key']) {
                return true;
            }
        }
        
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
            try {
                $decoded = JWT::decode($jwt, new Key($this->config['jwt_secret'], 'HS256'));
                
                $_SERVER['USER'] = $decoded;
                return true;
                
            } catch (ExpiredException $e) {
                $this->unauthorized('Token has expired');
            } catch (Exception $e) {
                $this->unauthorized('Invalid token');
            }
        }
        
        $this->unauthorized('Authentication required');
    }
    
    private function unauthorized($message) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => $message]);
        exit;
    }
}
