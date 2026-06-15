<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
}

$pdo  = getDB();
$user = getAuthUser($pdo);

if ($user['role'] !== 'Delegue') {
    json_response(["status" => 403, "message" => "Réservé aux délégués"], 403);
}

$id_rapport = $_POST['id_rapport'] ?? '';
if (!$id_rapport) {
    json_response(["status" => 400, "message" => "ID rapport requis"], 400);
}

$stmt = $pdo->prepare("
    SELECT r.* FROM rapports r
    JOIN utilisateurs u ON r.id_utilisateur = u.id
    WHERE r.id = ? AND u.id_delegue = ?
");
$stmt->execute([$id_rapport, $user['id']]);
$rapport = $stmt->fetch();

if (!$rapport) {
    json_response(["status" => 404, "message" => "Rapport introuvable ou non autorisé"], 404);
}

$pdo->prepare("UPDATE rapports SET statut = 'Validé' WHERE id = ?")->execute([$id_rapport]);
json_response(["status" => 200, "message" => "Rapport validé avec succès"]);
