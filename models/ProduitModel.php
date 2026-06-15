<?php
require_once __DIR__ . '/Model.php';

/**
 * Modèle pour la gestion des produits pharmaceutiques.
 * Partie MODEL du pattern MVC — contient toute la logique d'accès aux données.
 */
class ProduitModel extends Model {

    /**
     * Retourne la liste complète des produits avec leur famille.
     */
    public function findAll(): array {
        return $this->pdo->query("
            SELECT p.*, f.libelle AS famille_libelle
            FROM produits p
            LEFT JOIN familles f ON p.id_famille = f.id
            ORDER BY f.libelle, p.nom
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un nouveau produit.
     */
    public function create(string $nom, ?int $id_famille): bool {
        return $this->pdo->prepare("
            INSERT INTO produits (nom, id_famille) VALUES (?, ?)
        ")->execute([$nom, $id_famille]);
    }

    /**
     * Modifie un produit existant.
     */
    public function update(int $id, string $nom, ?int $id_famille): bool {
        return $this->pdo->prepare("
            UPDATE produits SET nom = ?, id_famille = ? WHERE id = ?
        ")->execute([$nom, $id_famille, $id]);
    }

    /**
     * Supprime un produit par son identifiant.
     */
    public function delete(int $id): bool {
        return $this->pdo->prepare("DELETE FROM produits WHERE id = ?")
                         ->execute([$id]);
    }
}
