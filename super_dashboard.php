<?php
require_once 'includes/init.php';
require_once 'config/super_admin.php';
require_once 'includes/logger.php';
requireSuperAdmin();

$logger = new ActivityLogger($main_pdo);

// Statistiques globales
$stats = [];

// Nombre total de boutiques
$stmt = $main_pdo->query("SELECT COUNT(*) as count FROM shops");
$stats['total_shops'] = $stmt->fetch()['count'];

// Nombre total d'utilisateurs
$stmt = $main_pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch()['count'];

// Chiffre d'affaires total aujourd'hui
$total_revenue_today = 0;
$total_sales_today = 0;

$shops = $main_pdo->query("SELECT * FROM shops")->fetchAll(PDO::FETCH_ASSOC);
foreach ($shops as $shop) {
    try {
        $shop_pdo = $database->getShopConnection($shop['database_name']);
        $stmt = $shop_pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = CURDATE()");
        $shop_sales = $stmt->fetch();
        $total_revenue_today += $shop_sales['total'];
        $total_sales_today += $shop_sales['count'];
    } catch (Exception $e) {
        // Ignorer les erreurs de connexion
    }
}

$stats['total_revenue_today'] = $total_revenue_today;
$stats['total_sales_today'] = $total_sales_today;

// Données pour les graphiques (7 derniers jours)
$chart_data = [];
$chart_labels = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($date));
    
    $daily_revenue = 0;
    foreach ($shops as $shop) {
        try {
            $shop_pdo = $database->getShopConnection($shop['database_name']);
            $stmt = $shop_pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $daily_revenue += $stmt->fetch()['total'];
        } catch (Exception $e) {
            // Ignorer les erreurs
        }
    }
    $chart_data[] = $daily_revenue;
}

