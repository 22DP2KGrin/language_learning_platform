<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

try {
    // Check if admins table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Admins table does not exist. Creating it now...'
        ]);
        
        // Create admins table
        $sql = "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'moderator') NOT NULL DEFAULT 'admin',
            permissions JSON,
            is_active BOOLEAN DEFAULT TRUE,
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Insert default admin
        $sql = "INSERT INTO admins (username, email, password, role, permissions, is_active) 
                VALUES (
                    'PlatformAdmin',
                    'admin@languageplatform.com',
                    :password,
                    'super_admin',
                    :permissions,
                    TRUE
                )";
        
        $stmt = $pdo->prepare($sql);
        $password = password_hash('LinguaAdmin@2025!', PASSWORD_DEFAULT);
        $permissions = json_encode([
            'canApproveUsers' => true,
            'canManageCourses' => true,
            'canManageContent' => true,
            'canViewAnalytics' => true,
            'canManageAdmins' => true,
            'canModerateContent' => true,
            'canAccessReports' => true
        ]);
        
        $stmt->execute([
            ':password' => $password,
            ':permissions' => $permissions
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Admins table created and default admin added successfully'
        ]);
    } else {
        // Check if admin exists
        $stmt = $pdo->query("SELECT id, username, email, role, is_active FROM admins WHERE email = 'admin@languageplatform.com'");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo json_encode([
                'success' => true,
                'message' => 'Admin exists in database',
                'admin' => $admin
            ]);
        } else {
            // Add default admin if not exists
            $sql = "INSERT INTO admins (username, email, password, role, permissions, is_active) 
                    VALUES (
                        'PlatformAdmin',
                        'admin@languageplatform.com',
                        :password,
                        'super_admin',
                        :permissions,
                        TRUE
                    )";
            
            $stmt = $pdo->prepare($sql);
            $password = password_hash('LinguaAdmin@2025!', PASSWORD_DEFAULT);
            $permissions = json_encode([
                'canApproveUsers' => true,
                'canManageCourses' => true,
                'canManageContent' => true,
                'canViewAnalytics' => true,
                'canManageAdmins' => true,
                'canModerateContent' => true,
                'canAccessReports' => true
            ]);
            
            $stmt->execute([
                ':password' => $password,
                ':permissions' => $permissions
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Default admin added successfully'
            ]);
        }
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 