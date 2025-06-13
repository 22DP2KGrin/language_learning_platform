<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/config/database.php';

echo "<h1>Creating New Session</h1>";

try {
    $pdo = getDBConnection();
    
    // Получаем первого пользователя (di19)
    $userStmt = $pdo->query("SELECT user_id, username FROM users WHERE username = 'di19' LIMIT 1");
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo "<p style='color: red;'>User 'di19' not found!</p>";
        exit;
    }
    
    echo "<p>Found user: {$user['username']} (ID: {$user['user_id']})</p>";
    
    // Генерируем новый токен сессии
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Создаем новую сессию
    $insertStmt = $pdo->prepare("
        INSERT INTO sessions (user_id, session_token, expires_at, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $insertStmt->execute([
        $user['user_id'],
        $sessionToken,
        $expiresAt,
        '::1',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'
    ]);
    
    echo "<p style='color: green;'>✓ New session created successfully!</p>";
    echo "<p><strong>Session Token:</strong> $sessionToken</p>";
    echo "<p><strong>Expires:</strong> $expiresAt</p>";
    
    // Сохраняем токен в файл для использования
    file_put_contents('session_token.txt', $sessionToken);
    echo "<p style='color: blue;'>Token saved to session_token.txt</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 