// Logs récents
$recent_logs = $logger->getRecentLogs(20);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Dashboard - Administration</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f1f5f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: #1e293b;
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }
        
        .sidebar-brand {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .brand-subtitle {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 2rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            border-left-color: #3b82f6;
        }
        
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 2rem;
            animation: fadeIn 0.8s ease-out;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 0.5rem 0;
        }
        
        .page-subtitle {
            color: #64748b;
            font-size: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.blue::before {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        }
        
        .stat-card.cyan::before {
            background: linear-gradient(90deg, #06b6d4, #0891b2);
        }
        
        .stat-card.pink::before {
            background: linear-gradient(90deg, #ec4899, #be185d);
        }
        
        .stat-card.purple::before {
            background: linear-gradient(90deg, #8b5cf6, #7c3aed);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .stat-icon.cyan {
            background: rgba(6, 182, 212, 0.1);
            color: #06b6d4;
        }
        
        .stat-icon.pink {
            background: rgba(236, 72, 153, 0.1);
            color: #ec4899;
        }
        
        .stat-icon.purple {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
            animation: countUp 1s ease-out;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            animation: slideInLeft 0.8s ease-out;
        }
        
        .chart-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .activity-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            animation: slideInRight 0.8s ease-out;
        }
        
        .activity-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid #e2e8f0;
            margin-bottom: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .activity-item:hover {
            transform: translateX(4px);
            border-left-color: #3b82f6;
            background: #f1f5f9;
        }
        
        .activity-action {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .activity-meta {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .shops-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            animation: fadeInUp 1s ease-out;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .create-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
        }
        
        .create-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.4);
        }
        
        .shops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .shop-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
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
            height: 3px;
            background: linear-gradient(90deg, #10b981, #059669);
        }
        
        .shop-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            background: white;
        }
        
        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .shop-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
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
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .shop-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin: 1rem 0;
        }
        
        .shop-stat {
            text-align: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .shop-stat-number {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .shop-stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .shop-revenue {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin: 1rem 0;
        }
        
        .revenue-amount {
            font-size: 1.25rem;
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
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        @keyframes countUp {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideInUp 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-close {
            font-size: 2rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .modal-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 2rem;
        }

        .detail-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .detail-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .detail-label {
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .shops-breakdown h4 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .breakdown-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 4px solid #3b82f6;
            transition: all 0.3s ease;
        }

        .breakdown-item:hover {
            background: white;
            transform: translateX(4px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .breakdown-shop {
            font-weight: 600;
            color: #1e293b;
        }

        .breakdown-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .breakdown-revenue {
            font-weight: 600;
            color: #059669;
        }

        .chart-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn-action {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-action:nth-child(2) {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Curseur pointer sur les points du graphique */
        .chart-container canvas {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <a style="text-align: center;" href="super_dashboard.php" class="brand-title">
                    🏢 Fiantso
                </a>
                <div class="brand-subtitle" style="text-align: center;">Gestion de caisse</div>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="super_dashboard.php" class="nav-link active">
                        📊 Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="super_shops.php" class="nav-link">
                        🏪 Boutiques
                    </a>
                </li>
                <li class="nav-item">
                    <a href="super_logs.php" class="nav-link">
                        📋 Journal
                    </a>
                </li>
                <li class="nav-item">
                    <a href="super_settings.php" class="nav-link">
                        ⚙️ Paramètres
                    </a>
                </li>
                <li class="nav-item">
                    <a href="super_logout.php" class="nav-link">
                        🚪 Déconnexion
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Vue d'ensemble de toutes les boutiques</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card blue" style="animation-delay: 0.1s;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number" data-target="<?php echo $stats['total_shops']; ?>">0</div>
                            <div class="stat-label">BOUTIQUES ACTIVES</div>
                        </div>
                        <div class="stat-icon blue">🏪</div>
                    </div>
                </div>
                
                <div class="stat-card cyan" style="animation-delay: 0.2s;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number" data-target="<?php echo $stats['total_users']; ?>">0</div>
                            <div class="stat-label">TOTAL UTILISATEURS</div>
                        </div>
                        <div class="stat-icon cyan">👥</div>
                    </div>
                </div>
                
                <div class="stat-card pink" style="animation-delay: 0.3s;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number" data-target="<?php echo $stats['total_sales_today']; ?>">0</div>
                            <div class="stat-label">TRANSACTIONS</div>
                        </div>
                        <div class="stat-icon pink">💳</div>
                    </div>
                </div>
                
                <!-- <div class="stat-card purple" style="animation-delay: 0.4s;">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number" data-target="<?php echo round($stats['total_revenue_today'] / 1000000, 1); ?>"><?php echo round($stats['total_revenue_today'] / 1000000, 1); ?></div>
                            <div class="stat-label">SOLDE TOTAL ARIARY</div>
                        </div>
                        <div class="stat-icon purple">💰</div>
                    </div>
                </div> -->
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <span>📈</span>
                        <h3 class="chart-title">Transactions par jour (7 derniers jours)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="activity-card">
                    <div class="activity-header">
                        <span>ℹ️</span>
                        <h3 class="chart-title">Transactions Récentes</h3>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($recent_logs as $index => $log): ?>
                            <div class="activity-item" style="animation-delay: <?php echo $index * 0.1; ?>">
                                <div class="activity-action">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </div>
                                <div class="activity-meta">
                                    <strong><?php echo htmlspecialchars($log['shop_name'] ?? 'Système'); ?></strong>
                                    <?php if ($log['username']): ?>
                                        par <?php echo htmlspecialchars($log['username']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="shops-section">
                <div class="section-header">
                    <h3 class="section-title">⚡ Actions Rapides</h3>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="super_shops.php?action=add" class="create-btn">
                        ➕ Nouvelle Boutique
                    </a>
                    <a href="super_shops.php" class="create-btn" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                        👥 Gérer Boutiques
                    </a>
                    <a href="super_logs.php" class="create-btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        📊 Voir Journal
                    </a>
                    <!-- <a href="#" class="create-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        🔄 Synchroniser
                    </a> -->
                </div>
            </div>
            
            <!-- Shops Overview -->
            <div class="shops-section" style="margin-top: 2rem;">
                <div class="section-header">
                    <h3 class="section-title">🏪 Boutiques Actives</h3>
                    <a href="super_shops.php" class="create-btn">
                        Voir toutes
                    </a>
                </div>
                
                <div class="shops-grid">
                    <?php foreach (array_slice($shops, 0, 6) as $index => $shop): ?>
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
                        
                        <div class="shop-card" style="animation-delay: <?php echo $index * 0.1; ?>">
                            <div class="shop-header">
                                <h4 class="shop-name"><?php echo htmlspecialchars($shop['name']); ?></h4>
                                <span class="shop-status <?php echo $status === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $status === 'active' ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </div>
                            
                            <?php if ($shop['phone']): ?>
                                <div style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">
                                    📞 <?php echo htmlspecialchars($shop['phone']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($status === 'active'): ?>
                                <div class="shop-stats">
                                    <div class="shop-stat">
                                        <div class="shop-stat-number"><?php echo $shop_stats['sales_today']; ?></div>
                                        <div class="shop-stat-label">Ventes</div>
                                    </div>
                                    <div class="shop-stat">
                                        <div class="shop-stat-number"><?php echo $shop_stats['total_products']; ?></div>
                                        <div class="shop-stat-label">Produits</div>
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
                                <a href="super_shops.php?action=view&id=<?php echo $shop['id']; ?>" class="btn-sm btn-primary">
                                    👁️ Détails
                                </a>
                                <?php if ($status === 'active'): ?>
                                    <a href="super_shops.php?action=login&id=<?php echo $shop['id']; ?>" class="btn-sm btn-success">
                                        🚀 Accéder
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de détails du graphique -->
    <div id="chartModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Détails du jour</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="detail-stats">
                    <div class="detail-card">
                        <div class="detail-number" id="modalTotalSales">0</div>
                        <div class="detail-label">Total Ventes</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-number" id="modalTotalRevenue">0 Ar</div>
                        <div class="detail-label">Chiffre d'Affaires</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-number" id="modalActiveShops">0</div>
                        <div class="detail-label">Boutiques Actives</div>
                    </div>
                </div>
                
                <div class="shops-breakdown">
                    <h4>Détail par boutique</h4>
                    <div id="shopsBreakdown" class="breakdown-list">
                        <!-- Contenu généré dynamiquement -->
                    </div>
                </div>
                
                <div class="chart-actions">
                    <button class="btn-action" onclick="exportDayData()">
                        📊 Exporter les données
                    </button>
                    <button class="btn-action" onclick="viewShopDetails()">
                        🏪 Voir détails boutiques
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Données détaillées pour chaque jour (générées côté PHP)
        const detailedData = <?php 
$detailed_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_data = [
        'date' => $date,
        'formatted_date' => date('d/m/Y', strtotime($date)),
        'total_sales' => 0,
        'total_revenue' => 0,
        'shops' => []
    ];
    
    foreach ($shops as $shop) {
        try {
            $shop_pdo = $database->getShopConnection($shop['database_name']);
            $stmt = $shop_pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $shop_sales = $stmt->fetch();
            
            if ($shop_sales['count'] > 0) {
                $day_data['shops'][] = [
                    'name' => $shop['name'],
                    'sales' => $shop_sales['count'],
                    'revenue' => $shop_sales['total']
                ];
                $day_data['total_sales'] += $shop_sales['count'];
                $day_data['total_revenue'] += $shop_sales['total'];
            }
        } catch (Exception $e) {
            // Ignorer les erreurs
        }
    }
    
    $detailed_data[] = $day_data;
}
echo json_encode($detailed_data);
?>;

        // Animation des compteurs
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number[data-target]');
            
            counters.forEach(counter => {
                const target = parseFloat(counter.getAttribute('data-target'));
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        counter.textContent = target % 1 === 0 ? target.toLocaleString() : target.toFixed(1);
                        clearInterval(timer);
                    } else {
                        const displayValue = current % 1 === 0 ? Math.floor(current).toLocaleString() : current.toFixed(1);
                        counter.textContent = displayValue;
                    }
                }, 16);
            });
        }

        // Fonction pour afficher les détails d'un jour
        function showDayDetails(dayIndex) {
            const dayData = detailedData[dayIndex];
            const modal = document.getElementById('chartModal');
            
            // Mettre à jour le titre
            document.getElementById('modalTitle').textContent = `Détails du ${dayData.formatted_date}`;
            
            // Mettre à jour les statistiques
            document.getElementById('modalTotalSales').textContent = dayData.total_sales.toLocaleString();
            document.getElementById('modalTotalRevenue').textContent = dayData.total_revenue.toLocaleString() + ' Ar';
            document.getElementById('modalActiveShops').textContent = dayData.shops.length;
            
            // Générer la liste des boutiques
            const shopsBreakdown = document.getElementById('shopsBreakdown');
            if (dayData.shops.length === 0) {
                shopsBreakdown.innerHTML = '<p style="text-align: center; color: #64748b; padding: 2rem;">Aucune vente ce jour-là</p>';
            } else {
                shopsBreakdown.innerHTML = dayData.shops.map(shop => `
                    <div class="breakdown-item">
                        <div class="breakdown-shop">${shop.name}</div>
                        <div class="breakdown-stats">
                            <span>${shop.sales} vente${shop.sales > 1 ? 's' : ''}</span>
                            <span class="breakdown-revenue">${shop.revenue.toLocaleString()} Ar</span>
                        </div>
                    </div>
                `).join('');
            }
            
            // Afficher la modal
            modal.style.display = 'block';
            
            // Stocker l'index du jour pour les actions
            modal.setAttribute('data-day-index', dayIndex);
        }

        // Fonctions pour les actions de la modal
        function exportDayData() {
            const dayIndex = document.getElementById('chartModal').getAttribute('data-day-index');
            const dayData = detailedData[dayIndex];
            
            // Créer les données CSV
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Boutique,Ventes,Chiffre d'Affaires\n";
            
            dayData.shops.forEach(shop => {
                csvContent += `"${shop.name}",${shop.sales},${shop.revenue}\n`;
            });
            
            // Télécharger le fichier
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `ventes_${dayData.date}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function viewShopDetails() {
            const dayIndex = document.getElementById('chartModal').getAttribute('data-day-index');
            const dayData = detailedData[dayIndex];
            
            // Rediriger vers la page des boutiques avec filtre de date
            window.location.href = `super_shops.php?date=${dayData.date}`;
        }

        // Démarrer l'animation après le chargement
        setTimeout(animateCounters, 500);

        // Graphique des revenus avec interactivité
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Chiffre d\'affaires (Ar)',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderColor: '#3b82f6',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    pointHoverBackgroundColor: '#1d4ed8',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            title: function(context) {
                                const dayData = detailedData[context[0].dataIndex];
                                return `📅 ${dayData.formatted_date}`;
                            },
                            label: function(context) {
                                const dayData = detailedData[context.dataIndex];
                                return [
                                    `💰 ${context.parsed.y.toLocaleString()} Ar`,
                                    `🛒 ${dayData.total_sales} vente${dayData.total_sales > 1 ? 's' : ''}`,
                                    `🏪 ${dayData.shops.length} boutique${dayData.shops.length > 1 ? 's' : ''} active${dayData.shops.length > 1 ? 's' : ''}`,
                                    '',
                                    '🖱️ Cliquez pour plus de détails'
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' Ar';
                            },
                            color: '#64748b'
                        }
                    },
                    x: {
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            color: '#64748b'
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                onHover: (event, activeElements) => {
                    event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                },
                onClick: (event, activeElements) => {
                    if (activeElements.length > 0) {
                        const dataIndex = activeElements[0].index;
                        showDayDetails(dataIndex);
                    }
                }
            }
        });

        // Gestion de la modal
        document.querySelector('.modal-close').addEventListener('click', function() {
            document.getElementById('chartModal').style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('chartModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Animation au scroll
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

        // Observer tous les éléments animés
        document.querySelectorAll('.shop-card, .activity-item').forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `all 0.6s ease-out ${index * 0.1}s`;
            observer.observe(el);
        });

        // Animation des liens de navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>
</html>
