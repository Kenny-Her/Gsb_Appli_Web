<?php
$host = 'kennyha214.mysql.db';
$port = '3306';
$db = 'kennyha214';
$user = 'kennyha214';
$pass = 'dRzUtxB7iqHu4aT';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
session_start();

if (isset($_SESSION['user'])) {
    $timeout = 1200;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header(header: "Location: index.php?error=inactivity");
        exit();
    }
    $_SESSION['last_activity'] = time();
}
?>