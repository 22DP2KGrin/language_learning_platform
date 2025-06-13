<?php
header('Content-Type: application/json');
require_once 'config/database.php';
require_once 'auth/check_auth.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Получаем последние результаты тестов
    $stmt = $pdo->prepare("
        SELECT tr.*, 
               COUNT(te.id) as total_errors,
               DATE_FORMAT(tr.completion_date, '%Y-%m-%d %H:%i') as formatted_date
        FROM test_results tr
        LEFT JOIN test_errors te ON tr.id = te.result_id
        WHERE tr.user_id = ?
        GROUP BY tr.id
        ORDER BY tr.completion_date DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Для каждого результата получаем детали ошибок
    foreach ($recent_results as &$result) {
        $stmt = $pdo->prepare("
            SELECT * FROM test_errors 
            WHERE result_id = ?
            ORDER BY id
        ");
        $stmt->execute([$result['id']]);
        $result['errors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получаем статистику по уровням
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tests,
            AVG(score/max_score * 100) as average_score,
            SUM(time_spent) as total_time
        FROM test_results 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $statistics = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'recent_results' => $recent_results,
            'statistics' => [
                'total_tests' => (int)$statistics['total_tests'],
                'average_score' => round($statistics['average_score'], 2),
                'total_time' => (int)$statistics['total_time'],
                'current_level' => 'Beginner'
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Error getting test results: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to get test results']);
}
?> 