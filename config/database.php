<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $main_db = 'gestion_caisse_main';
    
    public function getMainConnection() {
        try {
            $pdo = new PDO("mysql:host={$this->host}", $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la base principale si elle n'existe pas
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$this->main_db}");
            $pdo->exec("USE {$this->main_db}");
            
            return $pdo;
        } catch(PDOException $e) {
            die("Erreur de connexion: " . $e->getMessage());
        }
    }
    
    public function getShopConnection($shop_db) {
        try {
            $pdo = new PDO("mysql:host={$this->host};dbname={$shop_db}", $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch(PDOException $e) {
            die("Erreur de connexion boutique: " . $e->getMessage());
        }
    }

    public function databaseExists($db_name) {
        try {
            $pdo = new PDO("mysql:host={$this->host}", $this->username, $this->password);
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$db_name]);
            return $stmt->fetch() !== false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function createShopDatabase($shop_name) {
        $shop_db = 'shop_' . preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($shop_name));
        
        try {
            $pdo = new PDO("mysql:host={$this->host}", $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la base de données de la boutique
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$shop_db}");
            $pdo->exec("USE {$shop_db}");
            
            // Créer les tables
            $this->createShopTables($pdo);
            
            return $shop_db;
        } catch(PDOException $e) {
            die("Erreur création base boutique: " . $e->getMessage());
        }
    }
    
    private function createShopTables($pdo) {
        // Table produits
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            barcode VARCHAR(13) UNIQUE,
            price DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Table ventes
        $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) UNIQUE,
            total DECIMAL(10,2) NOT NULL,
            payment_received DECIMAL(10,2) NOT NULL,
            change_amount DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Table détails ventes
        $pdo->exec("CREATE TABLE IF NOT EXISTS sale_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT,
            product_id INT,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (sale_id) REFERENCES sales(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
        
        // Table mouvements stock
        $pdo->exec("CREATE TABLE IF NOT EXISTS stock_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            type ENUM('in', 'out') NOT NULL,
            quantity INT NOT NULL,
            reason VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");
    }
}
?>
