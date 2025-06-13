<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Определяем структуру таблиц
    $tables = [
        'users' => "
            CREATE TABLE IF NOT EXISTS users (
                user_id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'sessions' => "
            CREATE TABLE IF NOT EXISTS sessions (
                session_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'topics' => "
            CREATE TABLE IF NOT EXISTS topics (
                topic_id INT AUTO_INCREMENT PRIMARY KEY,
                topic_name VARCHAR(255) NOT NULL,
                difficulty_level ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'questions' => "
            CREATE TABLE IF NOT EXISTS questions (
                question_id INT AUTO_INCREMENT PRIMARY KEY,
                topic_id INT NOT NULL,
                question_text TEXT NOT NULL,
                correct_answer TEXT NOT NULL,
                explanation TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'test_results' => "
            CREATE TABLE IF NOT EXISTS test_results (
                result_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                topic_id INT NOT NULL,
                score INT NOT NULL,
                max_score INT NOT NULL,
                time_spent INT NOT NULL,
                completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'user_answers' => "
            CREATE TABLE IF NOT EXISTS user_answers (
                answer_id INT AUTO_INCREMENT PRIMARY KEY,
                result_id INT NOT NULL,
                question_id INT NOT NULL,
                answer_text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (result_id) REFERENCES test_results(result_id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];

    $results = [];
    
    // Проверяем и создаем каждую таблицу
    foreach ($tables as $table => $createQuery) {
        try {
            // Проверяем существование таблицы
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            
            if (!$exists) {
                // Создаем таблицу
                $pdo->exec($createQuery);
                $results[$table] = "created";
            } else {
                // Проверяем структуру таблицы
                $stmt = $pdo->query("SHOW COLUMNS FROM $table");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $results[$table] = "exists with " . count($columns) . " columns";
            }
        } catch (PDOException $e) {
            $results[$table] = "error: " . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database check completed',
        'results' => $results
    ]);

} catch (Exception $e) {
    error_log("Database check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database check failed',
        'message' => $e->getMessage()
    ]);
}
?> 