<?php
require_once 'config/session.php';

// Supprimer uniquement les variables de session du super admin
unset($_SESSION['super_admin']);
unset($_SESSION['super_admin_username']);

header('Location: super_login.php');
exit();
?>
