-- Script de création de la base de données principale
CREATE DATABASE IF NOT EXISTS gestion_caisse_main;
USE gestion_caisse_main;

-- Table des boutiques
CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    database_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer une boutique de démonstration
INSERT INTO shops (name, phone, address, database_name) VALUES 
('Boutique Demo', '038 83 267 16', 'Ankadimbahoka', 'shop_boutique_demo');
