<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function checkAdminAuth() {
    error_log("Checking admin auth...");
    error_log("Session data: " . print_r($_SESSION, true));
    
    // Проверяем сессию
    if (isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        error_log("Session auth passed");
        return true;
    }

    // Если нет сессии, проверяем токен
    $headers = getallheaders();
    error_log("Request headers: " . print_r($headers, true));
    
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    error_log("Auth header: " . $authHeader);
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        error_log("Auth header check failed: empty or invalid format");
        return false;
    }

    $token = $matches[1];
    error_log("Token from header: " . $token);

    // Проверяем токен в базе данных
    require_once 'db_connect.php';
    try {
        $stmt = $pdo->prepare("
            SELECT admin_id 
            FROM admin_sessions 
            WHERE session_token = ? 
            AND expires_at > NOW() 
            AND admin_id IN (SELECT id FROM admins WHERE is_active = 1)
        ");
        $stmt->execute([$token]);
        $session = $stmt->fetch();

        if ($session) {
            error_log("Token auth passed for admin_id: " . $session['admin_id']);
            // Устанавливаем сессию
            $_SESSION['admin_id'] = $session['admin_id'];
            $_SESSION['is_admin'] = true;
            return true;
        }
    } catch (PDOException $e) {
        error_log("Database error during token check: " . $e->getMessage());
    }

    error_log("Auth check failed");
    return false;
}

// Если файл включен напрямую, проверяем авторизацию
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    if (!checkAdminAuth()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
?> 