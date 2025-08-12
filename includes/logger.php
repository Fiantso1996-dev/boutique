<?php
class ActivityLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createLogTable();
    }
    
    private function createLogTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (shop_id) REFERENCES shops(id)
        )");
    }
    
    public function log($shop_id, $user_id, $action, $details = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $this->pdo->prepare("INSERT INTO activity_logs (shop_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$shop_id, $user_id, $action, $details, $ip]);
    }
    
    public function getRecentLogs($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT al.*, s.name as shop_name, u.username 
            FROM activity_logs al 
            LEFT JOIN shops s ON al.shop_id = s.id 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getShopLogs($shop_id, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT al.*, u.username 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            WHERE al.shop_id = ? 
            ORDER BY al.created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$shop_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
