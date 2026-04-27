<?php
require 'config.php';
$pdo  = getDB();
$user = getAuthUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $familles = $pdo->query("SELECT * FROM familles ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);
    json_response(["status" => 200, "familles" => $familles]);
}
json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
