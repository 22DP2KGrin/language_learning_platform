<?php
header('Content-Type: application/json');
require_once 'admin_auth_check.php';

if (!checkAdminAuth()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config/database.php';

try {
    // Получаем последние действия
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.action,
            a.description,
            a.created_at,
            u.username as user_name
        FROM admin_activity_log a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'activities' => $activities
    ]);

} catch (PDOException $e) {
    error_log("Error in admin_activities.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 