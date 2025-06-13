<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'admin_auth_check.php';

// Check if user is logged in and is admin
if (!checkAdminAuth()) {
    error_log("Unauthorized access attempt in get_exercises.php");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    // Get all exercises with their topic information
    $stmt = $pdo->prepare("
        SELECT 
            e.exercise_id,
            e.exercise_name,
            t.topic_name,
            e.time_limit,
            e.total_questions,
            e.description
        FROM exercises e
        JOIN topics t ON e.topic_id = t.topic_id
        ORDER BY e.exercise_id DESC
    ");
    $stmt->execute();
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'exercises' => $exercises
    ]);

} catch (PDOException $e) {
    error_log("Error fetching exercises from database: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error fetching exercises from database',
        'details' => $e->getMessage()
    ]);
}
?> 