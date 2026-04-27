<?php
require 'config.php';

$pdo    = getDB();
$user   = getAuthUser($pdo);
$id     = $user['id'];
$role   = $user['role'];
$method = $_SERVER['REQUEST_METHOD'];

// GET : récupérer les visites
if ($method === 'GET') {
    if ($role === 'Delegue') {
        // Le délégué voit toutes les visites de son équipe
        $stmt = $pdo->prepare("
            SELECT v.*, u.prenom as visiteur_prenom, u.nom as visiteur_nom,
                   p.nom as praticien_nom, p.prenom as praticien_prenom
            FROM visites v
            JOIN utilisateurs u ON v.id_utilisateur = u.id
            JOIN praticiens p ON v.id_praticien = p.id
            WHERE u.id_delegue = ?
            ORDER BY v.date_visite DESC
        ");
        $stmt->execute([$id]);
    } else {
        // Le visiteur voit ses propres visites
        $stmt = $pdo->prepare("
            SELECT v.*, p.nom as praticien_nom, p.prenom as praticien_prenom
            FROM visites v
            JOIN praticiens p ON v.id_praticien = p.id
            WHERE v.id_utilisateur = ?
            ORDER BY v.date_visite ASC
        ");
        $stmt->execute([$id]);
    }
    $visites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_response(["status" => 200, "visites" => $visites]);
}

// POST : créer une visite (Délégué uniquement)
if ($method === 'POST') {
    if ($role !== 'Delegue') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    $id_visiteur   = $_POST['id_visiteur'] ?? '';
    $id_praticien  = $_POST['id_praticien'] ?? '';
    $date_visite   = $_POST['date'] ?? '';
    $heure_visite  = $_POST['heure'] ?? '';
    $lieu          = $_POST['lieu'] ?? '';
    $motif         = $_POST['motif'] ?? '';

    if (!$id_visiteur || !$id_praticien || !$date_visite || !$heure_visite || !$lieu || !$motif) {
        json_response(["status" => 400, "message" => "Tous les champs sont requis"], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO visites (id_utilisateur, id_praticien, date_visite, heure_visite, lieu, motif) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_visiteur, $id_praticien, $date_visite, $heure_visite, $lieu, $motif]);
    json_response(["status" => 201, "message" => "Visite planifiée avec succès"]);
}

// DELETE : supprimer une visite
if ($method === 'DELETE') {
    if ($role !== 'Delegue') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    parse_str(file_get_contents("php://input"), $data);
    $id_visite = $data['id_visite'] ?? $_GET['id_visite'] ?? '';
    if (!$id_visite) {
        json_response(["status" => 400, "message" => "ID visite requis"], 400);
    }
    $pdo->prepare("DELETE FROM visites WHERE id = ?")->execute([$id_visite]);
    json_response(["status" => 200, "message" => "Visite supprimée"]);
}

json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
