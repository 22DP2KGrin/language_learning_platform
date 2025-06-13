<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

error_log("=== Admin Data Request ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request headers: " . print_r(getallheaders(), true));

require_once 'admin_auth_check.php';

if (!checkAdminAuth()) {
    error_log("Auth check failed in admin_data.php");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

error_log("Auth check passed, proceeding with data retrieval");

require_once 'config/database.php';

try {
    error_log("Session admin_id: " . $_SESSION['admin_id']);
    
    // Get admin data from database
    $stmt = $pdo->prepare("
        SELECT id, username, email, role, permissions, last_login
        FROM admins
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Database query result: " . print_r($admin, true));

    if (!$admin) {
        error_log("Admin not found in database");
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Admin not found'
        ]);
        exit;
    }

    // Remove sensitive data
    unset($admin['password']);

    error_log("Sending successful response");
    echo json_encode([
        'success' => true,
        'admin' => $admin
    ]);

} catch (PDOException $e) {
    error_log("Database error in admin_data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 