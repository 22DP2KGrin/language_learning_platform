<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Функция для безопасного выполнения SQL-запросов
function executeQuery($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Error in query: $query");
        error_log("Parameters: " . json_encode($params));
        error_log("Error message: " . $e->getMessage());
        throw new Exception("Database query failed: " . $e->getMessage());
    }
}

try {
    // Получаем токен сессии из заголовка или POST данных
    $headers = getallheaders();
    $sessionToken = null;

    // Проверяем различные варианты получения токена
    if (isset($headers['Authorization'])) {
        $sessionToken = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($_POST['session_token'])) {
        $sessionToken = $_POST['session_token'];
    } elseif (isset($_GET['session_token'])) {
        $sessionToken = $_GET['session_token'];
    }

    // Если токен не найден, возвращаем ошибку без редиректа
    if (!$sessionToken) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Session token is required',
            'message' => 'Please log in to view your test history'
        ]);
        exit;
    }

    // Подключаемся к базе данных
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Failed to connect to the database");
    }

    // Проверяем существование необходимых таблиц
    $requiredTables = ['users', 'sessions', 'test_results', 'user_answers', 'topics', 'questions'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        // Если таблицы отсутствуют, пытаемся их создать
        require_once __DIR__ . '/check_database.php';
        // После создания таблиц продолжаем выполнение
    }

    // Проверяем сессию и получаем информацию о пользователе
    $sessionQuery = "
        SELECT u.user_id, u.username, u.is_active 
        FROM sessions s 
        JOIN users u ON s.user_id = u.user_id 
        WHERE s.session_token = ? AND s.expires_at > NOW()
    ";
    
    $sessionStmt = executeQuery($pdo, $sessionQuery, [$sessionToken]);
    $userData = $sessionStmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid or expired session',
            'message' => 'Your session has expired. Please log in again.'
        ]);
        exit;
    }

    if (!$userData['is_active']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'User account is inactive',
            'message' => 'Your account is currently inactive. Please contact support.'
        ]);
        exit;
    }

    // Получаем историю тестов с детальной информацией
    $testHistoryQuery = "
        SELECT 
            tr.result_id,
            t.topic_name,
            t.difficulty_level,
            tr.score,
            tr.max_score,
            tr.time_spent,
            tr.completion_date,
            (
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'question_id', q.question_id,
                        'question_text', q.question_text,
                        'user_answer', ua.answer_text,
                        'correct_answer', q.correct_answer,
                        'is_correct', (ua.answer_text = q.correct_answer),
                        'explanation', q.explanation
                    )
                )
                FROM user_answers ua
                JOIN questions q ON ua.question_id = q.question_id
                WHERE ua.result_id = tr.result_id
            ) as question_details
        FROM test_results tr
        JOIN topics t ON tr.topic_id = t.topic_id
        WHERE tr.user_id = ?
        ORDER BY tr.completion_date DESC
    ";

    $testHistoryStmt = executeQuery($pdo, $testHistoryQuery, [$userData['user_id']]);
    $testResults = $testHistoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Обрабатываем результаты тестов
    foreach ($testResults as &$result) {
        if ($result['question_details']) {
            $result['question_details'] = json_decode($result['question_details'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error for result_id {$result['result_id']}: " . json_last_error_msg());
                $result['question_details'] = [];
            }
        } else {
            $result['question_details'] = [];
        }
    }

    // Получаем общую статистику
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT tr.result_id) as total_tests,
            AVG(tr.score * 100.0 / tr.max_score) as avg_score,
            SUM(tr.time_spent) as total_time,
            COUNT(DISTINCT tr.topic_id) as topics_covered,
            (
                SELECT COUNT(*) 
                FROM user_answers ua 
                JOIN test_results tr2 ON ua.result_id = tr2.result_id 
                WHERE tr2.user_id = ?
            ) as total_questions,
            (
                SELECT COUNT(*) 
                FROM user_answers ua 
                JOIN test_results tr2 ON ua.result_id = tr2.result_id 
                JOIN questions q ON ua.question_id = q.question_id 
                WHERE tr2.user_id = ? AND ua.answer_text = q.correct_answer
            ) as correct_answers
        FROM test_results tr
        WHERE tr.user_id = ?
    ";

    $statsStmt = executeQuery($pdo, $statsQuery, [
        $userData['user_id'],
        $userData['user_id'],
        $userData['user_id']
    ]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Получаем статистику по темам
    $topicStatsQuery = "
        SELECT 
            t.topic_name,
            COUNT(tr.result_id) as attempts,
            AVG(tr.score * 100.0 / tr.max_score) as avg_score,
            COUNT(DISTINCT CASE WHEN ua.answer_text = q.correct_answer THEN ua.answer_id END) as correct_answers,
            COUNT(DISTINCT ua.answer_id) as total_questions
        FROM topics t
        LEFT JOIN test_results tr ON t.topic_id = tr.topic_id AND tr.user_id = ?
        LEFT JOIN user_answers ua ON tr.result_id = ua.result_id
        LEFT JOIN questions q ON ua.question_id = q.question_id
        GROUP BY t.topic_id, t.topic_name
        HAVING attempts > 0
        ORDER BY attempts DESC
    ";

    $topicStatsStmt = executeQuery($pdo, $topicStatsQuery, [$userData['user_id']]);
    $topicStats = $topicStatsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Обновляем время истечения сессии
    $updateSessionQuery = "
        UPDATE sessions 
        SET expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) 
        WHERE session_token = ?
    ";
    executeQuery($pdo, $updateSessionQuery, [$sessionToken]);

    // Формируем ответ
    echo json_encode([
        'success' => true,
        'user' => [
            'username' => $userData['username'],
            'total_questions' => (int)$stats['total_questions'],
            'correct_answers' => (int)$stats['correct_answers'],
            'topics_covered' => (int)$stats['topics_covered']
        ],
        'statistics' => [
            'total_tests' => (int)$stats['total_tests'],
            'avg_score' => round((float)$stats['avg_score'], 2),
            'total_time' => (int)$stats['total_time'],
            'topics_covered' => (int)$stats['topics_covered']
        ],
        'topic_statistics' => $topicStats,
        'test_results' => $testResults
    ]);

} catch (Exception $e) {
    error_log("Error in get_test_history.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get test history',
        'message' => $e->getMessage()
    ]);
}
?> 