<?php
// ... votre code PHP existant ...

require_once 'includes/init.php';
requireLogin();
requireAdmin();

$shop = getCurrentShop();
$user = getCurrentUser();
$shop_pdo = $database->getShopConnection($shop['db']);

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$product_name = $_GET['product_name'] ?? '';
$barcode = $_GET['barcode'] ?? '';

$sql = "SELECT * FROM sales";
$conditions = [];
$params = [];

if (!empty($start_date)) {
    $conditions[] = "created_at >= :start_date";
    $params[':start_date'] = $start_date . ' 00:00:00';
}
if (!empty($end_date)) {
    $conditions[] = "created_at <= :end_date";
    $params[':end_date'] = $end_date . ' 23:59:59';
}

if (!empty($product_name) || !empty($barcode)) {
    $subquery = "SELECT sales_id FROM sales_items WHERE 1=1";
    $subquery_params = [];
    
    if (!empty($product_name)) {
        $subquery .= " AND name LIKE :product_name";
        $subquery_params[':product_name'] = '%' . $product_name . '%';
    }
    if (!empty($barcode)) {
        $subquery .= " AND barcode LIKE :barcode";
        $subquery_params[':barcode'] = '%' . $barcode . '%';
    }
    
    $conditions[] = "id IN (" . $subquery . ")";
    $params = array_merge($params, $subquery_params);
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY created_at DESC LIMIT 500";

$stmt = $shop_pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des ventes - <?php echo htmlspecialchars($shop['name']); ?></title>
    
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
            max-width: 1200px;
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
        }

        .card-header {
            display: flex;
            justify-content: space-between;
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
        
        /* Styles pour le formulaire de recherche */
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: flex-end; /* Aligne les éléments en bas */
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            /* Utilisation de flex-basis pour mieux contrôler la largeur */
            flex: 1 1 200px; /* Croître, rétrécir, et une largeur de base de 200px */
            min-width: 150px;
        }
        
        .form-control {
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        /* Styles pour les boutons */
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
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        /* Tableau et autres styles ... */
        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 1.5rem;
        }

        .table thead tr {
            background-color: #f4f4f4;
        }

        .table th, .table td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            text-transform: uppercase;
            font-weight: 600;
            color: var(--light-text-color);
        }

        .table tbody tr:hover {
            background-color: #f1f1f1;
        }
        
        .empty-state {
            text-align: center;
            color: var(--light-text-color);
            padding: 2rem 0;
            font-style: italic;
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
                <h2 class="card-title"><i class="fas fa-history"></i> Historique des ventes</h2>
            </div>
            
            <form action="sales.php" method="GET" class="search-form">
                <div class="form-group">
                    <label for="start_date">Date de début</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">Date de fin</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Rechercher</button>
                <a href="sales.php" class="btn btn-danger"><i class="fas fa-sync-alt"></i> Réinitialiser</a>
            </form>
            
            <?php if (empty($sales)): ?>
                <div class="empty-state">
                    Aucune vente ne correspond à vos critères de recherche.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Paiement</th>
                            <th>Monnaie</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
                                <td><?php echo number_format($sale['total'], 0, ',', ' '); ?> Ar</td>
                                <td><?php echo number_format($sale['payment_received'], 0, ',', ' '); ?> Ar</td>
                                <td><?php echo number_format($sale['change_amount'], 0, ',', ' '); ?> Ar</td>
                                <td>
                                    <a href="invoice.php?id=<?php echo htmlspecialchars($sale['id']); ?>" class="btn btn-primary" target="_blank" title="Voir les détails de la facture">
                                        <i class="fas fa-file-invoice"></i> Voir facture
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>