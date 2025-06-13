<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Получаем токен сессии из заголовка
$sessionToken = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? null;

if (!$sessionToken) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No session token provided']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Проверяем сессию в базе данных
    $stmt = $pdo->prepare("
        SELECT s.user_id, u.is_active
        FROM sessions s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.session_token = ? 
        AND s.expires_at > NOW()
        AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$sessionToken]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired session']);
        exit;
    }

    // Обновляем время жизни сессии
    $stmt = $pdo->prepare("
        UPDATE sessions 
        SET expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
        WHERE session_token = ?
    ");
    $stmt->execute([$sessionToken]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error checking session: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to check session'
    ]);
}
?> 