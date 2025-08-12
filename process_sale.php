<?php
require_once 'includes/init.php';
requireLogin();

header('Content-Type: application/json');

$shop = getCurrentShop();
$shop_pdo = $database->getShopConnection($shop['db']);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['items']) || empty($input['items'])) {
        throw new Exception('Données invalides');
    }
    
    $shop_pdo->beginTransaction();
    
    // Générer le numéro de facture
    $stmt = $shop_pdo->query("SELECT COUNT(*) + 1 as next_number FROM sales");
    $invoice_number = $stmt->fetch()['next_number'];
    
    // Créer la vente
    $stmt = $shop_pdo->prepare("INSERT INTO sales (invoice_number, total, payment_received, change_amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $invoice_number,
        $input['total'],
        $input['payment'],
        $input['change']
    ]);
    
    $sale_id = $shop_pdo->lastInsertId();
    
    // Ajouter les articles et mettre à jour le stock
    foreach ($input['items'] as $item) {
        // Vérifier le stock
        $stmt = $shop_pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $current_stock = $stmt->fetch()['stock'];
        
        if ($current_stock < $item['quantity']) {
            throw new Exception("Stock insuffisant pour " . $item['name']);
        }
        
        // Ajouter l'article à la vente
        $stmt = $shop_pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $sale_id,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $item['price'] * $item['quantity']
        ]);
        
        // Mettre à jour le stock
        $stmt = $shop_pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['id']]);
        
        // Enregistrer le mouvement de stock
        $stmt = $shop_pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reason) VALUES (?, 'out', ?, 'Vente')");
        $stmt->execute([$item['id'], $item['quantity']]);
    }
    
    $shop_pdo->commit();
    
    // Logger l'activité
    require_once 'includes/logger.php';
    $logger = new ActivityLogger($main_pdo);
    $logger->log($_SESSION['shop_id'], $_SESSION['user_id'], 'Vente enregistrée', "Facture N°{$invoice_number} - Total: {$input['total']} Ar");
    
    echo json_encode([
        'success' => true,
        'sale_id' => $sale_id,
        'invoice_number' => $invoice_number
    ]);
    
} catch (Exception $e) {
    $shop_pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
