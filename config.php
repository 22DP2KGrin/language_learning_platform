<?php
// Database configuration for MAMP
define('DB_HOST', 'localhost:8889'); // MAMP default port
define('DB_NAME', 'language_learning_platform'); // Updated database name
define('DB_USER', 'root');
define('DB_PASS', 'root'); // MAMP default password

// Session configuration
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// Set timezone
date_default_timezone_set('UTC');

// Function to create database tables
function createTables($pdo) {
    try {
        // Create users table
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            country VARCHAR(100),
            phone VARCHAR(20),
            birth_date DATE,
            gender ENUM('male', 'female', 'other'),
            bio TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Create sessions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            session_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Create languages table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS languages (
            language_id INT AUTO_INCREMENT PRIMARY KEY,
            language_name VARCHAR(50) NOT NULL UNIQUE,
            language_code VARCHAR(10) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Create topics table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS topics (
            topic_id INT AUTO_INCREMENT PRIMARY KEY,
            language_id INT NOT NULL,
            topic_name VARCHAR(100) NOT NULL,
            description TEXT,
            difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (language_id) REFERENCES languages(language_id) ON DELETE CASCADE,
            INDEX idx_language_difficulty (language_id, difficulty_level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insert default language if not exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO languages (language_name, language_code) VALUES ('English', 'en')");
        $stmt->execute();

        // Insert default topics if not exist
        $stmt = $pdo->prepare("INSERT IGNORE INTO topics (language_id, topic_name, description, difficulty_level) VALUES 
            (1, 'Present Simple', 'Learn about present simple tense and its usage', 'beginner'),
            (1, 'My Daily Routine', 'Practice vocabulary and grammar related to daily activities', 'beginner'),
            (1, 'Basic Vocabulary', 'Essential words for everyday conversations', 'beginner')");
        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
        return false;
    }
}

// Function to initialize database connection
function initDatabase() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        // Create tables if they don't exist
        if (!createTables($pdo)) {
            throw new Exception("Failed to create database tables");
        }

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

// Initialize database connection
try {
    $pdo = initDatabase();
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
}
?>