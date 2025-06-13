<?php
header('Content-Type: application/json');
require_once 'admin_auth_check.php';

if (!checkAdminAuth()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

try {
    // Get database connection
    $pdo = initDatabase();
    
    // Initialize stats array
    $stats = [
        'total_users' => 0,
        'active_users' => 0
    ];

    // Count total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();

    // Count active users (logged in within last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['active_users'] = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    error_log("Error in admin_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?> 