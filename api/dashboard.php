<?php
require 'config.php';

$pdo  = getDB();
$user = getAuthUser($pdo);
$id   = $user['id'];
$role = $user['role'];

// Stats visites pour cet utilisateur
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN date_visite < CURDATE() THEN 1 ELSE 0 END) as visites_effectuees,
        SUM(CASE WHEN date_visite >= CURDATE() THEN 1 ELSE 0 END) as visites_a_venir,
        SUM(CASE WHEN MONTH(date_visite) = MONTH(CURRENT_DATE()) AND YEAR(date_visite) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as visites_du_mois
    FROM visites WHERE id_utilisateur = ?
");
$stmt->execute([$id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$data = [
    "status"             => 200,
    "visites_effectuees" => (int)($stats['visites_effectuees'] ?? 0),
    "visites_a_venir"    => (int)($stats['visites_a_venir'] ?? 0),
    "visites_du_mois"    => (int)($stats['visites_du_mois'] ?? 0),
    "objectif"           => 20
];

// Stats supplémentaires pour Responsable et Admin
if ($role === 'Responsable' || $role === 'Admin') {
    $data['nb_praticiens'] = (int)$pdo->query("SELECT COUNT(*) FROM praticiens")->fetchColumn();
    $data['nb_produits']   = (int)$pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
    $data['nb_visiteurs']  = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'Visiteur'")->fetchColumn();
}

// Stats Délégué : rapports en attente de validation
if ($role === 'Delegue') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM rapports r
        JOIN utilisateurs u ON r.id_utilisateur = u.id
        WHERE u.id_delegue = ? AND (r.statut IS NULL OR r.statut != 'Validé')
    ");
    $stmt->execute([$id]);
    $data['rapports_a_valider'] = (int)$stmt->fetchColumn();
}

json_response($data);
