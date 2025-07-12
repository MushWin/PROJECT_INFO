<?php
/**
 * Test utility for certification data updates via API
 * 
 * This script helps debug certification data updates through Postman
 * Test with: http://localhost/PROJECT_INFO/api/cert_test.php
 */

header('Content-Type: application/json');

require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid JSON data',
            'received' => $json
        ]);
        exit;
    }
    
    $certifications = $data['certifications'] ?? null;
    
    echo json_encode([
        'success' => true,
        'message' => 'Data received successfully',
        'received' => [
            'raw' => $certifications,
            'parsed' => is_string($certifications) && isJson($certifications) ? 
                       json_decode($certifications, true) : $certifications
        ],
        'type' => gettype($certifications)
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'API certification test endpoint',
        'usage' => 'POST JSON data to this endpoint to test certification processing',
        'example' => [
            'certifications' => [
                [
                    'title' => 'AWS Certified Developer',
                    'description' => 'Amazon Web Services certification',
                    'date' => '2023-05-15',
                    'image' => 'uploads/certificates/cert1.jpg'
                ]
            ]
        ]
    ]);
}

// Helper function to check if string is valid JSON
function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}
?>
