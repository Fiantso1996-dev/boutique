<?php
require_once 'includes/init.php';
requireLogin();
requireAdmin(); // Seul l'admin peut voir le dashboard

$shop = getCurrentShop();
$user = getCurrentUser();
$shop_pdo = $database->getShopConnection($shop['db']);

// Statistiques
$stats = [];

// Nombre de produits
$stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM products");
$stats['products'] = $stmt->fetch()['count'];

// Ventes du jour
$stmt = $shop_pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = CURDATE()");
$today_sales = $stmt->fetch();
$stats['today_sales'] = $today_sales['count'];
$stats['today_revenue'] = $today_sales['total'];

// Stock total
$stmt = $shop_pdo->query("SELECT COALESCE(SUM(stock), 0) as total FROM products");
$stats['total_stock'] = $stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - <?php echo htmlspecialchars($shop['name']); ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Styles CSS pour un design moderne */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --background-color: #ecf0f1;
            --card-bg-color: #ffffff;
            --text-color: #2c3e50;
            --light-text-color: #7f8c8d;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
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

        /* Contenu principal */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Grille des statistiques */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--card-bg-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary-color);
        }

        .stat-label {
            font-size: 1rem;
            color: var(--light-text-color);
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Carte des actions rapides */
        .card {
            background-color: var(--card-bg-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .card-header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
        }

        /* Boutons d'actions */
        .btn-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: center;
            white-space: nowrap;
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
            background-color: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #229954;
        }

        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #e67e22;
        }

        /* icônes des boutons */
        .btn i {
            margin-right: 0.5rem;
        }
        
        /* Version mobile */
        @media (max-width: 768px) {
            .navbar .container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .navbar-nav {
                flex-direction: column;
                gap: 0.5rem;
            }
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
    
    <div class="main-container">
        <div class="dashboard-grid">
            <div class="stat-card">
                <i class="fas fa-box-open stat-icon"></i>
                <div style="font-size: 30px;" class="stat-number"><?php echo $stats['products']; ?></div>
                <div class="stat-label">Produits</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-chart-line stat-icon"></i>
                <div style="font-size: 30px;" class="stat-number"><?php echo $stats['today_sales']; ?></div>
                <div class="stat-label">Ventes aujourd'hui</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-money-bill-wave stat-icon"></i>
                <div style="font-size: 30px;" class="stat-number"><?php echo number_format($stats['today_revenue'], 0, ',', ' '); ?> Ar</div>
                <div class="stat-label">Chiffre d'affaires</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-boxes stat-icon"></i>
                <div style="font-size: 30px;" class="stat-number"><?php echo $stats['total_stock']; ?></div>
                <div class="stat-label">Stock total</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Actions rapides</h2>
            </div>
            
            <div class="btn-group">
                <a href="products.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un produit</a>
                <a href="pos.php" class="btn btn-success"><i class="fas fa-cash-register"></i> Ouvrir la caisse</a>
                <a href="sales.php" class="btn btn-warning"><i class="fas fa-history"></i> Voir les ventes</a>
            </div>
        </div>
    </div>
</body>
</html>