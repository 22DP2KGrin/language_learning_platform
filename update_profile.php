<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Получаем данные из POST запроса
$data = json_decode(file_get_contents('php://input'), true);

// Проверяем авторизацию
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'No authorization token provided']);
    exit;
}

try {
    // Проверяем токен и получаем ID пользователя
    $stmt = $pdo->prepare("SELECT user_id FROM sessions WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired session']);
        exit;
    }

    $userId = $session['user_id'];

    // Проверяем, не занят ли email другим пользователем
    if (isset($data['email'])) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$data['email'], $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email is already taken']);
            exit;
        }
    }

    // Начинаем транзакцию
    $pdo->beginTransaction();

    // Обновляем основную информацию пользователя
    $updateFields = [];
    $params = [];

    if (isset($data['username'])) {
        $updateFields[] = "username = ?";
        $params[] = $data['username'];
    }

    if (isset($data['email'])) {
        $updateFields[] = "email = ?";
        $params[] = $data['email'];
    }

    if (isset($data['language'])) {
        $updateFields[] = "language = ?";
        $params[] = $data['language'];
    }

    if (isset($data['timezone'])) {
        $updateFields[] = "timezone = ?";
        $params[] = $data['timezone'];
    }

    // Если есть новый пароль, обновляем его
    if (isset($data['newPassword']) && !empty($data['newPassword'])) {
        // Проверяем текущий пароль
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($data['currentPassword'], $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }

        $updateFields[] = "password = ?";
        $params[] = password_hash($data['newPassword'], PASSWORD_DEFAULT);
    }

    if (!empty($updateFields)) {
        $params[] = $userId; // Добавляем user_id для WHERE условия
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Получаем обновленные данные пользователя
    $stmt = $pdo->prepare("SELECT user_id, username, email, created_at, language, timezone FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Завершаем транзакцию
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => $user
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?> 