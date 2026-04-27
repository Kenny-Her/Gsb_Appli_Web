<?php
require 'config.php';
$pdo  = getDB();
$user = getAuthUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $types = $pdo->query("SELECT * FROM type_praticiens ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);
    json_response(["status" => 200, "types" => $types]);
}
json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
