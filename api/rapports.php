<?php
require 'config.php';

$pdo    = getDB();
$user   = getAuthUser($pdo);
$id     = $user['id'];
$role   = $user['role'];
// Détection method override (Android/Volley ne supporte pas PUT nativement)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && (($_POST['_method'] ?? '') === 'PUT' || ($_GET['_method'] ?? '') === 'PUT')) {
    $method = 'PUT';
}

// GET : récupérer les rapports
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT r.*, p.nom as praticien_nom, p.prenom as praticien_prenom
        FROM rapports r
        JOIN praticiens p ON r.id_praticien = p.id
        WHERE r.id_utilisateur = ?
        ORDER BY r.date_creation DESC
    ");
    $stmt->execute([$id]);
    $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter les produits de chaque rapport
    foreach ($rapports as &$r) {
        $stmtP = $pdo->prepare("SELECT p.id, p.nom FROM produits p JOIN rapport_produits rp ON p.id = rp.id_produit WHERE rp.id_rapport = ?");
        $stmtP->execute([$r['id']]);
        $r['produits'] = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    }

    json_response(["status" => 200, "rapports" => $rapports]);
}

// POST : créer un rapport (Visiteur uniquement)
if ($method === 'POST') {
    if ($role !== 'Visiteur') {
        json_response(["status" => 403, "message" => "Réservé aux visiteurs"], 403);
    }

    $id_praticien = $_POST['id_praticien'] ?? '';
    $date_visite  = $_POST['date_visite'] ?? '';
    $lieu_visite  = $_POST['lieu_visite'] ?? '';
    $bilan        = $_POST['bilan'] ?? '';
    $produits     = isset($_POST['produits']) ? explode(',', $_POST['produits']) : [];

    if (!$id_praticien || !$date_visite || !$lieu_visite || !$bilan) {
        json_response(["status" => 400, "message" => "Tous les champs sont requis"], 400);
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO rapports (id_utilisateur, id_praticien, date_visite, lieu_visite, bilan) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $id_praticien, $date_visite, $lieu_visite, $bilan]);
    $id_rapport = $pdo->lastInsertId();

    if (!empty($produits)) {
        $stmtP = $pdo->prepare("INSERT INTO rapport_produits (id_rapport, id_produit) VALUES (?, ?)");
        foreach ($produits as $id_produit) {
            if (is_numeric(trim($id_produit))) {
                $stmtP->execute([$id_rapport, trim($id_produit)]);
            }
        }
    }

    $pdo->commit();
    json_response(["status" => 201, "message" => "Rapport soumis avec succès", "id" => $id_rapport]);
}

// PUT : modifier un rapport (non validé uniquement)
if ($method === 'PUT') {
    // Accepter les données depuis le body (PUT standard) ou depuis $_POST (Volley workaround)
    parse_str(file_get_contents("php://input"), $data);
    // Fusionner avec $_POST au cas où
    $data = array_merge($data, $_POST);

    $id_rapport   = $data['id_rapport']   ?? '';
    $bilan        = $data['bilan']        ?? '';
    $id_praticien = $data['id_praticien'] ?? '';
    $date_visite  = $data['date_visite']  ?? '';
    $lieu_visite  = $data['lieu_visite']  ?? '';
    $produits     = isset($data['produits']) ? explode(',', $data['produits']) : [];

    if (!$id_rapport || !$bilan) {
        json_response(["status" => 400, "message" => "Champs requis manquants"], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM rapports WHERE id = ? AND id_utilisateur = ?");
    $stmt->execute([$id_rapport, $id]);
    $rapport = $stmt->fetch();

    if (!$rapport) {
        json_response(["status" => 404, "message" => "Rapport introuvable"], 404);
    }
    if (($rapport['statut'] ?? '') === 'Validé') {
        json_response(["status" => 403, "message" => "Ce rapport est validé, modification impossible"], 403);
    }

    // Mise à jour des champs disponibles
    $setClause = "bilan = ?";
    $params    = [$bilan];
    if ($id_praticien) { $setClause .= ", id_praticien = ?"; $params[] = $id_praticien; }
    if ($date_visite)  { $setClause .= ", date_visite = ?";  $params[] = $date_visite;  }
    if ($lieu_visite)  { $setClause .= ", lieu_visite = ?";  $params[] = $lieu_visite;  }
    $params[] = $id_rapport;
    $params[] = $id;

    $pdo->prepare("UPDATE rapports SET $setClause WHERE id = ? AND id_utilisateur = ?")->execute($params);

    // Mise à jour des produits si fournis
    if (!empty($produits) || isset($data['produits'])) {
        $pdo->prepare("DELETE FROM rapport_produits WHERE id_rapport = ?")->execute([$id_rapport]);
        if (!empty($produits)) {
            $stmtP = $pdo->prepare("INSERT INTO rapport_produits (id_rapport, id_produit) VALUES (?, ?)");
            foreach ($produits as $id_produit) {
                if (is_numeric(trim($id_produit))) {
                    $stmtP->execute([$id_rapport, trim($id_produit)]);
                }
            }
        }
    }

    json_response(["status" => 200, "message" => "Rapport mis à jour"]);
}

// DELETE : supprimer un rapport (non validé uniquement)
if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id_rapport = $data['id_rapport'] ?? $_GET['id_rapport'] ?? '';

    if (!$id_rapport) {
        json_response(["status" => 400, "message" => "ID rapport requis"], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM rapports WHERE id = ? AND id_utilisateur = ?");
    $stmt->execute([$id_rapport, $id]);
    $rapport = $stmt->fetch();

    if (!$rapport) {
        json_response(["status" => 404, "message" => "Rapport introuvable"], 404);
    }
    if (($rapport['statut'] ?? '') === 'Validé') {
        json_response(["status" => 403, "message" => "Rapport validé, suppression impossible"], 403);
    }

    $pdo->prepare("DELETE FROM rapport_produits WHERE id_rapport = ?")->execute([$id_rapport]);
    $pdo->prepare("DELETE FROM rapports WHERE id = ? AND id_utilisateur = ?")->execute([$id_rapport, $id]);
    json_response(["status" => 200, "message" => "Rapport supprimé"]);
}

json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
