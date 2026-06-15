<?php
require_once __DIR__ . '/Model.php';

/**
 * Modèle pour la gestion des praticiens.
 * Partie MODEL du pattern MVC — contient toute la logique d'accès aux données.
 */
class PraticienModel extends Model {

    /**
     * Retourne la liste complète des praticiens avec leur type.
     */
    public function findAll(): array {
        return $this->pdo->query("
            SELECT p.*, t.libelle AS type_libelle
            FROM praticiens p
            LEFT JOIN type_praticiens t ON p.id_type = t.id
            ORDER BY p.nom, p.prenom
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un nouveau praticien.
     */
    public function create(array $data): bool {
        $id_type = !empty($data['id_type']) ? (int)$data['id_type'] : null;
        return $this->pdo->prepare("
            INSERT INTO praticiens (nom, prenom, id_type, adresse, email, telephone, region)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $data['nom'],
            $data['prenom'],
            $id_type,
            $data['adresse']   ?? '',
            $data['email']     ?? '',
            $data['telephone'] ?? '',
            $data['region']    ?? '',
        ]);
    }

    /**
     * Modifie un praticien existant.
     */
    public function update(int $id, array $data): bool {
        $id_type = !empty($data['id_type']) ? (int)$data['id_type'] : null;
        return $this->pdo->prepare("
            UPDATE praticiens
            SET nom=?, prenom=?, id_type=?, adresse=?, email=?, telephone=?, region=?
            WHERE id=?
        ")->execute([
            $data['nom'],
            $data['prenom'],
            $id_type,
            $data['adresse']   ?? '',
            $data['email']     ?? '',
            $data['telephone'] ?? '',
            $data['region']    ?? '',
            $id,
        ]);
    }

    /**
     * Supprime un praticien par son identifiant.
     */
    public function delete(int $id): bool {
        return $this->pdo->prepare("DELETE FROM praticiens WHERE id = ?")
                         ->execute([$id]);
    }
}
