<?php
require 'config.php';

$pdo  = getDB();
$user = getAuthUser($pdo);

if ($user['role'] !== 'Delegue') {
    json_response(["status" => 403, "message" => "Réservé aux délégués"], 403);
}

$stmt = $pdo->prepare("
    SELECT r.*, u.prenom as visiteur_prenom, u.nom as visiteur_nom,
           p.nom as praticien_nom, p.prenom as praticien_prenom
    FROM rapports r
    JOIN utilisateurs u ON r.id_utilisateur = u.id
    JOIN praticiens p ON r.id_praticien = p.id
    WHERE u.id_delegue = ?
    ORDER BY r.date_creation DESC
");
$stmt->execute([$user['id']]);
$rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rapports as &$r) {
    $stmtP = $pdo->prepare("SELECT p.nom FROM produits p JOIN rapport_produits rp ON p.id = rp.id_produit WHERE rp.id_rapport = ?");
    $stmtP->execute([$r['id']]);
    $r['produits'] = $stmtP->fetchAll(PDO::FETCH_COLUMN);
}

json_response(["status" => 200, "rapports" => $rapports]);
