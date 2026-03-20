<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../db_connect.php';

$response = [
    'success' => false,
    'error' => '',
    'data' => []
];

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    if (!$input) {
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Validate required fields
    if (!isset($data['action'])) {
        throw new Exception('Action is required');
    }

    $action = $data['action'];
    
    switch ($action) {
        case 'code_generated':
            // Log code generation (optional - for analytics)
            $code = $data['code'] ?? '';
            if (empty($code)) {
                throw new Exception('Trial code is required');
            }
            
            // You can log code generation separately if needed
            // For now, we'll just return success
            $response['success'] = true;
            $response['data'] = ['message' => 'Code generation logged'];
            break;
            
        case 'download_clicked':
            // Log actual download
            $trial_code = $data['trial_code'] ?? $data['code'] ?? '';
            $email = $data['email'] ?? '';
            
            if (empty($trial_code)) {
                throw new Exception('Trial code is required for download logging');
            }
            
            // Use the existing log_download function from db_connect.php
            $result = log_download($trial_code, $email);
            
            if ($result) {
                $response['success'] = true;
                $response['data'] = [
                    'message' => 'Download logged successfully',
                    'trial_code' => $trial_code
                ];
            } else {
                throw new Exception('Failed to log download to database');
            }
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Download logging error: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
exit();
?>