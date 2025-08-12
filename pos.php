<?php
require_once 'includes/init.php';
requireLogin(); // Accessible aux caissiers et admins

$shop = getCurrentShop();
$user = getCurrentUser();
$shop_pdo = $database->getShopConnection($shop['db']);

// Récupérer tous les produits
$stmt = $shop_pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point de vente - <?php echo htmlspecialchars($shop['name']); ?></title>
    
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
            max-width: 1400px;
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
            max-width: 1400px;
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

        /* Conteneur principal du PDV */
        .pos-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            min-height: calc(100vh - 120px);
        }
        
        @media (max-width: 1024px) {
            .pos-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Grille des produits */
        .pos-products {
            background-color: var(--card-bg-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .product-item {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            user-select: none;
        }
        
        .product-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success-color);
            margin-top: 0.5rem;
        }
        
        .product-stock {
            color: var(--light-text-color);
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        
        /* Panier */
        .pos-cart {
            background-color: var(--card-bg-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
        }
        
        .cart-header {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .cart-items {
            flex-grow: 1;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            padding-right: 10px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-details {
            display: flex;
            flex-direction: column;
        }
        
        .item-name {
            font-weight: 600;
        }
        
        .item-price {
            color: var(--light-text-color);
            font-size: 0.9rem;
        }

        .item-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .item-total {
            font-weight: 700;
            min-width: 90px;
            text-align: right;
        }
        
        .quantity-btn {
            background: #f1f1f1;
            color: var(--secondary-color);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .quantity-btn:hover {
            background-color: #e0e0e0;
        }

        /* Totaux et paiement */
        .cart-total-box {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding: 1rem 0;
            border-top: 2px solid var(--secondary-color);
            text-align: right;
            color: var(--secondary-color);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }
        
        .change-box {
            font-size: 1.2rem;
            font-weight: 700;
            padding: 1rem 0;
            text-align: right;
        }

        /* Boutons */
        .btn-group {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
            white-space: nowrap;
            border: none;
            cursor: pointer;
            flex: 1;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            transform: scale(1.02);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            flex: unset;
            padding: 0.75rem;
            border-radius: 50%;
            width: 50px;
            height: 50px;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><?php echo htmlspecialchars($shop['name']); ?></a>
            <ul class="navbar-nav">
                <?php if (isAdmin()): ?>
                    <li><a href="dashboard.php" class="nav-link">Tableau de bord</a></li>
                    <li><a href="products.php" class="nav-link">Produits</a></li>
                <?php endif; ?>
                <li><a href="pos.php" class="nav-link">Caisse</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="sales.php" class="nav-link">Ventes</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="nav-link">Déconnexion (<?php echo htmlspecialchars($user['username']); ?>)</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="pos-container">
            <div class="pos-products">
                <div class="form-group">
                    <input type="text" id="barcode-input" placeholder="Scanner ou saisir le code-barres" class="form-control">
                </div>
                
                <div class="product-grid">
                    <?php if (empty($products)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; color: var(--light-text-color);">
                            Aucun produit en stock disponible.
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-item" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-price">
                                    <?php echo number_format($product['price'], 0, ',', ' '); ?> Ar
                                </div>
                                <div class="product-stock">
                                    Stock: <?php echo $product['stock']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="pos-cart">
                <div class="cart-header">Panier</div>
                
                <div class="cart-items" id="cart-items">
                    <div style="text-align: center; color: var(--light-text-color); padding: 50px 0;">
                        Le panier est vide
                    </div>
                </div>
                
                <div class="cart-total-box" id="cart-total">
                    Total: 0 Ar
                </div>
                
                <div class="form-group">
                    <label for="payment-amount" class="form-label">Montant reçu</label>
                    <input type="number" id="payment-amount" class="form-control" placeholder="0">
                </div>
                
                <div class="change-box">
                    Monnaie à rendre: <span id="change-amount">0 Ar</span>
                </div>
                
                <div class="btn-group">
                    <button class="btn btn-success" onclick="processPayment()">
                        <i class="fas fa-check"></i> Valider
                    </button>
                    <button class="btn btn-danger" onclick="clearCart()">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let cart = [];
        let products = <?php echo json_encode($products); ?>;
        
        // Focus sur le champ code-barres
        document.getElementById('barcode-input').focus();
        
        // Écouter la saisie du code-barres
        document.getElementById('barcode-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const barcode = this.value.trim();
                if (barcode) {
                    const product = products.find(p => p.barcode === barcode);
                    if (product) {
                        addToCart(product);
                        this.value = '';
                    } else {
                        alert('Produit non trouvé');
                    }
                }
            }
        });
        
        // Calculer la monnaie
        document.getElementById('payment-amount').addEventListener('input', function() {
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const payment = parseFloat(this.value) || 0;
            const change = payment - total;
            document.getElementById('change-amount').textContent = 
                change >= 0 ? formatPrice(change) + ' Ar' : '0 Ar';
        });
        
        function addToCart(product) {
            const existingItem = cart.find(item => item.id === product.id);
            
            if (existingItem) {
                if (existingItem.quantity < product.stock) {
                    existingItem.quantity++;
                } else {
                    alert('Stock insuffisant');
                    return;
                }
            } else {
                cart.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1,
                    stock: parseInt(product.stock)
                });
            }
            
            updateCartDisplay();
        }
        
        function removeFromCart(productId) {
            cart = cart.filter(item => item.id !== productId);
            updateCartDisplay();
        }
        
        function updateQuantity(productId, newQuantity) {
            const item = cart.find(item => item.id === productId);
            if (item) {
                if (newQuantity <= 0) {
                    removeFromCart(productId);
                } else if (newQuantity <= item.stock) {
                    item.quantity = newQuantity;
                    updateCartDisplay();
                } else {
                    alert('Stock insuffisant');
                }
            }
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<div style="text-align: center; color: var(--light-text-color); padding: 50px 0;">Le panier est vide</div>';
                cartTotal.textContent = 'Total: 0 Ar';
                return;
            }
            
            let html = '';
            let total = 0;
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                html += `
                    <div class="cart-item">
                        <div class="item-details">
                            <div class="item-name">${item.name}</div>
                            <div class="item-price">${formatPrice(item.price)} Ar × ${item.quantity}</div>
                        </div>
                        <div class="item-controls">
                            <button onclick="updateQuantity(${item.id}, ${item.quantity - 1})" class="quantity-btn"><i class="fas fa-minus"></i></button>
                            <span style="min-width: 25px; text-align: center;">${item.quantity}</span>
                            <button onclick="updateQuantity(${item.id}, ${item.quantity + 1})" class="quantity-btn"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="item-total">
                            ${formatPrice(itemTotal)} Ar
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            cartTotal.textContent = `Total: ${formatPrice(total)} Ar`;
        }
        
        function clearCart() {
            cart = [];
            updateCartDisplay();
            document.getElementById('payment-amount').value = '';
            document.getElementById('change-amount').textContent = '0 Ar';
        }
        
        function processPayment() {
            if (cart.length === 0) {
                alert('Le panier est vide');
                return;
            }
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const payment = parseFloat(document.getElementById('payment-amount').value) || 0;
            
            if (payment < total) {
                alert('Montant insuffisant');
                return;
            }
            
            // Envoyer la vente au serveur
            fetch('process_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    items: cart,
                    total: total,
                    payment: payment,
                    change: payment - total
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vente enregistrée avec succès!');
                    // Ouvrir la facture dans une nouvelle fenêtre
                    window.open('invoice.php?id=' + data.sale_id, '_blank');
                    clearCart();
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'enregistrement de la vente');
            });
        }
        
        function formatPrice(price) {
            return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }
    </script>
</body>
</html>