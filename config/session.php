<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['shop_id']) && isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentShop() {
    return [
        'id' => $_SESSION['shop_id'] ?? null,
        'name' => $_SESSION['shop_name'] ?? null,
        'db' => $_SESSION['shop_db'] ?? null
    ];
}

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}

function isAdmin() {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

function isCashier() {
    return ($_SESSION['user_role'] ?? '') === 'cashier';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: pos.php');
        exit();
    }
}
?>
