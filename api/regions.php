<?php
require 'config.php';

$pdo  = getDB();
$user = getAuthUser($pdo);

$method = $_SERVER['REQUEST_METHOD'];

// GET : liste des régions
if ($method === 'GET') {
    $regions = $pdo->query("SELECT * FROM regions ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    json_response(["status" => 200, "regions" => $regions]);
}

json_response(["status" => 405, "message" => "Méthode non autorisée"], 405);
