<?php
// Prevent any output before headers
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log request details
$logFile = __DIR__ . "/../logs/auth_test_" . date('Y-m-d') . ".log";
$timestamp = date('[Y-m-d H:i:s] ');
$requestData = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'input' => file_get_contents('php://input'),
    'post' => $_POST,
    'get' => $_GET
];
error_log($timestamp . "Request data: " . print_r($requestData, true) . "\n", 3, $logFile);

// Send test response
$response = [
    'success' => true,
    'message' => 'Test endpoint working',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'headers_received' => getallheaders(),
    'timestamp' => date('Y-m-d H:i:s')
];

// Clear any previous output
ob_clean();

// Send response
echo json_encode($response);
exit();
?> 