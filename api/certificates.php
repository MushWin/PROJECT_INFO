<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
$config = require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/middleware/auth.php';

$auth = new AuthMiddleware($config);

error_log("Certificate API Request: " . $_SERVER['REQUEST_URI']);

try {
    if (!$auth->authenticate()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $userId = $_SERVER['USER']->user_id ?? null;
    
    if (!$userId) {
        $userId = 2;
    }
    
    $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $urlParts = explode('/', trim($urlPath, '/'));
    
    $certPosition = array_search('certificates', $urlParts);
    if ($certPosition === false) {
        throw new Exception("Invalid URL format");
    }
    
    $certIndex = null;
    if (isset($urlParts[$certPosition + 2])) {
        $certIndex = intval($urlParts[$certPosition + 2]);
    }
    
    error_log("URL parts: " . json_encode($urlParts));
    error_log("Certificate position: " . $certPosition);
    error_log("Certificate index: " . ($certIndex !== null ? $certIndex : "null"));
    
    $portfolioStmt = $conn->prepare("SELECT certifications FROM portfolio WHERE user_id = ?");
    $portfolioStmt->bind_param("i", $userId);
    $portfolioStmt->execute();
    $portfolioResult = $portfolioStmt->get_result();
    
    if ($portfolioResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Portfolio not found']);
        exit;
    }
    
    $portfolio = $portfolioResult->fetch_assoc();
    $certificationsJson = $portfolio['certifications'] ?? '[]';
    
    if (empty($certificationsJson)) {
        $certificationsJson = '[]';
    }
    
    $certifications = json_decode($certificationsJson, true);
    if ($certifications === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON parse error: " . json_last_error_msg());
        $certifications = [];
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($certIndex !== null) {
                if (isset($certifications[$certIndex])) {
                    echo json_encode([
                        'success' => true, 
                        'data' => $certifications[$certIndex]
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Certificate not found'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true, 
                    'data' => $certifications
                ]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['title'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Invalid certificate data. Title is required.'
                ]);
                break;
            }
            $newCert = [
                'title' => sanitizeInput($data['title']),
                'description' => sanitizeInput($data['description'] ?? ''),
                'date' => sanitizeInput($data['date'] ?? ''),
                'image' => sanitizeInput($data['image'] ?? '')
            ];
            
            $certifications[] = $newCert;
            
            $updateStmt = $conn->prepare("UPDATE portfolio SET certifications = ? WHERE user_id = ?");
            $jsonCerts = json_encode($certifications);
            $updateStmt->bind_param("si", $jsonCerts, $userId);
            $updateStmt->execute();
            
            if ($updateStmt->affected_rows > 0) {
                http_response_code(201);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Certificate added successfully',
                    'data' => $newCert,
                    'index' => count($certifications) - 1
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Failed to add certificate'
                ]);
            }
            break;
            
        case 'PUT':
            if ($certIndex === null) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Certificate index is required'
                ]);
                break;
            }
            
            if (!isset($certifications[$certIndex])) {
                http_response_code(404);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Certificate not found'
                ]);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Invalid input data'
                ]);
                break;
            }
            
            if (isset($data['title'])) {
                $certifications[$certIndex]['title'] = sanitizeInput($data['title']);
            }
            if (isset($data['description'])) {
                $certifications[$certIndex]['description'] = sanitizeInput($data['description']);
            }
            if (isset($data['date'])) {
                $certifications[$certIndex]['date'] = sanitizeInput($data['date']);
            }
            if (isset($data['image'])) {
                $certifications[$certIndex]['image'] = sanitizeInput($data['image']);
            }
            
            $updateStmt = $conn->prepare("UPDATE portfolio SET certifications = ? WHERE user_id = ?");
            $jsonCerts = json_encode($certifications);
            $updateStmt->bind_param("si", $jsonCerts, $userId);
            $updateStmt->execute();
            
            if ($updateStmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Certificate updated successfully',
                    'data' => $certifications[$certIndex]
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'No changes made to certificate'
                ]);
            }
            break;
            
        case 'DELETE':
            if ($certIndex === null) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Certificate index is required'
                ]);
                break;
            }
            
            if (!isset($certifications[$certIndex])) {
                http_response_code(404);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Certificate not found'
                ]);
                break;
            }
            
            array_splice($certifications, $certIndex, 1);
            
            $updateStmt = $conn->prepare("UPDATE portfolio SET certifications = ? WHERE user_id = ?");
            $jsonCerts = json_encode($certifications);
            $updateStmt->bind_param("si", $jsonCerts, $userId);
            $updateStmt->execute();
            
            if ($updateStmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Certificate deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Failed to delete certificate'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    // Log detailed error information
    error_log("Certificate API Error: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
$conn->close();
?>
