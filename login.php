<?php
require_once 'includes/init.php';

$error = '';

if ($_POST) {
    $shop_name = trim($_POST['shop_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Vérifier si la boutique existe
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE name = ?");
    $stmt->execute([$shop_name]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        // Vérifier l'utilisateur
        $stmt = $main_pdo->prepare("SELECT * FROM users WHERE shop_id = ? AND username = ?");
        $stmt->execute([$shop['id'], $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['shop_id'] = $shop['id'];
            $_SESSION['shop_name'] = $shop['name'];
            $_SESSION['shop_db'] = $shop['database_name'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            require_once 'includes/logger.php';
            $logger = new ActivityLogger($main_pdo);
            $logger->log($shop['id'], $user['id'], 'Connexion utilisateur', "Utilisateur: $username ({$user['role']})");
            
            if ($user['role'] === 'admin') {
                header('Location: dashboard.php');
            } else {
                header('Location: pos.php');
            }
            exit();
        } else {
            $error = 'Nom d\'utilisateur ou mot de passe incorrect';
        }
    } else {
        $error = 'Boutique non trouvée';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion de Caisse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-animation {
            animation: slideInUp 0.6s ease-out;
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
        
        .form-control:focus {
            transform: scale(1.02);
            transition: transform 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="login-container">
        <div class="card login-card login-animation">
            <div class="card-header">
                <h1 class="card-title">🏪 Gestion de Caisse</h1>
                <p style="text-align: center; color: #666; margin-top: 10px;">Connexion Boutique</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message" style="background: #e74c3c; color: white; padding: 10px; border-radius: 4px; margin-bottom: 20px; animation: shake 0.5s;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label">Nom de la boutique</label>
                    <input type="text" name="shop_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 15px;">
                    <span class="btn-text">Se connecter</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <p style="color: #666; margin-bottom: 10px;">Vous êtes administrateur système ?</p>
                <a href="super_login.php" class="btn btn-secondary">Accéder au Super Dashboard</a>
            </div>
        </div>
    </div>
    
    <script>
        // Animation du formulaire
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            const btnText = btn.querySelector('.btn-text');
            const btnLoader = btn.querySelector('.btn-loader');
            
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline';
            btn.disabled = true;
        });
        
        // Animation des champs
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Animation d'erreur
        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.style.animation = 'shake 0.5s';
        }
        
        // Keyframes pour shake
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
