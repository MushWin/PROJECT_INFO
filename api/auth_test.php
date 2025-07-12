<?php
$isValid = ($username === 'admin' && $password === 'password');

if ($isValid) {
    $config = require_once '../config.php';

    $token = generateJWT(1, $username, 'admin', $config['token_expiration']);
    
    $response = [
        'success' => true,
        'token' => $token,
        'expires' => time() + $config['token_expiration'],
        'user' => [
            'id' => 1,
            'username' => $username,
            'role' => 'admin'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
} else {
    http_response_code(401);
    
    echo json_encode([
        'success' => false,
        'error' => 'Invalid username or password'
    ], JSON_PRETTY_PRINT);
}
?>
