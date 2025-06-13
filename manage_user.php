<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Проверка авторизации и прав администратора
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Проверяем, является ли пользователь администратором
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !$admin['is_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden - Admin access required']);
        exit;
    }

    // Получаем данные запроса
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action']) || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }

    $action = $data['action'];
    $userId = $data['user_id'];

    // Проверяем, что админ не пытается изменить свой аккаунт
    if ($userId == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot modify your own admin account']);
        exit;
    }

    // Выполняем запрошенное действие
    switch ($action) {
        case 'delete':
            // Удаляем пользователя
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'User deleted successfully';
            break;

        case 'block':
            // Блокируем пользователя
            $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'User blocked successfully';
            break;

        case 'unblock':
            // Разблокируем пользователя
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'User unblocked successfully';
            break;

        case 'make_admin':
            // Назначаем пользователя администратором
            $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'User promoted to admin successfully';
            break;

        case 'remove_admin':
            // Убираем права администратора
            $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            $message = 'Admin privileges removed successfully';
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 