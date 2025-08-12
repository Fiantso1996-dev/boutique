<?php
// inclus les fichiers de base
require_once 'includes/init.php';
require_once 'includes/barcode.php';

// Vérifier si l'utilisateur est connecté, sinon rediriger
requireLogin();

// Vérifier si l'ID de la vente est présent dans l'URL
if (!isset($_GET['id'])) {
    header('Location: sales.php');
    exit();
}

$shop = getCurrentShop();
$shop_pdo = $database->getShopConnection($shop['db']);

/**
 * Récupère un paramètre du système de la table system_settings.
 *
 * @param PDO $pdo L'objet PDO pour la connexion à la base de données.
 * @param string $key La clé du paramètre à récupérer.
 * @param string $default La valeur par défaut si le paramètre n'est pas trouvé.
 * @return string La valeur du paramètre ou la valeur par défaut.
 */
function getSetting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        // En cas d'erreur de base de données, on retourne la valeur par défaut
        return $default;
    }
}

// Récupérer les paramètres système depuis la base de données principale
$system_settings = [
    'currency_symbol'   => getSetting($main_pdo, 'currency_symbol', 'Ar'),
    'currency_position' => getSetting($main_pdo, 'currency_position', 'after'),
    'company_name'      => getSetting($main_pdo, 'company_name', 'DFM BUSINESS'),
    'company_address'   => getSetting($main_pdo, 'company_address', ''),
    'company_phone'     => getSetting($main_pdo, 'company_phone', ''),
    'company_email'     => getSetting($main_pdo, 'company_email', ''),
    'invoice_footer'    => getSetting($main_pdo, 'invoice_footer', 'Merci de votre visite - A TRÈS BIENTÔT'),
    'tax_rate'          => floatval(getSetting($main_pdo, 'tax_rate', '0'))
];

/**
 * Formate un montant en prix avec la devise.
 *
 * @param float $amount Le montant à formater.
 * @param string $symbol Le symbole de la devise.
 * @param string $position La position du symbole ('before' ou 'after').
 * @return string Le prix formaté.
 */
function formatPrice($amount, $symbol, $position) {
    $formatted_amount = number_format($amount, 2, ',', ' ');
    return $position === 'before' ? $symbol . ' ' . $formatted_amount : $formatted_amount . ' ' . $symbol;
}

// Récupérer les informations de la boutique
$stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
$stmt->execute([$shop['id']]);
$shop_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer la vente
$sale_id = intval($_GET['id']);
$stmt = $shop_pdo->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

// Si la vente n'existe pas, rediriger
if (!$sale) {
    header('Location: sales.php');
    exit();
}

