<?php
require 'config.php';

$pdo    = getDB();
$user   = getAuthUser($pdo);
$role   = $user['role'];
$method = $_SERVER['REQUEST_METHOD'];

// GET : liste des produits avec leur famille
if ($method === 'GET') {
    $produits = $pdo->query("
        SELECT p.*, f.libelle as famille_libelle
        FROM produits p
        LEFT JOIN familles f ON p.id_famille = f.id
        ORDER BY p.nom
    ")->fetchAll(PDO::FETCH_ASSOC);
    json_response(["status" => 200, "produits" => $produits]);
}

// POST : ajouter un produit
if ($method === 'POST') {
    if ($role !== 'Responsable' && $role !== 'Admin') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    $nom       = $_POST['nom']       ?? '';
    $id_famille = !empty($_POST['id_famille']) ? (int)$_POST['id_famille'] : null;
    if (!$nom) {
        json_response(["status" => 400, "message" => "Nom du produit requis"], 400);
    }
    $pdo->prepare("INSERT INTO produits (nom, id_famille) VALUES (?, ?)")->execute([$nom, $id_famille]);
    json_response(["status" => 201, "message" => "Produit ajouté"]);
}

// PUT : modifier un produit
if ($method === 'PUT' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT')) {
    if ($role !== 'Responsable' && $role !== 'Admin') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    $id         = $_POST['id']  ?? '';
    $nom        = $_POST['nom'] ?? '';
    $id_famille = !empty($_POST['id_famille']) ? (int)$_POST['id_famille'] : null;

    if (!$id || !$nom) {
        json_response(["status" => 400, "message" => "ID et nom requis"], 400);
    }
    $pdo->prepare("UPDATE produits SET nom=?, id_famille=? WHERE id=?")
        ->execute([$nom, $id_famille, $id]);
    json_response(["status" => 200, "message" => "Produit modifié"]);
}

// DELETE : supprimer un produit
if ($method === 'DELETE') {
    if ($role !== 'Responsable' && $role !== 'Admin') {
        json_response(["status" => 403, "message" => "Accès non autorisé"], 403);
    }
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'] ?? $_GET['id'] ?? '';
    if (!$id) {
        json_response(["status" => 400, "message" => "ID produit requis"], 400);
    }
    $pdo->prepare("DELETE FROM produits WHERE id = ?")->execute([$id]);
    json_response(["status" => 200, "message" => "Produit supprimé"]);
}

json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
