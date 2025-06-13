<?php
header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'admin_auth_check.php';

// Check if user is logged in and is admin
if (!checkAdminAuth()) {
    error_log("Unauthorized access attempt in get_topics.php");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    // Get all topics with their language information
    $stmt = $pdo->prepare("
        SELECT 
            t.topic_id,
            t.topic_name,
            l.language_name,
            t.difficulty_level,
            t.description
        FROM topics t
        JOIN languages l ON t.language_id = l.language_id
        ORDER BY t.topic_id DESC
    ");
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'topics' => $topics
    ]);

} catch (PDOException $e) {
    error_log("Error fetching topics from database: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error fetching topics from database',
        'details' => $e->getMessage()
    ]);
}
?> 