<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'config/super_admin.php';

$database = new Database();

// Initialiser la base principale
$main_pdo = $database->getMainConnection();

// Créer la table des super admins
createSuperAdminTable($main_pdo);

// Créer la table des boutiques avec utilisateurs
$main_pdo->exec("CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    database_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Créer la table des utilisateurs
$main_pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier') NOT NULL DEFAULT 'cashier',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id),
    UNIQUE KEY unique_shop_username (shop_id, username)
)");
?>
