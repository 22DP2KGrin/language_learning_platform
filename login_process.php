<?php
// Prevent any output before headers
ob_start();

// Enable error reporting but log to file instead of output
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Define session lifetime (24 hours in seconds)
define('SESSION_LIFETIME', 24 * 60 * 60);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8888');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log function
function logError($message) {
    $logFile = __DIR__ . "/../logs/login_" . date('Y-m-d') . ".log";
    $timestamp = date('[Y-m-d H:i:s] ');
    error_log($timestamp . $message . "\n", 3, $logFile);
}

// Function to send JSON response
function sendJsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

// Log request details
logError("=== New Login Attempt ===");
logError("Request method: " . $_SERVER['REQUEST_METHOD']);
logError("Request headers: " . print_r(getallheaders(), true));
logError("Raw input: " . file_get_contents('php://input'));

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    logError("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    sendJsonResponse(false, 'Invalid request method');
}

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    logError("Decoded input: " . print_r($input, true));
    
    if (!$input) {
        logError("JSON decode error: " . json_last_error_msg());
        throw new Exception('Invalid input data');
    }

    // Validate required fields
    if (empty($input['username']) || empty($input['password'])) {
        logError("Missing required fields");
        throw new Exception('Username and password are required');
    }

    // Initialize database connection
    $pdo = getDBConnection();
    if (!$pdo) {
        logError("Database connection failed");
        throw new Exception('Database connection failed');
    }
    logError("Database connection successful");

    // Check if username is email
    $isEmail = filter_var($input['username'], FILTER_VALIDATE_EMAIL);
    $field = $isEmail ? 'email' : 'username';
    logError("Using field: " . $field . " for username: " . $input['username']);

    // Get user from database
    $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, bio FROM users WHERE $field = ?");
    $stmt->execute([$input['username']]);
    $user = $stmt->fetch();

    if (!$user) {
        logError("User not found for " . $field . ": " . $input['username']);
        throw new Exception('Invalid username or password');
    }
    logError("User found: " . print_r(array_diff_key($user, ['password_hash' => '']), true));

    // Verify password
    if (!password_verify($input['password'], $user['password_hash'])) {
        logError("Password verification failed for user: " . $user['username']);
        throw new Exception('Invalid username or password');
    }
    logError("Password verified successfully");

    // Generate session token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    logError("Generated session token: " . $token);
    logError("Session expires at: " . $expiresAt);

    // Create new session
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sessions (
                user_id, 
                session_token, 
                expires_at,
                ip_address,
                user_agent
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['user_id'], 
            $token, 
            $expiresAt,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        logError("Session created successfully in database");
        
        // Verify session was created
        $stmt = $pdo->prepare("
            SELECT session_id, user_id, session_token, expires_at 
            FROM sessions 
            WHERE session_token = ?
        ");
        $stmt->execute([$token]);
        $createdSession = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$createdSession) {
            logError("Session verification failed - session not found in database");
            throw new Exception("Failed to verify session creation");
        }
        
        logError("Session verification successful: " . print_r($createdSession, true));
        
    } catch (PDOException $e) {
        logError("Failed to create or verify session: " . $e->getMessage());
        throw new Exception("Failed to create session: " . $e->getMessage());
    }

    // Update last login time
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    logError("Last login time updated");

    // Remove sensitive data
    unset($user['password_hash']);

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user,
        'session' => [
            'session_token' => $token,
            'expires_at' => $expiresAt
        ]
    ];

    logError("Sending response: " . json_encode($response));

    // Clear any previous output
    ob_clean();
    
    // Send response
    echo json_encode($response);

} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    // Log the error
    logError("Login error: " . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering and flush
ob_end_flush();
?>