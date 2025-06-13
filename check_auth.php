<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function isLoggedIn() {
    // Проверяем, авторизован ли пользователь (обычный или администратор)
    if (isset($_SESSION['user_id'])) {
         // Обычный пользователь: проверяем, что пользователь активен
         require_once __DIR__ . '/../config/database.php';
         try {
              $stmt = $pdo->prepare("SELECT is_active FROM users WHERE user_id = ?");
              $stmt->execute([$_SESSION['user_id']]);
              $user = $stmt->fetch(PDO::FETCH_ASSOC);
              if ($user && $user['is_active']) {
                   return true;
              } else {
                   error_log("User (user_id: " . $_SESSION['user_id'] . ") not found or inactive.");
              }
         } catch (PDOException $e) {
              error_log("Database error in isLoggedIn (user): " . $e->getMessage());
         }
    } else if (isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
         // Администратор: если в сессии есть admin_id и is_admin равен true, считаем авторизованным
         return true;
    }
    // Если ни обычный пользователь, ни администратор не авторизованы, возвращаем false
    return false;
}

// Если файл включен напрямую, проверяем авторизацию
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
?> 