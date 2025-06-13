<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>API Test for get_test_history.php</h1>";

// Проверяем, есть ли активные сессии
echo "<h2>1. Checking for active sessions</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    
    $sessionStmt = $pdo->query("SELECT s.session_id, s.user_id, s.session_token, s.expires_at, u.username 
                                FROM sessions s 
                                JOIN users u ON s.user_id = u.user_id 
                                WHERE s.expires_at > NOW() 
                                ORDER BY s.expires_at DESC 
                                LIMIT 5");
    $sessions = $sessionStmt->fetchAll();
    
    if (empty($sessions)) {
        echo "<p style='color: red;'>No active sessions found!</p>";
        echo "<p>You need to log in first to test the API.</p>";
    } else {
        echo "<p style='color: green;'>Found " . count($sessions) . " active session(s)</p>";
        echo "<ul>";
        foreach ($sessions as $session) {
            echo "<li>User: {$session['username']} (ID: {$session['user_id']}), Token: " . substr($session['session_token'], 0, 20) . "...</li>";
        }
        echo "</ul>";
        
        // Тестируем API с первой активной сессией
        $testToken = $sessions[0]['session_token'];
        echo "<h2>2. Testing API with session token</h2>";
        
        // Создаем cURL запрос
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8888/api/get_test_history.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $testToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>HTTP Status Code: <strong>$httpCode</strong></p>";
        echo "<p>Response:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
        
        // Парсим JSON ответ
        $data = json_decode($response, true);
        if ($data) {
            echo "<h3>Parsed Response:</h3>";
            echo "<ul>";
            echo "<li>Success: " . ($data['success'] ? 'Yes' : 'No') . "</li>";
            if (isset($data['user'])) {
                echo "<li>User: " . $data['user']['username'] . "</li>";
                echo "<li>Total Questions: " . $data['user']['total_questions'] . "</li>";
                echo "<li>Correct Answers: " . $data['user']['correct_answers'] . "</li>";
            }
            if (isset($data['statistics'])) {
                echo "<li>Total Tests: " . $data['statistics']['total_tests'] . "</li>";
                echo "<li>Average Score: " . $data['statistics']['avg_score'] . "%</li>";
            }
            if (isset($data['test_results'])) {
                echo "<li>Test Results Count: " . count($data['test_results']) . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Database Content Summary</h2>";

try {
    $pdo = getDBConnection();
    
    // Проверяем количество записей в каждой таблице
    $tables = ['users', 'sessions', 'topics', 'questions', 'test_results', 'user_answers'];
    
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $countStmt->fetch()['count'];
        echo "<p><strong>$table:</strong> $count records</p>";
    }
    
    // Проверяем последние результаты тестов
    echo "<h3>Recent Test Results:</h3>";
    $recentStmt = $pdo->query("SELECT tr.result_id, tr.user_id, t.topic_name, tr.score, tr.max_score, tr.completion_date 
                               FROM test_results tr 
                               JOIN topics t ON tr.topic_id = t.topic_id 
                               ORDER BY tr.completion_date DESC 
                               LIMIT 5");
    $recent = $recentStmt->fetchAll();
    
    if (empty($recent)) {
        echo "<p style='color: orange;'>No test results found in database.</p>";
    } else {
        echo "<ul>";
        foreach ($recent as $result) {
            $percentage = round(($result['score'] / $result['max_score']) * 100, 2);
            echo "<li>User ID: {$result['user_id']}, Topic: {$result['topic_name']}, Score: {$result['score']}/{$result['max_score']} ({$percentage}%), Date: {$result['completion_date']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?> 