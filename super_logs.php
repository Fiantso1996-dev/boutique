<?php
require_once 'includes/init.php';
require_once 'config/super_admin.php';
require_once 'includes/logger.php';
requireSuperAdmin();

$logger = new ActivityLogger($main_pdo);

// Récupérer tous les logs
$logs = $logger->getRecentLogs(100);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'activités - Super Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .super-navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }
        
        .super-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
        }
        
        .super-navbar .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .super-navbar .nav-link:hover {
            color: white !important;
        }
        
        .log-entry {
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .log-action {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .log-meta {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .log-details {
            color: #34495e;
            font-size: 13px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar super-navbar">
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
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📋 Journal d'activités système</h2>
                <p style="color: #666; margin: 10px 0 0 0;">Historique complet des activités de toutes les boutiques</p>
            </div>
            
            <div style="padding: 20px;">
                <?php if (empty($logs)): ?>
                    <div style="text-align: center; padding: 50px; color: #7f8c8d;">
                        Aucune activité enregistrée
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-entry">
                            <div class="log-action">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </div>
                            
                            <div class="log-meta">
                                <strong>Boutique:</strong> <?php echo htmlspecialchars($log['shop_name'] ?? 'Système'); ?>
                                <?php if ($log['username']): ?>
                                    | <strong>Utilisateur:</strong> <?php echo htmlspecialchars($log['username']); ?>
                                <?php endif; ?>
                                | <strong>Date:</strong> <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                | <strong>IP:</strong> <?php echo htmlspecialchars($log['ip_address']); ?>
                            </div>
                            
                            <?php if ($log['details']): ?>
                                <div class="log-details">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
