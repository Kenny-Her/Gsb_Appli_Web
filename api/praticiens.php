<?php
require 'config.php';

$pdo    = getDB();
$user   = getAuthUser($pdo);
$role   = $user['role'];
$method = $_SERVER['REQUEST_METHOD'];

// GET : liste des praticiens avec leur type
if ($method === 'GET') {
    $praticiens = $pdo->query("
        SELECT p.*, t.libelle as type_libelle
        FROM praticiens p
        LEFT JOIN type_praticiens t ON p.id_type = t.id
        ORDER BY p.nom, p.prenom
    ")->fetchAll(PDO::FETCH_ASSOC);
    json_response(["status" => 200, "praticiens" => $praticiens]);
}

// POST : ajouter un praticien (Responsable / Admin)
if ($method === 'POST') {
    if ($role !== 'Responsable' && $role !== 'Admin') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    $nom       = $_POST['nom']       ?? '';
    $prenom    = $_POST['prenom']    ?? '';
    $adresse   = $_POST['adresse']   ?? '';
    $email     = $_POST['email']     ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $region    = $_POST['region']    ?? '';
    $id_type   = !empty($_POST['id_type']) ? (int)$_POST['id_type'] : null;

    if (!$nom || !$prenom) {
        json_response(["status" => 400, "message" => "Nom et prénom requis"], 400);
    }

    $pdo->prepare("INSERT INTO praticiens (nom, prenom, adresse, email, telephone, region, id_type) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$nom, $prenom, $adresse, $email, $telephone, $region, $id_type]);
    json_response(["status" => 201, "message" => "Praticien ajouté"]);
}

// PUT : modifier un praticien
if ($method === 'PUT' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT')) {
    if ($role !== 'Responsable' && $role !== 'Admin') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    $id        = $_POST['id']        ?? '';
    $nom       = $_POST['nom']       ?? '';
    $prenom    = $_POST['prenom']    ?? '';
    $adresse   = $_POST['adresse']   ?? '';
    $email     = $_POST['email']     ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $region    = $_POST['region']    ?? '';
    $id_type   = !empty($_POST['id_type']) ? (int)$_POST['id_type'] : null;

    if (!$id || !$nom || !$prenom) {
        json_response(["status" => 400, "message" => "ID, nom et prénom requis"], 400);
    }
    $pdo->prepare("UPDATE praticiens SET nom=?, prenom=?, adresse=?, email=?, telephone=?, region=?, id_type=? WHERE id=?")
        ->execute([$nom, $prenom, $adresse, $email, $telephone, $region, $id_type, $id]);
    json_response(["status" => 200, "message" => "Praticien modifié"]);
}

// DELETE : supprimer un praticien
if ($method === 'DELETE') {
    if ($role !== 'Responsable' && $role !== 'Admin') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'] ?? $_GET['id'] ?? '';
    if (!$id) {
        json_response(["status" => 400, "message" => "ID praticien requis"], 400);
    }
    $pdo->prepare("DELETE FROM praticiens WHERE id = ?")->execute([$id]);
    json_response(["status" => 200, "message" => "Praticien supprimé"]);
}

json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
