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

if ($auth->authenticate()) {
    try {
        $userId = $_SERVER['USER']->user_id ?? null;
        
        if (!$userId) {
            $userId = 2;
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        $portfolioId = isset($_GET['id']) ? intval($_GET['id']) : null;
        
        switch ($method) {
            case 'GET':
                if ($portfolioId) {
                    $stmt = $conn->prepare("SELECT * FROM portfolio WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $portfolioId, $userId);
                } else {
                    $stmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($portfolioId) {
                    if ($result->num_rows > 0) {
                        $portfolio = $result->fetch_assoc();
                        echo json_encode(['success' => true, 'data' => $portfolio]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Portfolio not found']);
                    }
                } else {
                    $portfolios = [];
                    while ($row = $result->fetch_assoc()) {
                        $portfolios[] = $row;
                    }
                    echo json_encode(['success' => true, 'data' => $portfolios]);
                }
                break;
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!$data) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
                    break;
                }
                
                $checkStmt = $conn->prepare("SELECT id FROM portfolio WHERE user_id = ?");
                $checkStmt->bind_param("i", $userId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'User already has a portfolio. Use PUT to update.']);
                    break;
                }
                
                $fields = [
                    'user_id' => $userId,
                    'name' => $data['name'] ?? null,
                    'title' => $data['title'] ?? null,
                    'short_bio' => $data['short_bio'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'location' => $data['location'] ?? null,
                    'education' => $data['education'] ?? null,
                    'experience' => $data['experience'] ?? null,
                    'skills' => $data['skills'] ?? null,
                    'profile_image' => $data['profile_image'] ?? null,
                    'cv_link' => $data['cv_link'] ?? null,
                    'linkedin' => $data['linkedin'] ?? null,
                    'github' => $data['github'] ?? null,
                    'about_me' => $data['about_me'] ?? null,
                    'contact_section' => $data['contact_section'] ?? null,
                    'certifications' => $data['certifications'] ?? null
                ];
                
                $columns = implode(', ', array_keys($fields));
                $placeholders = implode(', ', array_fill(0, count($fields), '?'));
                
                $sql = "INSERT INTO portfolio ({$columns}) VALUES ({$placeholders})";
                $stmt = $conn->prepare($sql);
                
                $types = str_repeat('s', count($fields)); // All are strings
                $fieldValues = array_values($fields);
                $stmt->bind_param($types, ...$fieldValues);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $newId = $conn->insert_id;
                    
                    $newStmt = $conn->prepare("SELECT * FROM portfolio WHERE id = ?");
                    $newStmt->bind_param("i", $newId);
                    $newStmt->execute();
                    $result = $newStmt->get_result();
                    $newPortfolio = $result->fetch_assoc();
                    
                    http_response_code(201);
                    echo json_encode(['success' => true, 'data' => $newPortfolio]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Failed to create portfolio']);
                }
                break;
                
            case 'PUT':
                if (!$portfolioId) {
                    $checkStmt = $conn->prepare("SELECT id FROM portfolio WHERE user_id = ?");
                    $checkStmt->bind_param("i", $userId);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        $portfolioData = $checkResult->fetch_assoc();
                        $portfolioId = $portfolioData['id'];
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Portfolio not found. Create one first.']);
                        break;
                    }
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!$data) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
                    break;
                }
                
                if (isset($data['certifications']) && !empty($data['certifications'])) {
                    try {
                        // If it's a string but not JSON, convert to JSON format
                        if (is_string($data['certifications']) && !isJson($data['certifications'])) {
                            $data['certifications'] = json_encode([
                                [
                                    'title' => sanitizeInput('Imported Certification'), 
                                    'description' => sanitizeInput($data['certifications']),
                                    'date' => date('Y-m-d'),
                                    'image' => ''
                                ]
                            ]);
                        } 
                        else if (is_array($data['certifications'])) {
                            foreach ($data['certifications'] as &$cert) {
                                if (isset($cert['title'])) $cert['title'] = sanitizeInput($cert['title']);
                                if (isset($cert['description'])) $cert['description'] = sanitizeInput($cert['description']);
                                if (isset($cert['date'])) $cert['date'] = sanitizeInput($cert['date']);
                                if (isset($cert['image'])) $cert['image'] = sanitizeInput($cert['image']);
                            }
                            $data['certifications'] = json_encode($data['certifications']);
                        }
                        else if (is_string($data['certifications']) && isJson($data['certifications'])) {
                            $certsArray = json_decode($data['certifications'], true);
                            foreach ($certsArray as &$cert) {
                                if (isset($cert['title'])) $cert['title'] = sanitizeInput($cert['title']);
                                if (isset($cert['description'])) $cert['description'] = sanitizeInput($cert['description']);
                                if (isset($cert['date'])) $cert['date'] = sanitizeInput($cert['date']);
                                if (isset($cert['image'])) $cert['image'] = sanitizeInput($cert['image']);
                            }
                            $data['certifications'] = json_encode($certsArray);
                        }
                    } catch (Exception $e) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Invalid certification data format']);
                        break;
                    }
                }
                
                $updateFields = [];
                $updateParams = [];
                $updateTypes = "";
                
                $fields = [
                    'name', 'title', 'short_bio', 'email', 'phone', 
                    'location', 'education', 'experience', 'skills',
                    'profile_image', 'cv_link', 'linkedin', 'github',
                    'about_me', 'contact_section', 'certifications'
                ];
                
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $updateFields[] = "$field = ?";
                        $updateParams[] = $data[$field];
                        $updateTypes .= "s";
                    }
                }
                
                if (empty($updateFields)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'No fields to update']);
                    break;
                }
                
                $sql = "UPDATE portfolio SET " . implode(", ", $updateFields) . " WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql);
                
                $updateParams[] = $portfolioId;
                $updateParams[] = $userId;
                $updateTypes .= "ii";
                
                $stmt->bind_param($updateTypes, ...$updateParams);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $updatedStmt = $conn->prepare("SELECT * FROM portfolio WHERE id = ?");
                    $updatedStmt->bind_param("i", $portfolioId);
                    $updatedStmt->execute();
                    $result = $updatedStmt->get_result();
                    $updatedPortfolio = $result->fetch_assoc();
                    
                    echo json_encode(['success' => true, 'data' => $updatedPortfolio]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'No changes made to portfolio']);
                }
                break;
                
            case 'DELETE':
                if (!$portfolioId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Portfolio ID is required']);
                    break;
                }
                
                $stmt = $conn->prepare("DELETE FROM portfolio WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $portfolioId, $userId);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Portfolio deleted successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Portfolio not found or not authorized']);
                }
                break;
                
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        }
        
    } catch (Exception $e) {
        // Log detailed error information
        error_log("API ERROR: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => $config['debug_mode'] ? $e->getMessage() : 'Server error'
        ]);
    }
    
    $conn->close();
}
?>
