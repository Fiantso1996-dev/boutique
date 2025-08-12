<?php
require_once 'includes/init.php';
require_once 'config/super_admin.php';
require_once 'includes/logger.php';
requireSuperAdmin();

$logger = new ActivityLogger($main_pdo);
$message = '';
$error = '';

// Créer la table des paramètres système
$main_pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insérer les paramètres par défaut
$default_settings = [
    'currency_code' => 'FCFA',
    'currency_symbol' => 'FCFA',
    'currency_position' => 'after', // before ou after
    'company_name' => 'DFM BUSINESS',
    'company_address' => '',
    'company_phone' => '',
    'company_email' => '',
    'invoice_footer' => 'Merci de votre visite - A TRÈS BIENTÔT',
    'tax_rate' => '0'
];

foreach ($default_settings as $key => $value) {
    $stmt = $main_pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute([$key, $value]);
}

// Fonction pour récupérer un paramètre
function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

// Fonction pour sauvegarder un paramètre
function setSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$key, $value, $value]);
}

// Traitement des formulaires
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        try {
            // Vérifier le mot de passe actuel
            $stmt = $main_pdo->prepare("SELECT * FROM super_admins WHERE id = ?");
            $stmt->execute([$_SESSION['super_admin_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $admin['password'])) {
                $error = 'Mot de passe actuel incorrect';
            } else {
                $update_password = false;
                $password_hash = $admin['password'];
                
                // Si nouveau mot de passe fourni
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Les mots de passe ne correspondent pas';
                    } else {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_password = true;
                    }
                }
                
                if (empty($error)) {
                    $stmt = $main_pdo->prepare("UPDATE super_admins SET full_name = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $password_hash, $_SESSION['super_admin_id']]);
                    
                    $logger->log(null, null, 'Profil super admin modifié', $update_password ? 'Profil et mot de passe mis à jour' : 'Profil mis à jour');
                    $message = 'Profil mis à jour avec succès';
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la mise à jour: ' . $e->getMessage();
        }
    }
    
    elseif ($action === 'update_currency') {
        $currency_code = trim($_POST['currency_code']);
        $currency_symbol = trim($_POST['currency_symbol']);
        $currency_position = $_POST['currency_position'];
        
        setSetting($main_pdo, 'currency_code', $currency_code);
        setSetting($main_pdo, 'currency_symbol', $currency_symbol);
        setSetting($main_pdo, 'currency_position', $currency_position);
        
        $logger->log(null, null, 'Devise système modifiée', "Nouvelle devise: $currency_code ($currency_symbol)");
        $message = 'Paramètres de devise mis à jour';
    }
    
    elseif ($action === 'update_company') {
        $company_name = trim($_POST['company_name']);
        $company_address = trim($_POST['company_address']);
        $company_phone = trim($_POST['company_phone']);
        $company_email = trim($_POST['company_email']);
        $invoice_footer = trim($_POST['invoice_footer']);
        $tax_rate = floatval($_POST['tax_rate']);
        
        setSetting($main_pdo, 'company_name', $company_name);
        setSetting($main_pdo, 'company_address', $company_address);
        setSetting($main_pdo, 'company_phone', $company_phone);
        setSetting($main_pdo, 'company_email', $company_email);
        setSetting($main_pdo, 'invoice_footer', $invoice_footer);
        setSetting($main_pdo, 'tax_rate', $tax_rate);
        
        $logger->log(null, null, 'Paramètres entreprise modifiés', "Entreprise: $company_name");
        $message = 'Paramètres de l\'entreprise mis à jour';
    }
}

// Récupérer les informations du super admin
$stmt = $main_pdo->prepare("SELECT * FROM super_admins WHERE id = ?");
$stmt->execute([$_SESSION['super_admin_id']]);
$admin_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les paramètres actuels
$settings = [
    'currency_code' => getSetting($main_pdo, 'currency_code', 'FCFA'),
    'currency_symbol' => getSetting($main_pdo, 'currency_symbol', 'FCFA'),
    'currency_position' => getSetting($main_pdo, 'currency_position', 'after'),
    'company_name' => getSetting($main_pdo, 'company_name', 'DFM BUSINESS'),
    'company_address' => getSetting($main_pdo, 'company_address'),
    'company_phone' => getSetting($main_pdo, 'company_phone'),
    'company_email' => getSetting($main_pdo, 'company_email'),
    'invoice_footer' => getSetting($main_pdo, 'invoice_footer', 'Merci de votre visite - A TRÈS BIENTÔT'),
    'tax_rate' => getSetting($main_pdo, 'tax_rate', '0')
];

// Devises prédéfinies
$currencies = [
    ['code' => 'FCFA', 'symbol' => 'FCFA', 'name' => 'Franc CFA'],
    ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro'],
    ['code' => 'USD', 'symbol' => '$', 'name' => 'Dollar US'],
    ['code' => 'GBP', 'symbol' => '£', 'name' => 'Livre Sterling'],
    ['code' => 'MGA', 'symbol' => 'Ar', 'name' => 'Ariary Malgache'],
    ['code' => 'JPY', 'symbol' => '¥', 'name' => 'Yen Japonais']
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Système - Super Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
        
        .page-header {
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
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            animation: fadeInUp 0.6s ease-out;
            transition: all 0.3s ease;
        }
        
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideInDown 0.5s ease-out;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .currency-preview {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
        }
        
        .preview-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .preview-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .info-box-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        .info-box-text {
            color: #1e40af;
            font-size: 0.875rem;
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
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <a href="super_dashboard.php" class="brand-title">
                    🏢 Fiantso
                </a>
                <div class="brand-subtitle" style="margin-left: 50px;">Gestion Caisse</div>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="super_dashboard.php" class="nav-link">
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
                    <a href="super_settings.php" class="nav-link active">
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
            <div class="page-header">
                <h1 class="page-title">⚙️ Paramètres Système</h1>
                <p class="page-subtitle">Configuration générale du système</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="settings-grid">
                <!-- Profil Super Admin -->
                <div class="settings-card" style="animation-delay: 0.1s;">
                    <div class="card-header">
                        <div class="card-icon">👤</div>
                        <h3 class="card-title">Profil Administrateur</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($admin_info['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_info['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nouveau mot de passe (optionnel)</label>
                            <input type="password" name="new_password" class="form-control" minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            💾 Mettre à jour le profil
                        </button>
                    </form>
                </div>
                
                <!-- Paramètres de devise -->
                <div class="settings-card" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <div class="card-icon">💰</div>
                        <h3 class="card-title">Devise du Système</h3>
                    </div>
                    
                    <form method="POST" id="currencyForm">
                        <input type="hidden" name="action" value="update_currency">
                        
                        <div class="form-group">
                            <label class="form-label">Devise prédéfinie</label>
                            <select class="form-control form-select" id="predefinedCurrency" onchange="updateCurrencyFields()">
                                <option value="">Choisir une devise...</option>
                                <?php foreach ($currencies as $currency): ?>
                                    <option value="<?php echo $currency['code']; ?>" 
                                            data-symbol="<?php echo $currency['symbol']; ?>"
                                            <?php echo $settings['currency_code'] === $currency['code'] ? 'selected' : ''; ?>>
                                        <?php echo $currency['name']; ?> (<?php echo $currency['symbol']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Code devise</label>
                            <input type="text" name="currency_code" id="currencyCode" class="form-control" value="<?php echo htmlspecialchars($settings['currency_code']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Symbole</label>
                            <input type="text" name="currency_symbol" id="currencySymbol" class="form-control" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Position du symbole</label>
                            <select name="currency_position" class="form-control form-select" id="currencyPosition">
                                <option value="before" <?php echo $settings['currency_position'] === 'before' ? 'selected' : ''; ?>>Avant le montant</option>
                                <option value="after" <?php echo $settings['currency_position'] === 'after' ? 'selected' : ''; ?>>Après le montant</option>
                            </select>
                        </div>
                        
                        <div class="currency-preview" id="currencyPreview">
                            <div class="preview-amount">1,234.56</div>
                            <div class="preview-label">Aperçu du format</div>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="margin-top: 1rem;">
                            💰 Mettre à jour la devise
                        </button>
                    </form>
                </div>
                
                <!-- Paramètres de l'entreprise -->
                <div class="settings-card" style="animation-delay: 0.3s; grid-column: 1 / -1;">
                    <div class="card-header">
                        <div class="card-icon">🏢</div>
                        <h3 class="card-title">Informations de l'Entreprise</h3>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_company">
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                            <div class="form-group">
                                <label class="form-label">Nom de l'entreprise</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="company_phone" class="form-control" value="<?php echo htmlspecialchars($settings['company_phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="company_email" class="form-control" value="<?php echo htmlspecialchars($settings['company_email']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Taux de taxe (%)</label>
                                <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Adresse complète</label>
                            <textarea name="company_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['company_address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Message de fin de facture</label>
                            <textarea name="invoice_footer" class="form-control" rows="2"><?php echo htmlspecialchars($settings['invoice_footer']); ?></textarea>
                        </div>
                        
                        <div class="info-box">
                            <div class="info-box-title">ℹ️ Information</div>
                            <div class="info-box-text">
                                Ces informations apparaîtront sur toutes les factures générées par le système.
                                Le taux de taxe sera appliqué automatiquement aux ventes si défini.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="margin-top: 1rem;">
                            🏢 Mettre à jour les informations
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateCurrencyFields() {
            const select = document.getElementById('predefinedCurrency');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('currencyCode').value = option.value;
                document.getElementById('currencySymbol').value = option.dataset.symbol;
                updateCurrencyPreview();
            }
        }
        
        function updateCurrencyPreview() {
            const code = document.getElementById('currencyCode').value;
            const symbol = document.getElementById('currencySymbol').value;
            const position = document.getElementById('currencyPosition').value;
            const amount = '1,234.56';
            
            const preview = document.getElementById('currencyPreview').querySelector('.preview-amount');
            
            if (position === 'before') {
                preview.textContent = symbol + ' ' + amount;
            } else {
                preview.textContent = amount + ' ' + symbol;
            }
        }
        
        // Mettre à jour l'aperçu en temps réel
        document.getElementById('currencySymbol').addEventListener('input', updateCurrencyPreview);
        document.getElementById('currencyPosition').addEventListener('change', updateCurrencyPreview);
        
        // Initialiser l'aperçu
        updateCurrencyPreview();
        
        // Animation des cartes
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
        
        document.querySelectorAll('.settings-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `all 0.6s ease-out ${index * 0.1}s`;
            observer.observe(card);
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
