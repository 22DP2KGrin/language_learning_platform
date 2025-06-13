<?php
// Enable error reporting
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

// Set header to return JSON
header('Content-Type: application/json');

// Log function
function logError($message) {
    $logFile = __DIR__ . "/../logs/registration_" . date('Y-m-d') . ".log";
    $timestamp = date('[Y-m-d H:i:s] ');
    error_log($timestamp . $message . "\n", 3, $logFile);
}

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        logError("=== New Registration Attempt ===");
        
        // Get JSON data from request body
        $json = file_get_contents('php://input');
        logError("Received JSON data: " . $json);
        
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logError("JSON decode error: " . json_last_error_msg());
            throw new Exception("Invalid JSON data received");
        }
        
        // Validate required fields
        $required_fields = ['username', 'email', 'password', 'firstName', 'lastName', 'country', 'birthDate', 'gender'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                logError("Missing required field: $field");
                echo json_encode([
                    'success' => false,
                    'message' => 'Please fill in all required fields',
                    'field' => $field
                ]);
                exit();
            }
        }
        
        // Get and sanitize input data
        $username = trim($data['username']);
        $email = trim($data['email']);
        $password = trim($data['password']);
        $firstName = trim($data['firstName']);
        $lastName = trim($data['lastName']);
        $country = trim($data['country']);
        $phone = isset($data['phone']) ? trim($data['phone']) : null;
        $birthDate = trim($data['birthDate']);
        $gender = trim($data['gender']);
        
        logError("Processing registration for username: $username, email: $email");
        
        // Validate username
        if (strlen($username) < 3 || strlen($username) > 50) {
            logError("Invalid username length: " . strlen($username));
            echo json_encode([
                'success' => false,
                'message' => 'Username must be between 3 and 50 characters',
                'field' => 'username'
            ]);
            exit();
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            logError("Invalid email format: $email");
            echo json_encode([
                'success' => false,
                'message' => 'Please enter a valid email address',
                'field' => 'email'
            ]);
            exit();
        }
        
        // Validate password
        if (strlen($password) < 8) {
            logError("Password too short: " . strlen($password) . " characters");
            echo json_encode([
                'success' => false,
                'message' => 'Password must be at least 8 characters long',
                'field' => 'password'
            ]);
            exit();
        }

        // Validate first name
        if (strlen($firstName) < 2) {
            logError("First name too short: " . strlen($firstName));
            echo json_encode([
                'success' => false,
                'message' => 'First name must be at least 2 characters long',
                'field' => 'firstName'
            ]);
            exit();
        }

        // Validate last name
        if (strlen($lastName) < 2) {
            logError("Last name too short: " . strlen($lastName));
            echo json_encode([
                'success' => false,
                'message' => 'Last name must be at least 2 characters long',
                'field' => 'lastName'
            ]);
            exit();
        }

        // Validate phone if provided
        if ($phone) {
            // Remove all non-digit characters for validation
            $phoneDigits = preg_replace('/\D/', '', $phone);
            if (strlen($phoneDigits) < 8 || strlen($phoneDigits) > 15) {
                logError("Invalid phone format: $phone");
                echo json_encode([
                    'success' => false,
                    'message' => 'Please enter a valid phone number (8-15 digits)',
                    'field' => 'phone'
                ]);
                exit();
            }
        }

        // Validate birth date
        $birthDateObj = new DateTime($birthDate);
        $today = new DateTime();
        $age = $today->diff($birthDateObj)->y;
        if ($age < 13) {
            logError("User too young: $age years old");
            echo json_encode([
                'success' => false,
                'message' => 'You must be at least 13 years old to register',
                'field' => 'birthDate'
            ]);
            exit();
        }

        // Validate gender
        if (!in_array($gender, ['male', 'female', 'other'])) {
            logError("Invalid gender: $gender");
            echo json_encode([
                'success' => false,
                'message' => 'Please select a valid gender',
                'field' => 'gender'
            ]);
            exit();
        }
        
        // Get database connection
        try {
            $pdo = getDBConnection();
            if (!$pdo) {
                throw new Exception('Database connection failed');
            }
            logError("Database connection successful");
        } catch (Exception $e) {
            logError("Database connection failed: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Database connection error. Please try again later.',
                'field' => 'general'
            ]);
            exit();
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        if ($result) {
            logError("Username already exists: $username");
            echo json_encode([
                'success' => false,
                'message' => 'This username is already taken',
                'field' => 'username'
            ]);
            exit();
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        if ($result) {
            logError("Email already registered: $email");
            echo json_encode([
                'success' => false,
                'message' => 'This email is already registered',
                'field' => 'email'
            ]);
            exit();
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    username, 
                    email, 
                    password_hash, 
                    first_name,
                    last_name,
                    country,
                    phone,
                    birth_date,
                    gender,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $username,
                $email,
                $password_hash,
                $firstName,
                $lastName,
                $country,
                $phone,
                $birthDate,
                $gender
            ]);
            
            $user_id = $pdo->lastInsertId();
            logError("User registered successfully with ID: $user_id");
            
            // Generate session token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 24 * 60 * 60); // 24 hours
            
            // Create session
            $stmt = $pdo->prepare("
                INSERT INTO sessions (
                    user_id,
                    session_token,
                    expires_at,
                    created_at
                ) VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $token,
                $expires_at
            ]);
            
            logError("Session created for user ID: $user_id");
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'user_id' => $user_id,
                    'username' => $username,
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ],
                'session' => [
                    'session_token' => $token,
                    'expires_at' => $expires_at
                ]
            ]);
            
        } catch (PDOException $e) {
            logError("Database error during registration: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Registration failed. Please try again later.',
                'field' => 'general'
            ]);
        }
        
    } catch (Exception $e) {
        logError("Registration error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred during registration. Please try again.',
            'field' => 'general'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
        'field' => 'general'
    ]);
}
?>