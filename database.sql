-- Создание базы данных
CREATE DATABASE IF NOT EXISTS language_learning_platform 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE language_learning_platform;

-- Удаление существующих таблиц (если они есть)
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS topics;
DROP TABLE IF EXISTS languages;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS test_results;
DROP TABLE IF EXISTS test_errors;

-- Таблица пользователей
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    country VARCHAR(100),
    phone VARCHAR(20),
    birth_date DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    bio TEXT,
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    is_admin BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица языков
CREATE TABLE languages (
    language_id INT AUTO_INCREMENT PRIMARY KEY,
    language_name VARCHAR(50) NOT NULL UNIQUE,
    language_code VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_language_code (language_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица тем
CREATE TABLE topics (
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    language_id INT NOT NULL,
    topic_name VARCHAR(100) NOT NULL,
    description TEXT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(language_id) ON DELETE CASCADE,
    INDEX idx_language_difficulty (language_id, difficulty_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица сессий
CREATE TABLE sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45) NULL DEFAULT NULL,
    user_agent VARCHAR(255) NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица результатов тестов
CREATE TABLE test_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    topic_id INT NOT NULL,
    score INT NOT NULL,
    max_score INT NOT NULL,
    time_spent INT NOT NULL COMMENT 'Time spent in seconds',
    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_topic (user_id, topic_id),
    INDEX idx_user_id (user_id),
    INDEX idx_topic_id (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для хранения деталей ошибок в тестах
CREATE TABLE test_errors (
    error_id INT AUTO_INCREMENT PRIMARY KEY,
    result_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer TEXT,
    correct_answer TEXT,
    question_text TEXT,
    FOREIGN KEY (result_id) REFERENCES test_results(result_id) ON DELETE CASCADE,
    INDEX idx_result_id (result_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка стандартного языка
INSERT INTO languages (language_name, language_code) VALUES ('English', 'en');

-- Вставка тем из папки exercises
INSERT INTO topics (language_id, topic_name, description, difficulty_level) VALUES
(1, 'Basic Vocabulary', 'Basic vocabulary exercise.', 'beginner'),
(1, 'Present Simple', 'Present simple tense exercise.', 'beginner'),
(1, 'My Daily Routine', 'Daily routine vocabulary and grammar.', 'beginner'),
(1, 'Introducing Yourself', 'Self introduction exercise.', 'beginner'),
(1, 'Opinion Essay', 'Opinion essay writing.', 'intermediate'),
(1, 'Idiomatic Expressions', 'Idiomatic expressions practice.', 'intermediate'),
(1, 'Conditionals Wishes', 'Conditionals and wishes exercise.', 'intermediate'),
(1, 'City vs Country', 'City versus country vocabulary and discussion.', 'intermediate'),
(1, 'Phrasal Verbs', 'Phrasal verbs practice.', 'intermediate'),
(1, 'Present Perfect vs Past Simple', 'Present perfect versus past simple exercise.', 'intermediate'); 