// Récupérer les articles de la vente
$stmt = $shop_pdo->prepare("
    SELECT si.*, p.name as product_name 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les totaux avec taxe
$subtotal = $sale['total'];
$tax_amount = $subtotal * ($system_settings['tax_rate'] / 100);
$total_with_tax = $subtotal + $tax_amount;

// Générer un code-barres pour la facture
$invoice_barcode = BarcodeGenerator::generateEAN13();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture N° <?php echo htmlspecialchars($sale['invoice_number']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .invoice {
            width: 80mm;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            background: white;
            padding: 10px;
        }
        
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 10px;
            line-height: 1.3;
            margin-bottom: 5px;
        }
        
        .shop-info {
            font-size: 11px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }
        
        .invoice-info {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .invoice-table th {
            border-bottom: 1px solid #000;
            padding: 5px 2px;
            font-size: 10px;
            text-align: left;
        }
        
        .invoice-table td {
            padding: 3px 2px;
            font-size: 10px;
            border-bottom: 1px dotted #ccc;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-section {
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-bottom: 15px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }
        
        .total-line.final {
            font-weight: bold;
            font-size: 12px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .payment-info {
            background: #f5f5f5;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 11px;
        }
        
        .invoice-footer {
            text-align: center;
            border-top: 2px solid #000;
            padding-top: 15px;
            font-size: 11px;
        }
        
        .barcode-section {
            text-align: center;
            margin: 15px 0;
        }
        
        .thank-you {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .footer-message {
            font-style: italic;
            margin-bottom: 10px;
        }
        
        .qr-info {
            font-size: 9px;
            color: #666;
            margin-top: 10px;
        }
        
        @media print {
            body {
                margin: 0;
                background: white;
            }
            
            .no-print {
                display: none;
            }
            
            .invoice {
                width: auto;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimer</button>
        <a href="pos.php" class="btn btn-secondary">← Retour à la caisse</a>
        <a href="sales.php" class="btn btn-warning">📋 Voir les ventes</a>
    </div>
    
    <div class="invoice print-area">
        <div class="invoice-header">
            <div class="company-name">
                <?php echo htmlspecialchars($system_settings['company_name']); ?>
            </div>
            
            <?php if ($system_settings['company_address']): ?>
                <div class="company-info">
                    📍 <?php echo nl2br(htmlspecialchars($system_settings['company_address'])); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($system_settings['company_phone']): ?>
                <div class="company-info">
                    📞 <?php echo htmlspecialchars($system_settings['company_phone']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($system_settings['company_email']): ?>
                <div class="company-info">
                    ✉️ <?php echo htmlspecialchars($system_settings['company_email']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($shop_info): ?>
                <div class="shop-info">
                    <strong>Point de vente: <?php echo htmlspecialchars($shop_info['name']); ?></strong>
                    <?php if (!empty($shop_info['phone']) && $shop_info['phone'] !== $system_settings['company_phone']): ?>
                        <br>📞 <?php echo htmlspecialchars($shop_info['phone']); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="invoice-info">
            <div>
                <strong>FACTURE N° <?php echo htmlspecialchars($sale['invoice_number']); ?></strong><br>
                📅 <?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?>
            </div>
            <div class="text-right">
                <strong>TICKET DE CAISSE</strong><br>
                🛒 Vente au détail
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th class="text-center">Qté</th>
                    <th class="text-right">P.U.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td class="text-right">
                            <?php echo formatPrice($item['unit_price'], $system_settings['currency_symbol'], $system_settings['currency_position']); ?>
                        </td>
                        <td class="text-right">
                            <?php echo formatPrice($item['total_price'], $system_settings['currency_symbol'], $system_settings['currency_position']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals-section">
            <div class="total-line">
                <span>Sous-total:</span>
                <span><?php echo formatPrice($subtotal, $system_settings['currency_symbol'], $system_settings['currency_position']); ?></span>
            </div>
            
            <?php if ($system_settings['tax_rate'] > 0): ?>
                <div class="total-line">
                    <span>TVA (<?php echo htmlspecialchars($system_settings['tax_rate']); ?>%):</span>
                    <span><?php echo formatPrice($tax_amount, $system_settings['currency_symbol'], $system_settings['currency_position']); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="total-line final">
                <span>TOTAL:</span>
                <span><?php echo formatPrice($total_with_tax, $system_settings['currency_symbol'], $system_settings['currency_position']); ?></span>
            </div>
        </div>
        
        <div class="payment-info">
            <div class="total-line">
                <span>💰 Montant reçu:</span>
                <span><?php echo formatPrice($sale['payment_received'], $system_settings['currency_symbol'], $system_settings['currency_position']); ?></span>
            </div>
            <div class="total-line">
                <span>💸 Monnaie rendue:</span>
                <span><?php echo formatPrice($sale['change_amount'], $system_settings['currency_symbol'], $system_settings['currency_position']); ?></span>
            </div>
        </div>
        
        <div class="barcode-section">
            <?php
            // Générer l'image du code-barres en Base64 pour l'intégration directe
            ob_start();
            $image = BarcodeGenerator::generateBarcodeImage($invoice_barcode, 150, 40);
            imagepng($image);
            $imageData = ob_get_clean();
            imagedestroy($image);
            
            $base64 = base64_encode($imageData);
            ?>
            <img src="data:image/png;base64,<?php echo htmlspecialchars($base64); ?>" alt="Code-barres facture" style="max-width: 100%;">
            <div class="qr-info">Code: <?php echo htmlspecialchars($invoice_barcode); ?></div>
        </div>
        
        <div class="invoice-footer">
            <div class="thank-you">
                <?php echo htmlspecialchars($system_settings['invoice_footer']); ?>
            </div>
            
            <div class="footer-message">
                🌟Votre satisfaction est notre priorité🌟
            </div>
            
            <div style="font-size: 9px; margin-top: 10px;">
                Facture générée le <?php echo date('d/m/Y à H:i'); ?><br>
                Système de gestion AKAH FIANTSO
            </div>
        </div>
    </div>
</body>
</html>