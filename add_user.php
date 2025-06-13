<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['username', 'email', 'password', 'language', 'timezone'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$data['username'], $data['email']]);
    if ($stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Username or email already exists']);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, language, timezone, created_at, is_active)
        VALUES (?, ?, ?, ?, ?, NOW(), 1)
    ");

    $stmt->execute([
        $data['username'],
        $data['email'],
        $hashed_password,
        $data['language'],
        $data['timezone']
    ]);

    echo json_encode([
        'message' => 'User added successfully',
        'user_id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    error_log("Error adding user: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error adding user']);
}
?> 