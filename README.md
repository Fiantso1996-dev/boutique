# Système de Gestion de Caisse Multi-Boutiques

## Description

Système complet de gestion de caisse développé en PHP natif avec design flat, permettant la gestion de plusieurs boutiques avec des bases de données séparées.

## Fonctionnalités

### 🏪 Multi-boutiques
- Connexion séparée pour chaque boutique
- Base de données indépendante par boutique
- Création automatique de la structure de données

### 📦 Gestion des produits
- Ajout, modification, suppression de produits
- Gestion du stock en temps réel
- Génération automatique de codes-barres EAN-13
- Téléchargement des codes-barres générés

### 💰 Point de vente (POS)
- Interface de caisse intuitive
- Scan de codes-barres
- Ajout de produits par clic
- Calcul automatique du total et de la monnaie

### 🧾 Facturation
- Génération automatique de factures
- Format d'impression 80mm (compatible Xprinter N13)
- Numérotation automatique des factures
- Code-barres sur chaque facture

### 📊 Suivi des ventes
- Historique complet des ventes
- Statistiques du tableau de bord
- Mouvements de stock automatiques

## Installation

1. **Prérequis**
   - Serveur web (Apache/Nginx)
   - PHP 7.4 ou supérieur
   - MySQL 5.7 ou supérieur
   - Extension PHP GD (pour les codes-barres)

2. **Installation**
   \`\`\`bash
   # Cloner ou télécharger le projet
   git clone [url-du-projet]
   
   # Configurer la base de données dans config/database.php
   # Modifier les paramètres de connexion MySQL
   
   # Créer le dossier temp pour les codes-barres
   mkdir temp
   chmod 777 temp
   \`\`\`

3. **Configuration**
   - Modifier `config/database.php` avec vos paramètres MySQL
   - Vérifier que l'extension GD est activée pour PHP
   - Configurer votre serveur web pour pointer vers le dossier du projet

## Utilisation

### Première connexion
1. Accéder à l'application via votre navigateur
2. Créer une nouvelle boutique ou se connecter à une existante
3. Le système créera automatiquement la base de données de la boutique

### Gestion des produits
1. Aller dans "Produits" > "Ajouter un produit"
2. Saisir les informations du produit
3. Le code-barres sera généré automatiquement si non fourni
4. Télécharger et imprimer le code-barres

### Utilisation de la caisse
1. Aller dans "Caisse"
2. Scanner les codes-barres ou cliquer sur les produits
3. Saisir le montant reçu
4. Valider la vente
5. La facture s'ouvrira automatiquement pour impression

## Structure du projet

\`\`\`
/
├── config/
│   ├── database.php      # Configuration base de données
│   └── session.php       # Gestion des sessions
├── includes/
│   ├── init.php         # Initialisation
│   └── barcode.php      # Générateur de codes-barres
├── assets/css/
│   └── style.css        # Styles CSS (design flat)
├── scripts/
│   ├── create_main_database.sql
│   └── create_shop_tables.sql
├── temp/                # Dossier temporaire pour codes-barres
├── login.php           # Page de connexion
├── dashboard.php       # Tableau de bord
├── products.php        # Gestion des produits
├── pos.php            # Point de vente
├── sales.php          # Historique des ventes
├── invoice.php        # Génération de factures
├── barcode.php        # Affichage/téléchargement codes-barres
└── process_sale.php   # Traitement des ventes (API)
\`\`\`

## Sécurité

- Sessions PHP sécurisées
- Protection contre l'injection SQL (PDO avec requêtes préparées)
- Validation des données côté serveur
- Séparation des bases de données par boutique

## Impression

Le système génère des factures au format 80mm, optimisées pour les imprimantes thermiques comme la Xprinter N13. Les factures incluent :
- En-tête avec nom et téléphone de la boutique
- Détail des articles avec prix et quantités
- Total, montant reçu et monnaie rendue
- Code-barres de la facture
- Message de remerciement

## Support

Pour toute question ou problème, veuillez consulter la documentation ou contacter le support technique.
