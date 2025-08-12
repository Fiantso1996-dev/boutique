<?php
require_once 'includes/init.php';

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    
    if (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        try {
            // Vérifier si l'utilisateur existe déjà
            $stmt = $main_pdo->prepare("SELECT id FROM super_admins WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Nom d\'utilisateur ou email déjà utilisé';
            } else {
                // Créer le super admin
                $stmt = $main_pdo->prepare("INSERT INTO super_admins (username, password, email, full_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email, $full_name]);
                
                $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la création du compte: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Super Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .super-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: slideInDown 0.8s ease-out;
        }
        
        .super-title {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .floating-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: floatParticle 8s linear infinite;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes floatParticle {
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
        
        .form-group {
            position: relative;
            overflow: hidden;
        }
        
        .form-control:focus {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-register:hover::before {
            left: 100%;
        }
        
        .success-message {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: bounceIn 0.6s ease-out;
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="super-register">
    <div class="floating-particles" id="particles"></div>
    
    <div class="login-container">
        <div class="card login-card register-card">
            <div class="card-header">
                <h1 class="super-title">🚀 Inscription Super Admin</h1>
                <p style="text-align: center; color: #666;">Créer un compte administrateur système</p>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #e74c3c; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; animation: shake 0.5s;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label class="form-label">Nom complet</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mot de passe (min. 8 caractères)</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register" style="width: 100%; margin-bottom: 15px;">
                    <span class="btn-text">Créer le compte</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
            </form>
            
            <div style="text-align: center;">
                <a href="super_login.php" style="color: #666; text-decoration: none;">← Retour à la connexion</a>
            </div>
        </div>
    </div>
    
    <script>
        // Créer des particules flottantes
        function createParticles() {
            const container = document.getElementById('particles');
            
            setInterval(() => {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.width = particle.style.height = Math.random() * 10 + 5 + 'px';
                particle.style.animationDuration = Math.random() * 3 + 5 + 's';
                
                container.appendChild(particle);
                
                setTimeout(() => {
                    particle.remove();
                }, 8000);
            }, 300);
        }
        
        createParticles();
        
        // Animation du formulaire
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            const btnText = btn.querySelector('.btn-text');
            const btnLoader = btn.querySelector('.btn-loader');
            
            btnText.style.display = 'none';
            btnLoader.style.display = 'inline';
            btn.disabled = true;
        });
        
        // Validation en temps réel
        const password = document.querySelector('input[name="password"]');
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#27ae60';
            }
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
    </script>
</body>
</html>
