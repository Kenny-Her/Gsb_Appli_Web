<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
}

$pdo   = getDB();
$email = $_POST['email'] ?? '';
$mdp   = $_POST['password'] ?? '';

if (empty($email) || empty($mdp)) {
    json_response(["status" => 400, "message" => "Email et mot de passe requis"], 400);
}

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($mdp, $user['mdp'])) {
    json_response(["status" => 401, "message" => "Identifiants incorrects"], 401);
}

// Générer et sauvegarder le token
$token = bin2hex(random_bytes(32));
$pdo->prepare("UPDATE utilisateurs SET token = ? WHERE id = ?")->execute([$token, $user['id']]);

json_response([
    "status"    => 200,
    "message"   => "Connexion réussie",
    "id"        => (int)$user['id'],
    "firstName" => $user['prenom'],
    "lastName"  => $user['nom'],
    "email"     => $user['email'],
    "role"      => $user['role'],
    "token"     => $token
]);
