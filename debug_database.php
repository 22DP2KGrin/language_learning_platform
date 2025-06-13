<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/config/database.php';

echo "<h1>Database Debug Information</h1>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Проверяем таблицы
    $tables = ['users', 'sessions', 'topics', 'questions', 'test_results', 'user_answers'];
    
    foreach ($tables as $table) {
        echo "<h2>Table: $table</h2>";
        
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ Table exists</p>";
                
                // Показываем количество записей
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $countStmt->fetch()['count'];
                echo "<p>Records count: <strong>$count</strong></p>";
                
                // Показываем первые 5 записей
                if ($count > 0) {
                    $dataStmt = $pdo->query("SELECT * FROM $table LIMIT 5");
                    $data = $dataStmt->fetchAll();
                    
                    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                    if (!empty($data)) {
                        echo "<tr>";
                        foreach (array_keys($data[0]) as $column) {
                            echo "<th style='padding: 5px;'>$column</th>";
                        }
                        echo "</tr>";
                        
                        foreach ($data as $row) {
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td style='padding: 5px;'>" . htmlspecialchars($value) . "</td>";
                            }
                            echo "</tr>";
                        }
                    }
                    echo "</table>";
                }
            } else {
                echo "<p style='color: red;'>✗ Table does not exist</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Проверяем конкретные данные для тестов
    echo "<h2>Test Results Analysis</h2>";
    
    // Проверяем пользователей
    $userStmt = $pdo->query("SELECT user_id, username, is_active FROM users LIMIT 10");
    $users = $userStmt->fetchAll();
    echo "<h3>Users:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>ID: {$user['user_id']}, Username: {$user['username']}, Active: " . ($user['is_active'] ? 'Yes' : 'No') . "</li>";
    }
    echo "</ul>";
    
    // Проверяем сессии
    $sessionStmt = $pdo->query("SELECT session_id, user_id, session_token, expires_at FROM sessions LIMIT 5");
    $sessions = $sessionStmt->fetchAll();
    echo "<h3>Active Sessions:</h3>";
    echo "<ul>";
    foreach ($sessions as $session) {
        $expired = strtotime($session['expires_at']) < time() ? ' (EXPIRED)' : ' (ACTIVE)';
        echo "<li>User ID: {$session['user_id']}, Token: " . substr($session['session_token'], 0, 20) . "...$expired</li>";
    }
    echo "</ul>";
    
    // Проверяем результаты тестов
    $testStmt = $pdo->query("SELECT tr.result_id, tr.user_id, t.topic_name, tr.score, tr.max_score, tr.completion_date 
                             FROM test_results tr 
                             JOIN topics t ON tr.topic_id = t.topic_id 
                             LIMIT 10");
    $tests = $testStmt->fetchAll();
    echo "<h3>Test Results:</h3>";
    echo "<ul>";
    foreach ($tests as $test) {
        $percentage = round(($test['score'] / $test['max_score']) * 100, 2);
        echo "<li>User ID: {$test['user_id']}, Topic: {$test['topic_name']}, Score: {$test['score']}/{$test['max_score']} ({$percentage}%), Date: {$test['completion_date']}</li>";
    }
    echo "</ul>";
    
    // Проверяем ответы пользователей
    $answerStmt = $pdo->query("SELECT ua.answer_id, ua.result_id, q.question_text, ua.answer_text, q.correct_answer 
                               FROM user_answers ua 
                               JOIN questions q ON ua.question_id = q.question_id 
                               LIMIT 10");
    $answers = $answerStmt->fetchAll();
    echo "<h3>User Answers:</h3>";
    echo "<ul>";
    foreach ($answers as $answer) {
        $isCorrect = $answer['answer_text'] === $answer['correct_answer'] ? '✓' : '✗';
        echo "<li>Result ID: {$answer['result_id']}, Question: " . substr($answer['question_text'], 0, 50) . "..., Answer: {$answer['answer_text']}, Correct: {$answer['correct_answer']} $isCorrect</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?> 