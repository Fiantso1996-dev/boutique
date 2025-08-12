<?php
require_once 'includes/init.php';
require_once 'config/super_admin.php';
require_once 'includes/logger.php';
requireSuperAdmin();

$logger = new ActivityLogger($main_pdo);
$action = $_GET['action'] ?? 'list';
$message = '';

if ($_POST) {
    if ($action === 'add') {
        $shop_name = trim($_POST['shop_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $admin_username = trim($_POST['admin_username']);
        $admin_password = $_POST['admin_password'];
        $cashier_username = trim($_POST['cashier_username']);
        $cashier_password = $_POST['cashier_password'];
        
        try {
            $main_pdo->beginTransaction();
            
            // Créer la boutique
            $shop_db = $database->createShopDatabase($shop_name);
            
            $stmt = $main_pdo->prepare("INSERT INTO shops (name, phone, address, database_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$shop_name, $phone, $address, $shop_db]);
            $shop_id = $main_pdo->lastInsertId();
            
            // Créer les utilisateurs
            $stmt = $main_pdo->prepare("INSERT INTO users (shop_id, username, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$shop_id, $admin_username, password_hash($admin_password, PASSWORD_DEFAULT)]);
            
            $stmt = $main_pdo->prepare("INSERT INTO users (shop_id, username, password, role) VALUES (?, ?, ?, 'cashier')");
            $stmt->execute([$shop_id, $cashier_username, password_hash($cashier_password, PASSWORD_DEFAULT)]);
            
            $main_pdo->commit();
            
            // Log de l'activité
            $logger->log(null, null, 'Boutique créée', "Nouvelle boutique: $shop_name");
            
            $message = 'Boutique créée avec succès';
            $action = 'list';
            
        } catch (Exception $e) {
            $main_pdo->rollBack();
            $message = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Suppression de boutique
if ($action === 'delete' && isset($_GET['id'])) {
    $shop_id = intval($_GET['id']);
    
    try {
        $main_pdo->beginTransaction();
        
        // Récupérer les infos de la boutique
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shop) {
            // Supprimer les utilisateurs
            $stmt = $main_pdo->prepare("DELETE FROM users WHERE shop_id = ?");
            $stmt->execute([$shop_id]);
            
            // Supprimer les logs
            $stmt = $main_pdo->prepare("DELETE FROM activity_logs WHERE shop_id = ?");
            $stmt->execute([$shop_id]);
            
            // Supprimer la boutique
            $stmt = $main_pdo->prepare("DELETE FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            
            // Supprimer la base de données (optionnel - commenté pour sécurité)
            // $main_pdo->exec("DROP DATABASE IF EXISTS " . $shop['database_name']);
            
            $main_pdo->commit();
            
            // Log de l'activité
            $logger->log(null, null, 'Boutique supprimée', "Boutique supprimée: " . $shop['name']);
            
            $message = 'Boutique supprimée avec succès';
        }
        
    } catch (Exception $e) {
        $main_pdo->rollBack();
        $message = 'Erreur lors de la suppression: ' . $e->getMessage();
    }
    
    $action = 'list';
}

// Récupérer les boutiques
$shops = $main_pdo->query("SELECT * FROM shops ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Détails d'une boutique
$shop_details = null;
if ($action === 'view' && isset($_GET['id'])) {
    $shop_id = intval($_GET['id']);
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop_details) {
        // Récupérer les utilisateurs
        $stmt = $main_pdo->prepare("SELECT * FROM users WHERE shop_id = ?");
        $stmt->execute([$shop_id]);
        $shop_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les stats
        try {
            $shop_pdo = $database->getShopConnection($shop_details['database_name']);
            
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM products");
            $products_count = $stmt->fetch()['count'];
            
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total FROM sales");
            $sales_stats = $stmt->fetch();
            
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = CURDATE()");
            $today_stats = $stmt->fetch();
            
        } catch (Exception $e) {
            $products_count = 0;
            $sales_stats = ['count' => 0, 'total' => 0];
            $today_stats = ['count' => 0, 'total' => 0];
        }
        
        // Logs de la boutique
        $shop_logs = $logger->getShopLogs($shop_id, 10);
    }
}

// Connexion directe à une boutique
if ($action === 'login' && isset($_GET['id'])) {
    $shop_id = intval($_GET['id']);
    $stmt = $main_pdo->prepare("SELECT s.*, u.id as user_id, u.username, u.role FROM shops s JOIN users u ON s.id = u.shop_id WHERE s.id = ? AND u.role = 'admin' LIMIT 1");
    $stmt->execute([$shop_id]);
    $shop_admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop_admin) {
        $_SESSION['shop_id'] = $shop_admin['id'];
        $_SESSION['shop_name'] = $shop_admin['name'];
        $_SESSION['shop_db'] = $shop_admin['database_name'];
        $_SESSION['user_id'] = $shop_admin['user_id'];
        $_SESSION['username'] = $shop_admin['username'];
        $_SESSION['user_role'] = 'admin';
        
        header('Location: dashboard.php');
        exit();
    }
}

// Modification de mot de passe 
if ($_POST && $action === 'update_credentials' && isset($_GET['id'])) {
    $shop_id = intval($_GET['id']);
    $admin_username = trim($_POST['admin_username']);
    $admin_password = $_POST['admin_password'];
    $cashier_username = trim($_POST['cashier_username']);
    $cashier_password = $_POST['cashier_password'];

    try {
        $main_pdo->beginTransaction();

        // Mise à jour Admin
        $params = [$admin_username, $shop_id, 'admin'];
        $sql = "UPDATE users SET username = ?, password = password WHERE shop_id = ? AND role = ?";
        if (!empty($admin_password)) {
            $sql = "UPDATE users SET username = ?, password = ? WHERE shop_id = ? AND role = ?";
            $params = [$admin_username, password_hash($admin_password, PASSWORD_DEFAULT), $shop_id, 'admin'];
        }
        $stmt = $main_pdo->prepare($sql);
        $stmt->execute($params);

        // Mise à jour Caissier
        $params = [$cashier_username, $shop_id, 'cashier'];
        $sql = "UPDATE users SET username = ?, password = password WHERE shop_id = ? AND role = ?";
        if (!empty($cashier_password)) {
            $sql = "UPDATE users SET username = ?, password = ? WHERE shop_id = ? AND role = ?";
            $params = [$cashier_username, password_hash($cashier_password, PASSWORD_DEFAULT), $shop_id, 'cashier'];
        }
        $stmt = $main_pdo->prepare($sql);
        $stmt->execute($params);

        $main_pdo->commit();

        $logger->log(null, $shop_id, 'Modification identifiants', "Identifiants mis à jour pour la boutique ID $shop_id");
        $message = 'Identifiants mis à jour avec succès';
        $action = 'view';

    } catch (Exception $e) {
        $main_pdo->rollBack();
        $message = 'Erreur lors de la mise à jour : ' . $e->getMessage();
    }
}



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Boutiques - Super Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .modern-navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            animation: slideInDown 0.6s ease-out;
        }
        
        .modern-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .modern-navbar .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin: 0 0.25rem;
        }
        
        .modern-navbar .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .modern-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .modern-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            animation: fadeInUp 0.6s ease-out;
            transition: all 0.3s ease;
        }
        
        .modern-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .shop-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        .shop-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .shop-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .shop-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }
        
        .shop-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .shop-info {
            color: #64748b;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .shop-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin: 1rem 0;
        }
        
        .stat-item {
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .shop-revenue {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            margin: 1rem 0;
        }
        
        .revenue-amount {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .revenue-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .shop-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-modern {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            flex: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .create-btn {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(72, 187, 120, 0.3);
        }
        
        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(72, 187, 120, 0.4);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideInDown 0.5s ease-out;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .delete-btn {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        
        .delete-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 90%;
            animation: slideInUp 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar modern-navbar">
        <div class="container" style="display: flex; align-items: center;">
            <a href="super_dashboard.php" class="navbar-brand">🏢 Fiantso</a>
            <ul class="navbar-nav">
                <li><a href="super_dashboard.php" class="nav-link">Vue d'ensemble</a></li>
                <li><a href="super_shops.php" class="nav-link">Boutiques</a></li>
                <li><a href="super_logs.php" class="nav-link">Journal</a></li>
                <li class="nav-item">
                    <a href="super_settings.php" class="nav-link">
                        ⚙️ Paramètres
                    </a>
                </li>
                <li><a href="super_logout.php" class="nav-link">Déconnexion</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="modern-container">
        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'Erreur') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
            <div class="page-header">
                <h1 class="page-title">🏪 Gestion des Boutiques</h1>
                <a href="super_shops.php?action=add" class="create-btn">
                    ➕ Créer une boutique
                </a>
            </div>
            
            <div class="shop-grid">
                <?php foreach ($shops as $index => $shop): ?>
                    <?php
                    // Récupérer les stats de la boutique
                    $shop_stats = ['sales_today' => 0, 'revenue_today' => 0, 'total_products' => 0];
                    try {
                        $shop_pdo = $database->getShopConnection($shop['database_name']);
                        
                        $stmt = $shop_pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = CURDATE()");
                        $today = $stmt->fetch();
                        $shop_stats['sales_today'] = $today['count'];
                        $shop_stats['revenue_today'] = $today['total'];
                        
                        $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM products");
                        $shop_stats['total_products'] = $stmt->fetch()['count'];
                        
                        $status = 'active';
                    } catch (Exception $e) {
                        $status = 'inactive';
                    }
                    ?>
                    
                    <div class="shop-card" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="shop-header">
                            <h3 class="shop-name"><?php echo htmlspecialchars($shop['name']); ?></h3>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="shop-status <?php echo $status === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $status === 'active' ? 'Actif' : 'Inactif'; ?>
                                </span>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $shop['id']; ?>, '<?php echo htmlspecialchars($shop['name']); ?>')">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($shop['phone']): ?>
                            <div class="shop-info">
                                📞 <?php echo htmlspecialchars($shop['phone']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($status === 'active'): ?>
                            <div class="shop-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $shop_stats['sales_today']; ?></div>
                                    <div class="stat-label">Ventes</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $shop_stats['total_products']; ?></div>
                                    <div class="stat-label">Produits</div>
                                </div>
                            </div>
                            
                            <div class="shop-revenue">
                                <div class="revenue-amount">
                                    <?php echo number_format($shop_stats['revenue_today'], 0, ',', ' '); ?> Ar
                                </div>
                                <div class="revenue-label">CA aujourd'hui</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="shop-actions">
                            <a href="super_shops.php?action=view&id=<?php echo $shop['id']; ?>" class="btn-modern btn-primary">
                                👁️ Détails
                            </a>
                            <?php if ($status === 'active'): ?>
                                <a href="super_shops.php?action=login&id=<?php echo $shop['id']; ?>" class="btn-modern btn-success">
                                    🚀 Accéder
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php elseif ($action === 'add'): ?>
            <div class="page-header">
                <h1 class="page-title">➕ Créer une nouvelle boutique</h1>
            </div>
            
            <div class="modern-card" style="padding: 2rem;">
                <form method="POST">
                    <h3 style="margin-bottom: 1.5rem; color: #1a202c;">Informations de la boutique</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Nom de la boutique</label>
                        <input type="text" name="shop_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <h3 style="margin: 2rem 0 1.5rem 0; color: #1a202c;">Compte Administrateur</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Nom d'utilisateur Admin</label>
                        <input type="text" name="admin_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe Admin</label>
                        <input type="password" name="admin_password" class="form-control" required minlength="6">
                    </div>
                    
                    <h3 style="margin: 2rem 0 1.5rem 0; color: #1a202c;">Compte Caissier</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Nom d'utilisateur Caissier</label>
                        <input type="text" name="cashier_username" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe Caissier</label>
                        <input type="password" name="cashier_password" class="form-control" required minlength="6">
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn-modern btn-success" style="flex: 1;">
                            ✅ Créer la boutique
                        </button>
                        <a href="super_shops.php" class="btn-modern btn-danger">
                            ❌ Annuler
                        </a>
                    </div>
                </form>
            </div>
            
        <?php elseif ($action === 'view' && $shop_details): ?>
            <div class="page-header">
                <h1 class="page-title">🏪 <?php echo htmlspecialchars($shop_details['name']); ?></h1>
            </div>
            
            <div class="modern-card" style="padding: 2rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h3 style="color: #1a202c; margin-bottom: 1rem;">Informations générales</h3>
                        <div style="space-y: 0.5rem;">
                            <p><strong>Nom:</strong> <?php echo htmlspecialchars($shop_details['name']); ?></p>
                            <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($shop_details['phone']); ?></p>
                            <p><strong>Adresse:</strong> <?php echo htmlspecialchars($shop_details['address']); ?></p>
                            <p><strong>Date création:</strong> <?php echo date('d/m/Y H:i', strtotime($shop_details['created_at'])); ?></p>
                            <p><strong>Base de données:</strong> <code><?php echo htmlspecialchars($shop_details['database_name']); ?></code></p>
                        </div>
                        
                        <h3 style="color: #1a202c; margin: 2rem 0 1rem 0;">Utilisateurs</h3>
                        <?php foreach ($shop_users as $user): ?>
                            <div style="padding: 0.75rem; background: #f8fafc; margin: 0.5rem 0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <span style="background: <?php echo $user['role'] === 'admin' ? '#f56565' : '#667eea'; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                    <?php echo $user['role'] === 'admin' ? 'Admin' : 'Caissier'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div>
                        <h3 style="color: #1a202c; margin-bottom: 1rem;">Statistiques</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <div class="stat-item">
                                <div class="stat-number" style="color: #667eea;"><?php echo $products_count; ?></div>
                                <div class="stat-label">Produits</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number" style="color: #48bb78;"><?php echo $sales_stats['count']; ?></div>
                                <div class="stat-label">Ventes totales</div>
                            </div>
                        </div>
                        
                        <div class="shop-revenue">
                            <div class="revenue-amount"><?php echo number_format($sales_stats['total'], 0, ',', ' '); ?> Ar</div>
                            <div class="revenue-label">CA total</div>
                        </div>
                        
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 12px; text-align: center;">
                            <div style="font-size: 1.25rem; font-weight: 700; color: #1a202c;">
                                <?php echo number_format($today_stats['total'], 0, ',', ' '); ?> Ar
                            </div>
                            <div style="font-size: 0.875rem; color: #64748b;">
                                CA aujourd'hui (<?php echo $today_stats['count']; ?> ventes)
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modification  -->
                                <h3 style="margin-top:2rem;">🔑 Modifier les identifiants</h3>
<div class="modern-card" style="padding: 2rem; margin-top: 1rem;">
    <form method="POST" action="super_shops.php?action=update_credentials&id=<?php echo $shop_details['id']; ?>">
        <?php
        $admin = $main_pdo->prepare("SELECT * FROM users WHERE shop_id = ? AND role = 'admin' LIMIT 1");
        $admin->execute([$shop_details['id']]);
        $admin_user = $admin->fetch(PDO::FETCH_ASSOC);

        $cashier = $main_pdo->prepare("SELECT * FROM users WHERE shop_id = ? AND role = 'cashier' LIMIT 1");
        $cashier->execute([$shop_details['id']]);
        $cashier_user = $cashier->fetch(PDO::FETCH_ASSOC);
        ?>
        
        <h4>Compte Administrateur</h4>
        <div class="form-group">
            <label>Nom d'utilisateur</label>
            <input type="text" name="admin_username" value="<?php echo htmlspecialchars($admin_user['username']); ?>" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="admin_password" class="form-control" minlength="6">
        </div>

        <h4 style="margin-top:1.5rem;">Compte Caissier</h4>
        <div class="form-group">
            <label>Nom d'utilisateur</label>
            <input type="text" name="cashier_username" value="<?php echo htmlspecialchars($cashier_user['username']); ?>" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="cashier_password" class="form-control" minlength="6">
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn-modern btn-success">💾 Mettre à jour</button>
        </div>
    </form>
</div>

                 <!-- Modification  -->

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <a href="super_shops.php?action=login&id=<?php echo $shop_details['id']; ?>" class="btn-modern btn-success">
                        🚀 Accéder à la boutique
                    </a>
                    <a href="super_shops.php" class="btn-modern btn-primary">
                        ← Retour à la liste
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #1a202c; margin-bottom: 1rem;">⚠️ Confirmer la suppression</h3>
            <p style="color: #64748b; margin-bottom: 1.5rem;">
                Êtes-vous sûr de vouloir supprimer la boutique <strong id="shopNameToDelete"></strong> ?
                <br><br>
                <span style="color: #f56565; font-weight: 600;">Cette action est irréversible !</span>
            </p>
            <div style="display: flex; gap: 1rem;">
                <button onclick="deleteShop()" class="btn-modern btn-danger" style="flex: 1;">
                    🗑️ Supprimer
                </button>
                <button onclick="closeDeleteModal()" class="btn-modern btn-primary" style="flex: 1;">
                    ❌ Annuler
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let shopToDelete = null;
        
        function confirmDelete(shopId, shopName) {
            shopToDelete = shopId;
            document.getElementById('shopNameToDelete').textContent = shopName;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            shopToDelete = null;
        }
        
        function deleteShop() {
            if (shopToDelete) {
                window.location.href = `super_shops.php?action=delete&id=${shopToDelete}`;
            }
        }
        
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
        
        // Animation des cartes au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observer toutes les cartes
        document.querySelectorAll('.shop-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `all 0.6s ease-out ${index * 0.1}s`;
            observer.observe(card);
        });
        
        // Animation des boutons
        document.querySelectorAll('.btn-modern').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>
