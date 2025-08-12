<?php
require_once 'includes/init.php';
require_once 'includes/barcode.php';
requireLogin();
requireAdmin(); // Seul l'admin peut gérer les produits

$shop = getCurrentShop();
$user = getCurrentUser(); // Ajout pour la déconnexion
$shop_pdo = $database->getShopConnection($shop['db']);

$message = '';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $barcode = $_POST['barcode'] ?: BarcodeGenerator::generateEAN13();
        
        if ($action == 'add') {
            $stmt = $shop_pdo->prepare("INSERT INTO products (name, barcode, price, stock) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $barcode, $price, $stock]);
            $message = 'Produit ajouté avec succès';

            require_once 'includes/logger.php';
            $logger = new ActivityLogger($main_pdo);
            $logger->log($_SESSION['shop_id'], $_SESSION['user_id'], 'Produit ajouté', "Produit: $name");
        } else {
            $id = intval($_POST['id']);
            $stmt = $shop_pdo->prepare("UPDATE products SET name = ?, barcode = ?, price = ?, stock = ? WHERE id = ?");
            $stmt->execute([$name, $barcode, $price, $stock, $id]);
            $message = 'Produit modifié avec succès';

            require_once 'includes/logger.php';
            $logger = new ActivityLogger($main_pdo);
            $logger->log($_SESSION['shop_id'], $_SESSION['user_id'], 'Produit modifié', "Produit: $name");
        }
        
        header('Location: products.php?message=' . urlencode($message));
        exit;
    }
}

// Nouvelle section pour la gestion de la suppression
if ($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Start a transaction to ensure all deletions succeed or fail together
        $shop_pdo->beginTransaction();

        // Check if the product has associated sales. If so, delete them.
        $checkSalesStmt = $shop_pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
        $checkSalesStmt->execute([$id]);
        if ($checkSalesStmt->fetchColumn() > 0) {
            $deleteSalesStmt = $shop_pdo->prepare("DELETE FROM sale_items WHERE product_id = ?");
            $deleteSalesStmt->execute([$id]);
        }
        
        // Check if the product has stock movements. If so, delete them.
        $checkMovementsStmt = $shop_pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id = ?");
        $checkMovementsStmt->execute([$id]);
        if ($checkMovementsStmt->fetchColumn() > 0) {
            $deleteMovementsStmt = $shop_pdo->prepare("DELETE FROM stock_movements WHERE product_id = ?");
            $deleteMovementsStmt->execute([$id]);
        }

        // Finally, delete the product from the products table
        $stmt = $shop_pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        // Commit the transaction
        $shop_pdo->commit();

        $message = 'Produit, ses ventes et mouvements de stock associés ont été supprimés avec succès.';

        require_once 'includes/logger.php';
        $logger = new ActivityLogger($main_pdo);
        $logger->log($_SESSION['shop_id'], $_SESSION['user_id'], 'Produit supprimé', "ID: $id");

    } catch (PDOException $e) {
        // Rollback the transaction on error
        $shop_pdo->rollBack();
        $message = 'Une erreur est survenue lors de la suppression du produit : ' . $e->getMessage();
        $messageType = 'error';
    }

    header('Location: products.php?message=' . urlencode($message) . '&type=' . ($messageType ?? 'success'));
    exit;
}


// Récupérer les produits
$products = [];
if ($action == 'list') {
    $stmt = $shop_pdo->query("SELECT * FROM products ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer un produit pour édition
$product = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $shop_pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Gérer les messages de statut après une redirection
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
$messageType = $_GET['type'] ?? 'success'; // Ajout d'un type pour le style
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des produits - <?php echo htmlspecialchars($shop['name']); ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* Variables CSS pour un design cohérent */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #ecf0f1;
            --card-bg-color: #ffffff;
            --text-color: #2c3e50;
            --light-text-color: #7f8c8d;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Navbar */
        .navbar {
            background-color: var(--secondary-color);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .navbar-nav {
            list-style: none;
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 400;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }
        
        /* Alertes */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-success {
            background: var(--success-color);
        }

        .alert-error {
            background: var(--danger-color);
        }

        .alert-icon {
            font-size: 1.5rem;
        }

        /* Cartes (containers) */
        .card {
            background-color: var(--card-bg-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
        }

        /* Boutons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #e67e22;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        /* Formulaire */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        /* Tableau */
        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 1.5rem;
        }

        .table thead tr {
            background-color: #f4f4f4;
        }

        .table th, .table td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            text-transform: uppercase;
            font-weight: 600;
            color: var(--light-text-color);
        }

        .table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?php echo htmlspecialchars($shop['name']); ?></a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Tableau de bord</a></li>
                <li><a href="products.php" class="nav-link">Produits</a></li>
                <li><a href="pos.php" class="nav-link">Caisse</a></li>
                <li><a href="sales.php" class="nav-link">Ventes</a></li>
                <li><a href="logout.php" class="nav-link">Déconnexion (<?php echo htmlspecialchars($user['username']); ?>)</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php if ($messageType == 'success'): ?>
                    <i class="fas fa-check-circle alert-icon"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($action == 'list'): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Liste des produits</h2>
                    <a href="products.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un produit</a>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Code-barres</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--light-text-color);">Aucun produit n'a été trouvé.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['barcode']); ?></td>
                                    <td><?php echo number_format($prod['price'], 0, ',', ' '); ?> Ar</td>
                                    <td><?php echo $prod['stock']; ?></td>
                                    <td class="action-buttons">
                                        <a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                        <a href="barcode.php?id=<?php echo $prod['id']; ?>" class="btn btn-primary" title="Imprimer le code-barres" target="_blank"><i class="fas fa-barcode"></i></a>
                                        <a href="products.php?action=delete&id=<?php echo $prod['id']; ?>" class="btn btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible si le produit n\'a jamais été vendu.')"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo $action == 'add' ? 'Ajouter un nouveau produit' : 'Modifier le produit'; ?></h2>
                </div>
                
                <form method="POST">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Nom du produit</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="barcode" class="form-label">Code-barres (laisser vide pour générer)</label>
                        <input type="text" id="barcode" name="barcode" class="form-control" value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="price" class="form-label">Prix (Ar)</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" value="<?php echo $product['price'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock" class="form-label">Stock initial</label>
                        <input type="number" id="stock" name="stock" class="form-control" value="<?php echo $product['stock'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
                        <a href="products.php" class="btn btn-danger"><i class="fas fa-times"></i> Annuler</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>