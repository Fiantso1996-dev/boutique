<?php
// Configuration du super administrateur
function isSuperAdmin() {
    return isset($_SESSION['super_admin']) && $_SESSION['super_admin'] === true;
}

function requireSuperAdmin() {
    if (!isSuperAdmin()) {
        header('Location: super_login.php');
        exit();
    }
}

function loginSuperAdmin($username, $password, $main_pdo) {
    $stmt = $main_pdo->prepare("SELECT * FROM super_admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['super_admin'] = true;
        $_SESSION['super_admin_id'] = $admin['id'];
        $_SESSION['super_admin_username'] = $admin['username'];
        return true;
    }
    return false;
}

function createSuperAdminTable($main_pdo) {
    $main_pdo->exec("CREATE TABLE IF NOT EXISTS super_admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        full_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}
?>
