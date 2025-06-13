<?php
header('Content-Type: application/json');
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not authenticated'
    ]);
    exit;
}

if (!isset($_GET['result_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Result ID is required'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get test result details
    $stmt = $pdo->prepare("
        SELECT 
            tr.*,
            t.title as test_title,
            t.difficulty,
            DATE_FORMAT(tr.completion_date, '%Y-%m-%d %H:%i') as completion_date_formatted,
            SEC_TO_TIME(tr.time_spent) as time_spent_formatted,
            ROUND((tr.score / tr.max_score) * 100) as score_percentage
        FROM test_results tr
        JOIN tests t ON tr.test_id = t.id
        WHERE tr.id = ? AND tr.user_id = ?
    ");
    
    $stmt->execute([$_GET['result_id'], $_SESSION['user_id']]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test) {
        echo json_encode([
            'success' => false,
            'error' => 'Test result not found'
        ]);
        exit;
    }
    
    // Get errors for this test
    $errorStmt = $pdo->prepare("
        SELECT 
            te.*,
            q.question_text
        FROM test_errors te
        JOIN questions q ON te.question_id = q.id
        WHERE te.result_id = ?
    ");
    
    $errorStmt->execute([$_GET['result_id']]);
    $errors = $errorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'test' => $test,
        'errors' => $errors,
        'navigation' => [
            'back_to_history' => 'test_history.html'
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in get_test_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?> 