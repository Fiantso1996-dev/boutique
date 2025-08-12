<?php
require_once 'includes/init.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    // Afficher une page d'accueil avec choix
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Système de Gestion de Caisse</title>
        <link rel="stylesheet" href="assets/css/style.css">
        <style>
            .welcome-container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                position: relative;
                overflow: hidden;
            }
            
            .welcome-card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(15px);
                border-radius: 20px;
                padding: 50px;
                text-align: center;
                max-width: 600px;
                box-shadow: 0 25px 50px rgba(0,0,0,0.15);
                animation: slideInUp 1s ease-out;
                position: relative;
                z-index: 2;
            }
            
            .welcome-title {
                font-size: 36px;
                font-weight: 700;
                color: #2c3e50;
                margin-bottom: 20px;
                animation: fadeInDown 1s ease-out 0.3s both;
            }
            
            .welcome-subtitle {
                color: #7f8c8d;
                margin-bottom: 50px;
                font-size: 18px;
                animation: fadeInUp 1s ease-out 0.6s both;
            }
            
            .access-buttons {
                display: flex;
                flex-direction: column;
                gap: 20px;
                animation: fadeInUp 1s ease-out 0.9s both;
            }
            
            .access-btn {
                padding: 20px 40px;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            
            .access-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                transition: left 0.5s;
            }
            
            .access-btn:hover::before {
                left: 100%;
            }
            
            .access-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            }
            
            .btn-shop {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
            }
            
            .btn-super {
                background: linear-gradient(135deg, #1e3c72, #2a5298);
                color: white;
            }
            
            .floating-elements {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1;
            }
            
            .floating-element {
                position: absolute;
                color: rgba(255, 255, 255, 0.1);
                font-size: 24px;
                animation: float 8s ease-in-out infinite;
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
            
            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
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
        <div class="welcome-container">
            <div class="floating-elements" id="floatingElements"></div>
            
            <div class="welcome-card">
                <h1 class="welcome-title">🏪 Système de Gestion de Caisse</h1>
                <p class="welcome-subtitle">
                    Plateforme complète pour la gestion de vos points de vente
                </p>
                
                <div class="access-buttons">
                    <a href="login.php" class="access-btn btn-shop">
                        🏬 Accès Boutique
                        <div style="font-size: 16px; font-weight: normal; margin-top: 8px;">
                            Connexion en tant qu'administrateur ou caissier
                        </div>
                    </a>
                    
                    <a href="super_login.php" class="access-btn btn-super">
                        🏢 Super Dashboard
                        <div style="font-size: 16px; font-weight: normal; margin-top: 8px;">
                            Administration générale de toutes les boutiques
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <script>
            // Créer des éléments flottants
            function createFloatingElements() {
                const container = document.getElementById('floatingElements');
                const icons = ['💰', '📊', '🛒', '📈', '⚙️', '👥', '📋', '🏪'];
                
                for (let i = 0; i < 15; i++) {
                    const element = document.createElement('div');
                    element.className = 'floating-element';
                    element.textContent = icons[Math.floor(Math.random() * icons.length)];
                    element.style.left = Math.random() * 100 + '%';
                    element.style.top = Math.random() * 100 + '%';
                    element.style.animationDelay = Math.random() * 8 + 's';
                    element.style.animationDuration = (Math.random() * 4 + 6) + 's';
                    
                    container.appendChild(element);
                }
            }
            
            createFloatingElements();
            
            // Animation des boutons au survol
            document.querySelectorAll('.access-btn').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px) scale(1.02)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        </script>
    </body>
    </html>
    <?php
}
exit();
?>
