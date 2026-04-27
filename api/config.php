<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function getDB() {
    $host = 'kennyha214.mysql.db';
    $db   = 'kennyha214';
    $user = 'kennyha214';
    $pass = 'dRzUtxB7iqHu4aT';
    try {
        $pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        json_response(["status" => 500, "message" => "Erreur base de données"], 500);
    }
}

function getAuthUser($pdo) {
    // 1. Token en paramètre GET ou POST (prioritaire, contourne OVH CGI)
    $token = '';
    if (!empty($_GET['token'])) {
        $token = trim($_GET['token']);
    } elseif (!empty($_POST['token'])) {
        $token = trim($_POST['token']);
    }

    // 2. Fallback : header Authorization (si disponible)
    if (empty($token)) {
        $authHeader = '';
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (empty($authHeader)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';
        }
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        }
    }

    if (empty($token)) {
        json_response(["status" => 401, "message" => "Token manquant"], 401);
    }
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        json_response(["status" => 401, "message" => "Token invalide ou expiré"], 401);
    }
    return $user;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}
