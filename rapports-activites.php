<?php
require 'bd_connexion.php';
require 'accueil.php';

$id_utilisateur = $_SESSION['user']['id'];
$success_msg = '';

if (isset($_POST['supprimer_rapport'])) {
    $pdo->prepare(query: "DELETE FROM rapports WHERE id = ? AND id_utilisateur = ?")->execute(params: [$_POST['id_rapport'], $id_utilisateur]);
    $success_msg = "Rapport supprimé.";
}

if (isset($_POST['modifier_rapport'])) {
    $pdo->prepare(query: "UPDATE rapports SET bilan = ? WHERE id = ? AND id_utilisateur = ?")
        ->execute(params: [$_POST['bilan_edit'], $_POST['id_rapport'], $id_utilisateur]);
    $success_msg = "Rapport mis à jour.";
}

if (isset($_POST['ajout_rapport'])) {
    try {
        $pdo->beginTransaction();

        $sqlRapport = "INSERT INTO rapports (id_utilisateur, id_praticien, date_visite, lieu_visite, bilan) VALUES (?, ?, ?, ?, ?)";
        $stmtRapport = $pdo->prepare(query: $sqlRapport);
        $stmtRapport->execute(params: [
            $id_utilisateur,
            $_POST['id_praticien'],
            $_POST['date_visite'],
            $_POST['lieu_visite'],
            $_POST['bilan']
        ]);
        $id_rapport = $pdo->lastInsertId();

        if (!empty($_POST['produits'])) {
            $sqlProduits = "INSERT INTO rapport_produits (id_rapport, id_produit) VALUES (?, ?)";
            $stmtProduits = $pdo->prepare(query: $sqlProduits);
            foreach ($_POST['produits'] as $id_produit) {
                $stmtProduits->execute(params: [$id_rapport, $id_produit]);
            }
        }

        $pdo->commit();
        $success_msg = "Rapport soumis avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la soumission du rapport : " . $e->getMessage());
    }
}

$stmtRapports = $pdo->prepare(query: "SELECT r.*, p.nom as praticien_nom, p.prenom as praticien_prenom FROM rapports r JOIN praticiens p ON r.id_praticien = p.id WHERE r.id_utilisateur = ? ORDER BY r.date_creation DESC");
$stmtRapports->execute(params: [$id_utilisateur]);
$rapports = $stmtRapports->fetchAll();

$praticiens = $pdo->query(query: "SELECT * FROM praticiens ORDER BY nom, prenom")->fetchAll();
$produits = $pdo->query(query: "SELECT * FROM produits ORDER BY nom")->fetchAll();
?>

<h2>Mes Rapports d'activités</h2>

<div class="card">
    <h3>Rédiger un rapport</h3>
    <?php if ($success_msg): ?>
        <p style="color: green; margin-bottom: 1rem;"><?= $success_msg ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Praticien visité</label>
        <select name="id_praticien" required>
            <option value="">-- Choisir un praticien --</option>
            <?php foreach ($praticiens as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars(string: $p['nom'] . ' ' . $p['prenom']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <div style="display:flex; gap:10px;">
            <div style="flex:1"><label>Date de la visite</label><input type="date" name="date_visite" required></div>
            <div style="flex:1"><label>Lieu de la visite</label><input type="text" name="lieu_visite" required></div>
        </div>
        
        <label>Produit(s) présenté(s) (maintenir CTRL pour sélectionner plusieurs)</label>
        <select name="produits[]" multiple size="5">
            <?php foreach ($produits as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars(string: $p['nom']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Bilan / Remarques</label>
        <textarea name="bilan" rows="5" required style="width:100%; border:1px solid #ddd;"></textarea>
        
        <button type="submit" name="ajout_rapport" class="btn">Soumettre le rapport</button>
    </form>
</div>

<div class="card">
    <h3>Mes rapports soumis</h3>
    <table>
        <thead>
            <tr>
                <th>Date soumission</th>
                <th>Praticien</th>
                <th>Date visite</th>
                <th>Bilan</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rapports as $r): ?>
            <tr>
                <td><?= date(format: "d/m/Y", timestamp: strtotime(datetime: $r['date_creation'])) ?></td>
                <td><?= htmlspecialchars(string: $r['praticien_nom'] . ' ' . $r['praticien_prenom']) ?></td>
                <td><?= date(format: "d/m/Y", timestamp: strtotime(datetime: $r['date_visite'])) ?></td>
                <td>
                    <form method="POST" style="display:flex; gap:5px;">
                        <input type="hidden" name="id_rapport" value="<?= $r['id'] ?>">
                        <input type="text" name="bilan_edit" value="<?= htmlspecialchars(string: $r['bilan']) ?>" style="width:100%">
                        <button type="submit" name="modifier_rapport" class="btn" style="padding:2px 5px;">OK</button>
                    </form>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Supprimer ce rapport ?');">
                        <input type="hidden" name="id_rapport" value="<?= $r['id'] ?>">
                        <button type="submit" name="supprimer_rapport" class="btn" style="background:#e74c3c; padding:5px;">X</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>