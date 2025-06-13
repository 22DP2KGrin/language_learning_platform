<?php
// Set session cookie parameters before starting session
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Clear any existing session data
$_SESSION = array();

// If a session cookie exists, destroy it
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Regenerate session ID
session_regenerate_id(true);

header('Content-Type: application/json');

// Log incoming request
error_log("Admin login attempt - Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw input: " . file_get_contents('php://input'));

// Database connection
require_once 'db_connect.php';

// Function to verify password
function verifyPassword($password, $hash) {
    error_log("Verifying password. Hash from DB: " . $hash);
    $result = password_verify($password, $hash);
    error_log("Password verification result: " . ($result ? 'true' : 'false'));
    return $result;
}

// Function to generate session token
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

// Function to log admin activity
function logAdminActivity($adminId, $action, $description) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log (admin_id, action, description, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $adminId, 
            $action, 
            $description, 
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
        return false;
    }
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("=== Admin Login Attempt ===");
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Raw input: " . file_get_contents('php://input'));
    error_log("Decoded input data: " . print_r($data, true));
    error_log("Session status: " . print_r($_SESSION, true));
    
    if (!isset($data['email']) || !isset($data['password'])) {
        error_log("Missing email or password in request");
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required'
        ]);
        exit;
    }

    try {
        // Get admin from database
        error_log("Attempting to connect to database...");
        error_log("Database connection parameters: " . print_r([
            'host' => DB_HOST,
            'dbname' => DB_NAME,
            'user' => DB_USER
        ], true));

        $stmt = $pdo->prepare("
            SELECT id, username, email, password, role, permissions, is_active
            FROM admins
            WHERE email = ?
        ");
        error_log("SQL Query prepared: " . $stmt->queryString);
        error_log("Executing query with email: " . $data['email']);
        
        $stmt->execute([$data['email']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Admin lookup result: " . ($admin ? "Found" : "Not found"));
        if ($admin) {
            error_log("Admin details (excluding password): " . print_r(array_diff_key($admin, ['password' => '']), true));
        }

        if (!$admin) {
            error_log("Admin not found for email: " . $data['email']);
            echo json_encode([
                'success' => false,
                'message' => 'User not found. Please check your email and try again.'
            ]);
            exit;
        }

        if (!$admin['is_active']) {
            error_log("Inactive admin account: " . $data['email']);
            echo json_encode([
                'success' => false,
                'message' => 'This account has been deactivated. Please contact support.'
            ]);
            exit;
        }

        // Verify password
        error_log("Attempting password verification for admin: " . $admin['id']);
        if (!verifyPassword($data['password'], $admin['password'])) {
            error_log("Password verification failed for admin: " . $admin['id']);
            // Try to log failed login attempt, but don't stop if it fails
            logAdminActivity($admin['id'], 'LOGIN_FAILED', 'Failed login attempt');
            
            echo json_encode([
                'success' => false,
                'message' => 'Invalid password. Please try again.'
            ]);
            exit;
        }
        error_log("Password verification successful for admin: " . $admin['id']);

        // Generate session token
        $sessionToken = generateSessionToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        error_log("Generated session token: " . $sessionToken);
        error_log("Session expires at: " . $expiresAt);

        try {
            // Create session in database
            $stmt = $pdo->prepare("
                INSERT INTO admin_sessions (admin_id, session_token, expires_at, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $admin['id'],
                $sessionToken,
                $expiresAt,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            error_log("Session created successfully in database");

            // Set session data
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_token'] = $sessionToken;
            $_SESSION['admin_expires'] = $expiresAt;
            $_SESSION['is_admin'] = true;
            error_log("Session data saved to PHP session: " . print_r($_SESSION, true));

            // Update last login
            $stmt = $pdo->prepare("
                UPDATE admins
                SET last_login = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$admin['id']]);
            error_log("Last login timestamp updated");

            // Try to log successful login
            logAdminActivity($admin['id'], 'LOGIN_SUCCESS', 'Successful login');
            error_log("Login activity logged");

            // Return success response
            $response = [
                'success' => true,
                'message' => 'Login successful',
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'role' => $admin['role'],
                    'permissions' => json_decode($admin['permissions'], true)
                ],
                'session' => [
                    'token' => $sessionToken,
                    'expires' => $expiresAt
                ]
            ];
            error_log("Sending success response: " . print_r($response, true));
            echo json_encode($response);
            exit;

        } catch (PDOException $e) {
            error_log("Session creation error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create session. Please try again.',
                'error_details' => $e->getMessage()
            ]);
            exit;
        }

    } catch (PDOException $e) {
        error_log("Database error during login: " . $e->getMessage());
        error_log("Error code: " . $e->getCode());
        error_log("Error trace: " . $e->getTraceAsString());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again later.',
            'error_details' => $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?> 