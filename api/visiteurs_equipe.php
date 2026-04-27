<?php
require 'config.php';

$pdo  = getDB();
$user = getAuthUser($pdo);

if ($user['role'] !== 'Delegue') {
    json_response(["status" => 403, "message" => "Réservé aux délégués"], 403);
}

$stmt = $pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE id_delegue = ? AND role = 'Visiteur'");
$stmt->execute([$user['id']]);
$visiteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response(["status" => 200, "visiteurs" => $visiteurs]);
