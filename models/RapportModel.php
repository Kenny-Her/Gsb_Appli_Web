<?php
require_once __DIR__ . '/Model.php';

/**
 * Modèle pour la gestion des rapports de visites médicales.
 * Partie MODEL du pattern MVC — contient toute la logique d'accès aux données.
 */
class RapportModel extends Model {

    /**
     * Retourne les rapports d'un visiteur (avec praticien et produits).
     */
    public function findByVisiteur(int $id_visiteur): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*,
                   p.nom AS nom_praticien, p.prenom AS prenom_praticien,
                   GROUP_CONCAT(pr.nom SEPARATOR ', ') AS produits_liste
            FROM rapports r
            LEFT JOIN praticiens p ON r.id_praticien = p.id
            LEFT JOIN rapport_produits rp ON r.id = rp.id_rapport
            LEFT JOIN produits pr ON rp.id_produit = pr.id
            WHERE r.id_visiteur = ?
            GROUP BY r.id
            ORDER BY r.date_visite DESC
        ");
        $stmt->execute([$id_visiteur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retourne tous les rapports non validés (pour les délégués).
     */
    public function findNonValides(): array {
        return $this->pdo->query("
            SELECT r.*,
                   u.nom AS nom_visiteur, u.prenom AS prenom_visiteur,
                   p.nom AS nom_praticien, p.prenom AS prenom_praticien,
                   GROUP_CONCAT(pr.nom SEPARATOR ', ') AS produits_liste
            FROM rapports r
            LEFT JOIN utilisateurs u ON r.id_visiteur = u.id
            LEFT JOIN praticiens p ON r.id_praticien = p.id
            LEFT JOIN rapport_produits rp ON r.id = rp.id_rapport
            LEFT JOIN produits pr ON rp.id_produit = pr.id
            WHERE r.valide = 0
            GROUP BY r.id
            ORDER BY r.date_visite DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau rapport et lie les produits associés.
     */
    public function create(int $id_visiteur, array $data, array $id_produits): bool {
        $this->pdo->prepare("
            INSERT INTO rapports (id_visiteur, id_praticien, date_visite, lieu_visite, bilan)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $id_visiteur,
            $data['id_praticien'],
            $data['date_visite'],
            $data['lieu_visite'] ?? '',
            $data['bilan']       ?? '',
        ]);

        $id_rapport = (int)$this->pdo->lastInsertId();
        $this->lierProduits($id_rapport, $id_produits);
        return true;
    }

    /**
     * Modifie un rapport existant.
     */
    public function update(int $id, array $data, array $id_produits): bool {
        $this->pdo->prepare("
            UPDATE rapports
            SET id_praticien=?, date_visite=?, lieu_visite=?, bilan=?
            WHERE id=?
        ")->execute([
            $data['id_praticien'],
            $data['date_visite'],
            $data['lieu_visite'] ?? '',
            $data['bilan']       ?? '',
            $id,
        ]);

        // Réinitialiser les produits liés
        $this->pdo->prepare("DELETE FROM rapport_produits WHERE id_rapport = ?")->execute([$id]);
        $this->lierProduits($id, $id_produits);
        return true;
    }

    /**
     * Valide un rapport (délégué uniquement).
     */
    public function valider(int $id): bool {
        return $this->pdo->prepare("UPDATE rapports SET valide = 1 WHERE id = ?")
                         ->execute([$id]);
    }

    /**
     * Supprime un rapport et ses produits associés.
     */
    public function delete(int $id): bool {
        $this->pdo->prepare("DELETE FROM rapport_produits WHERE id_rapport = ?")->execute([$id]);
        return $this->pdo->prepare("DELETE FROM rapports WHERE id = ?")->execute([$id]);
    }

    /**
     * Lie des produits à un rapport (table pivot).
     */
    private function lierProduits(int $id_rapport, array $id_produits): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO rapport_produits (id_rapport, id_produit) VALUES (?, ?)"
        );
        foreach ($id_produits as $id_produit) {
            if (!empty($id_produit)) {
                $stmt->execute([$id_rapport, (int)$id_produit]);
            }
        }
    }
}
