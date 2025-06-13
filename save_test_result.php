<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Получаем токен сессии из заголовка
$sessionToken = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? null;

if (!$sessionToken) {
    echo json_encode(['success' => false, 'error' => 'No session token provided']);
    exit;
}

try {
    $pdo = getDBConnection();
    
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
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
    }

    $user_id = $session['user_id'];

    // Получаем данные из POST запроса
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }

    // Проверяем необходимые поля
    $required_fields = ['topic_id', 'score', 'max_score', 'time_spent', 'errors'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit;
        }
    }

    $pdo->beginTransaction();

    $topic_id = $data['topic_id'];
    $score = $data['score'];
    $max_score = $data['max_score'];
    $time_spent = $data['time_spent'];
    $errors = $data['errors'];

    // Проверяем существование темы
    $stmt = $pdo->prepare("SELECT topic_id FROM topics WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Topic not found");
    }

    // Проверяем, существует ли уже результат для этой темы
    $stmt = $pdo->prepare("SELECT result_id FROM test_results WHERE user_id = ? AND topic_id = ?");
    $stmt->execute([$user_id, $topic_id]);
    $existing_result = $stmt->fetch();

    if ($existing_result) {
        // Обновляем существующий результат
        $stmt = $pdo->prepare("
            UPDATE test_results 
            SET score = ?, max_score = ?, time_spent = ?, completion_date = CURRENT_TIMESTAMP 
            WHERE result_id = ?
        ");
        $stmt->execute([$score, $max_score, $time_spent, $existing_result['result_id']]);
        $result_id = $existing_result['result_id'];

        // Удаляем старые ошибки
        $stmt = $pdo->prepare("DELETE FROM test_errors WHERE result_id = ?");
        $stmt->execute([$result_id]);
    } else {
        // Создаем новый результат
        $stmt = $pdo->prepare("
            INSERT INTO test_results (user_id, topic_id, score, max_score, time_spent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $topic_id, $score, $max_score, $time_spent]);
        $result_id = $pdo->lastInsertId();
    }

    // Сохраняем ошибки
    if (!empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO test_errors (result_id, question_id, user_answer, correct_answer, question_text) 
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($errors as $error) {
            $stmt->execute([
                $result_id,
                $error['question_id'],
                $error['user_answer'],
                $error['correct_answer'],
                $error['question_text']
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Test result saved successfully',
        'result_id' => $result_id
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error saving test result: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to save test result: ' . $e->getMessage()
    ]);
}
?> 