<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration for MAMP
define('DB_HOST', 'localhost:8889'); // MAMP default port
define('DB_NAME', 'language_learning_platform');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // MAMP default password

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log the error
            error_log("Database connection failed: " . $e->getMessage());
            
            // Check if database doesn't exist
            if ($e->getCode() == 1049) { // Error code for unknown database
                try {
                    // Try to create the database
                    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, $options);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE " . DB_NAME);
                    
                } catch (PDOException $e2) {
                    throw new PDOException("Failed to create database: " . $e2->getMessage(), $e2->getCode());
                }
            } else {
                throw new PDOException("Connection failed: " . $e->getMessage(), $e->getCode());
            }
        }
    }
    
    return $pdo;
}

// Test connection on include
try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    // Only log the error, don't throw it
    error_log("Initial database connection test failed: " . $e->getMessage());
}
?> 