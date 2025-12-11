<?php
require 'bd_connexion.php';
require 'accueil.php';

$id_utilisateur = $_SESSION['user']['id'];
$success_msg = '';

if (isset($_POST['ajout_rapport'])) {
    try {
        $pdo->beginTransaction();

        $sqlRapport = "INSERT INTO rapports (id_utilisateur, id_praticien, date_visite, lieu_visite, bilan) VALUES (?, ?, ?, ?, ?)";
        $stmtRapport = $pdo->prepare($sqlRapport);
        $stmtRapport->execute([
            $id_utilisateur,
            $_POST['id_praticien'],
            $_POST['date_visite'],
            $_POST['lieu_visite'],
            $_POST['bilan']
        ]);
        $id_rapport = $pdo->lastInsertId();

        if (!empty($_POST['produits'])) {
            $sqlProduits = "INSERT INTO rapport_produits (id_rapport, id_produit) VALUES (?, ?)";
            $stmtProduits = $pdo->prepare($sqlProduits);
            foreach ($_POST['produits'] as $id_produit) {
                $stmtProduits->execute([$id_rapport, $id_produit]);
            }
        }

        $pdo->commit();
        $success_msg = "Rapport soumis avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la soumission du rapport : " . $e->getMessage());
    }
}

$stmtRapports = $pdo->prepare("SELECT r.*, p.nom as praticien_nom, p.prenom as praticien_prenom FROM rapports r JOIN praticiens p ON r.id_praticien = p.id WHERE r.id_utilisateur = ? ORDER BY r.date_creation DESC");
$stmtRapports->execute([$id_utilisateur]);
$rapports = $stmtRapports->fetchAll();

$praticiens = $pdo->query("SELECT * FROM praticiens ORDER BY nom, prenom")->fetchAll();
$produits = $pdo->query("SELECT * FROM produits ORDER BY nom")->fetchAll();
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
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <div style="display:flex; gap:10px;">
            <div style="flex:1"><label>Date de la visite</label><input type="date" name="date_visite" required></div>
            <div style="flex:1"><label>Lieu de la visite</label><input type="text" name="lieu_visite" required></div>
        </div>
        
        <label>Produit(s) présenté(s) (maintenir CTRL pour sélectionner plusieurs)</label>
        <select name="produits[]" multiple size="5">
            <?php foreach ($produits as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
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
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rapports as $r): ?>
            <tr>
                <td><?= date("d/m/Y", strtotime($r['date_creation'])) ?></td>
                <td><?= htmlspecialchars($r['praticien_nom'] . ' ' . $r['praticien_prenom']) ?></td>
                <td><?= date("d/m/Y", strtotime($r['date_visite'])) ?></td>
                <td><?= htmlspecialchars(substr($r['bilan'], 0, 50)) ?>...</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>