<?php
// Fichier: barcode.php

require_once 'includes/init.php';
require_once 'includes/barcode.php'; // Assurez-vous que votre classe BarcodeGenerator est ici

requireLogin();
requireAdmin();

$shop = getCurrentShop();
$user = getCurrentUser();

if (!$database->databaseExists($shop['db'])) {
    die('Erreur: Base de données de la boutique non trouvée');
}

$shop_pdo = $database->getShopConnection($shop['db']);

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$id = intval($_GET['id']);
$stmt = $shop_pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Vérifier si le code-barres est valide (ex. EAN-13, 13 chiffres)
$barcode_value = trim($product['barcode']);
$is_ean13 = (strlen($barcode_value) == 13 && is_numeric($barcode_value));

// -- GESTION DU TÉLÉCHARGEMENT --
if (isset($_GET['download'])) {
    if (!$is_ean13) {
        die('Erreur: Le code-barres du produit n\'est pas un EAN-13 valide.');
    }
    
    // Générer l'image du code-barres directement dans la mémoire tampon de sortie
    ob_start();
    // Assurez-vous que cette fonction gère l'EAN-13
    $image = BarcodeGenerator::generateBarcodeImage($barcode_value);
    imagepng($image);
    $imageData = ob_get_clean();
    imagedestroy($image);
    
    $filename = 'barcode_ean13_' . $barcode_value . '.png';
    
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($imageData));
    
    echo $imageData;
    exit();
}
// -- FIN GESTION DU TÉLÉCHARGEMENT --
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code-barres EAN-13 - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS du fichier original, inchangé */
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
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
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
        .card {
            background-color: var(--card-bg-color);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
        }
        .card-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 1rem;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .barcode-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }
        .barcode-image {
            max-width: 100%;
            height: auto;
            background-color: white;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        .product-info {
            background-color: #f8f9fa;
            border: 1px dashed #e0e0e0;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 400px;
            margin-top: 1rem;
        }
        .product-info p {
            margin: 0;
            font-size: 1rem;
        }
        .product-info p strong {
            color: var(--secondary-color);
            min-width: 100px;
            display: inline-block;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
            white-space: nowrap;
            border: none;
            cursor: pointer;
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
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        @media print {
            body { background: none; }
            .navbar, .btn-group { display: none; }
            .container { margin: 0; padding: 0; max-width: 100%; }
            .card { box-shadow: none; border: none; padding: 1rem; }
            .card-header { border-bottom: none; margin-bottom: 1rem; justify-content: flex-start; }
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
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-barcode"></i> Code-barres EAN-13 du produit</h2>
            </div>
            
            <div class="barcode-container">
                <?php if (!$is_ean13): ?>
                    <p class="error-message">Erreur : Le code-barres n'est pas un EAN-13 valide (doit être un nombre de 13 chiffres).</p>
                <?php else: ?>
                    <?php
                    // Générer l'image en base64 pour l'affichage
                    ob_start();
                    $image = BarcodeGenerator::generateBarcodeImage($barcode_value);
                    imagepng($image);
                    $imageData = ob_get_clean();
                    imagedestroy($image);
                    
                    $base64 = base64_encode($imageData);
                    ?>
                    
                    <img src="data:image/png;base64,<?php echo $base64; ?>" alt="Code-barres EAN-13" class="barcode-image">
                <?php endif; ?>
                
                <div class="product-info">
                    <p><strong>Code-barres:</strong> <?php echo htmlspecialchars($product['barcode']); ?></p>
                    <p><strong>Produit:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
                    <p><strong>Prix:</strong> <?php echo number_format($product['price'], 0, ',', ' '); ?> Ar</p>
                </div>
                
                <div class="btn-group">
                    <?php if ($is_ean13): ?>
                        <a href="barcode.php?id=<?php echo $product['id']; ?>&download=1" class="btn btn-primary">
                            <i class="fas fa-download"></i> Télécharger
                        </a>
                    <?php endif; ?>
                    <!-- <a href="#" class="btn btn-primary" onclick="window.print(); return false;">
                        <i class="fas fa-print"></i> Imprimer
                    </a> -->
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>