<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=bd_appli_web;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur BDD : ' . $e->getMessage());
}
session_start();

if (isset($_SESSION['user'])) {
    $timeout = 1200;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: index.php?error=inactivity");
        exit();
    }
    $_SESSION['last_activity'] = time();
}
?>