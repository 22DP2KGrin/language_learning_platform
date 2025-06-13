<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'admin_auth_check.php';

// Check if user is logged in and is admin
if (!checkAdminAuth()) {
    error_log("Unauthorized access attempt in get_users.php");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    // Get all users from the users table with their details
    $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, created_at, last_login, is_active FROM users ORDER BY user_id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data for display
    $formatted_users = array_map(function($user) {
        return [
            'user_id' => $user['user_id'],
            'username' => htmlspecialchars($user['username']),
            'email' => htmlspecialchars($user['email']),
            'password_hash' => $user['password_hash'],
            'created_at' => $user['created_at'] ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : 'Never',
            'last_login' => $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never',
            'is_active' => $user['is_active'] ? 'Yes' : 'No'
        ];
    }, $users);

    // Log the number of users found for debugging
    error_log("Found " . count($formatted_users) . " users in the database");

    echo json_encode([
        'success' => true,
        'users' => $formatted_users,
        'total_users' => count($formatted_users)
    ]);

} catch (PDOException $e) {
    error_log("Error fetching users from database: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error fetching users from database',
        'details' => $e->getMessage()
    ]);
}
?> 