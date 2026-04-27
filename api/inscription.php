<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
}

$pdo  = getDB();
$user = getAuthUser($pdo);

if ($user['role'] !== 'Admin' && $user['role'] !== 'Responsable') {
    json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
}

$nom       = $_POST['nom']       ?? '';
$prenom    = $_POST['prenom']    ?? '';
$email     = $_POST['email']     ?? '';
$mdp       = $_POST['mdp']       ?? '';
$role      = $_POST['role']      ?? 'Visiteur';
$id_region = $_POST['id_region'] ?? null;
if ($id_region !== null) $id_region = (int)$id_region ?: null;

if (!$nom || !$prenom || !$email || !$mdp) {
    json_response(["status" => 400, "message" => "Tous les champs sont requis"], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(["status" => 400, "message" => "Email invalide"], 400);
}

$rolesAutorisés = ['Visiteur', 'Delegue', 'Responsable', 'Admin'];
if (!in_array($role, $rolesAutorisés)) {
    json_response(["status" => 400, "message" => "Rôle invalide"], 400);
}

// Vérifier unicité de l'email
$stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetchColumn() > 0) {
    json_response(["status" => 409, "message" => "Cet email est déjà utilisé"], 409);
}

$hashed = password_hash($mdp, PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mdp, role, id_region) VALUES (?, ?, ?, ?, ?, ?)")
    ->execute([$nom, $prenom, $email, $hashed, $role, $id_region]);

json_response(["status" => 201, "message" => "Compte créé avec succès"]);
