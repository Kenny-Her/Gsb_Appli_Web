<?php
/**
 * Classe de base pour tous les modèles.
 * Fournit l'accès à la base de données via PDO.
 */
abstract class Model {
    protected PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
}
