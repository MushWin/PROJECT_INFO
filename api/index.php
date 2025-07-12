<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once '../vendor/autoload.php';
require_once '../api/middleware/auth.php';
require_once '../api/controllers/UserController.php';
require_once '../api/controllers/DataController.php';

$config = require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

$uri_parts = explode('/api/', $request_uri);
$path = isset($uri_parts[1]) ? trim($uri_parts[1]) : '';

$path = explode('?', $path)[0];

try {
    $auth = new AuthMiddleware($config);
    $userController = new UserController();
    $dataController = new DataController();
    
    if ($path === 'login' && $request_method === 'POST') {
        $userController->login();
        exit;
    }

    $auth->authenticate();
    
    switch (true) {
        case ($path === 'user' && $request_method === 'GET'):
            $userController->getUser();
            break;
            
        case ($path === 'data' && $request_method === 'GET'):
            $dataController->getData();
            break;
            
        case ($path === 'data' && $request_method === 'POST'):
            $dataController->createData();
            break;
            
        case (preg_match('/^data\/(\d+)$/', $path, $matches) && $request_method === 'GET'):
            $dataController->getDataById($matches[1]);
            break;
            
        case (preg_match('/^data\/(\d+)$/', $path, $matches) && $request_method === 'PUT'):
            $dataController->updateData($matches[1]);
            break;
            
        case (preg_match('/^data\/(\d+)$/', $path, $matches) && $request_method === 'DELETE'):
            $dataController->deleteData($matches[1]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
