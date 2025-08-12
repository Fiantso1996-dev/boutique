<?php
require_once 'includes/init.php';

$error = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (loginSuperAdmin($username, $password, $main_pdo)) {
        header('Location: super_dashboard.php');
        exit();
    } else {
        $error = 'Nom d\'utilisateur ou mot de passe incorrect';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Connexion</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .super-login {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            position: relative;
        }
        
        .super-login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: slideInUp 0.8s ease-out;
        }
        
        .super-title {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .floating-icons {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .icon {
            position: absolute;
            color: rgba(255, 255, 255, 0.1);
            font-size: 24px;
            animation: floatIcon 10s linear infinite;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes floatIcon {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        .btn-super {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-super::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-super:hover::before {
            left: 100%;
        }
        
        .form-control:focus {
            box-shadow: 0 0 20px rgba(30, 60, 114, 0.3);
            transform: scale(1.02);
        }
    </style>
</head>
<body class="super-login">
    <div class="floating-icons" id="icons"></div>
    
    <div class="login-container">
        <div class="card login-card super-login-card">
            <div class="card-header">
                <h1 class="super-title">🏢 Fiantso</h1>
                <p style="text-align: center; color: #666;">Administration Générale</p>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #e74c3c; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; animation: shake 0.5s;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="superLoginForm">
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-super" style="width: 100%; margin-bottom: 15px;">
                    <span class="btn-text">Accéder au Super Dashboard</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="super_register.php" class="btn btn-success" style="margin-bottom: 10px;">Créer un compte Super Admin</a>
                <br>
                <a href="login.php" style="color: #666; text-decoration: none;">← Retour aux boutiques</a>
            </div>
        </div>
    </div>
    
    <script>
        // Créer des icônes flottantes
        function createFloatingIcons() {
            const container = document.getElementById('icons');
            const icons = ['🏪', '📊', '💰', '📈', '🛒', '📋', '⚙️', '👥'];
            
            setInterval(() => {
                const icon = document.createElement('div');
                icon.className = 'icon';
                icon.textContent = icons[Math.floor(Math.random() * icons.length)];
                icon.style.left = Math.random() * 100 + '%';
                icon.style.animationDuration = Math.random() * 5 + 8 + 's';
                
                container.appendChild(icon);
                
                setTimeout(() => {
                    icon.remove();
                }, 13000);
            }, 500);
        }
        
        createFloatingIcons();
        
        // Animation du formulaire
        document.getElementById('superLoginForm').addEventListener('submit', function(e) {
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
                this.style.transform = 'scale(1.02)';
                this.style.transition = 'all 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });
        
        // Animation shake pour les erreurs
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
