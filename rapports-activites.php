<?php
require 'bd_connexion.php';
require 'accueil.php';

$id_utilisateur = $_SESSION['user']['id'];
$success_msg = '';

if (isset($_POST['supprimer_rapport'])) {
    $pdo->prepare(query: "DELETE FROM rapports WHERE id = ? AND id_utilisateur = ?")->execute(params: [$_POST['id_rapport'], $id_utilisateur]);
    $success_msg = "Rapport supprimé.";
    header("Location: rapports-activites.php?msg=supprime");
    exit();
}

if (isset($_POST['modifier_rapport'])) {
    // Mise à jour du bilan
    $pdo->prepare(query: "UPDATE rapports SET bilan = ?, id_praticien = ?, date_visite = ?, lieu_visite = ? WHERE id = ? AND id_utilisateur = ?")
        ->execute(params: [
            $_POST['bilan_edit'],
            $_POST['id_praticien'],
            $_POST['date_visite'],
            $_POST['lieu_visite'],
            $_POST['id_rapport'],
            $id_utilisateur
        ]);
    // Mise à jour des produits : supprimer les anciens, insérer les nouveaux
    $pdo->prepare(query: "DELETE FROM rapport_produits WHERE id_rapport = ?")->execute(params: [$_POST['id_rapport']]);
    if (!empty($_POST['produits'])) {
        $stmtP = $pdo->prepare(query: "INSERT INTO rapport_produits (id_rapport, id_produit) VALUES (?, ?)");
        foreach ($_POST['produits'] as $id_produit) {
            $stmtP->execute(params: [$_POST['id_rapport'], $id_produit]);
        }
    }
    header("Location: rapports-activites.php?msg=modifie");
    exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'supprime') $success_msg = "Rapport supprimé.";
    if ($_GET['msg'] === 'modifie') $success_msg = "Rapport mis à jour avec succès !";
    if ($_GET['msg'] === 'soumis')  $success_msg = "Rapport soumis avec succès !";
}

// Rapport à modifier (si ?edit_id=X)
$rapport_edit = null;
$produits_edit = [];
if (!empty($_GET['edit_id'])) {
    $stmtEdit = $pdo->prepare(query: "SELECT * FROM rapports WHERE id = ? AND id_utilisateur = ? AND (statut IS NULL OR statut != 'Validé')");
    $stmtEdit->execute(params: [$_GET['edit_id'], $id_utilisateur]);
    $rapport_edit = $stmtEdit->fetch();
    if ($rapport_edit) {
        $stmtProdEdit = $pdo->prepare(query: "SELECT id_produit FROM rapport_produits WHERE id_rapport = ?");
        $stmtProdEdit->execute(params: [$rapport_edit['id']]);
        $produits_edit = array_column($stmtProdEdit->fetchAll(), 'id_produit');
    }
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
        header("Location: rapports-activites.php?msg=soumis");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la soumission du rapport : " . $e->getMessage());
    }
}

$stmtRapports = $pdo->prepare(query: "
    SELECT r.*, p.nom as praticien_nom, p.prenom as praticien_prenom,
           GROUP_CONCAT(pr.nom ORDER BY pr.nom SEPARATOR ', ') as produits_noms
    FROM rapports r
    JOIN praticiens p ON r.id_praticien = p.id
    LEFT JOIN rapport_produits rp ON rp.id_rapport = r.id
    LEFT JOIN produits pr ON pr.id = rp.id_produit
    WHERE r.id_utilisateur = ?
    GROUP BY r.id
    ORDER BY r.date_creation DESC
");
$stmtRapports->execute(params: [$id_utilisateur]);
$rapports = $stmtRapports->fetchAll();

$praticiens = $pdo->query(query: "SELECT * FROM praticiens ORDER BY nom, prenom")->fetchAll();
$produits = $pdo->query(query: "SELECT * FROM produits ORDER BY nom")->fetchAll();
?>

<h2>Mes Rapports d'activités</h2>

<?php if ($success_msg): ?>
    <p style="color:green; padding: 0.5rem 1rem; background:#eaffea; border-left:4px solid green; margin-bottom:1rem;"><?= $success_msg ?></p>
<?php endif; ?>

<?php if ($rapport_edit): ?>
<!-- ===== FORMULAIRE MODIFICATION ===== -->
<div class="card" style="border-left: 5px solid #f39c12;">
    <h3>✏️ Modifier le rapport #<?= $rapport_edit['id'] ?></h3>
    <form method="POST" action="rapports-activites.php">
        <input type="hidden" name="id_rapport" value="<?= $rapport_edit['id'] ?>">
        <label>Praticien visité</label>
        <select name="id_praticien" required>
            <option value="">-- Choisir un praticien --</option>
            <?php foreach ($praticiens as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $rapport_edit['id_praticien'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $p['nom'] . ' ' . $p['prenom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div style="display:flex; gap:10px;">
            <div style="flex:1"><label>Date de la visite</label><input type="date" name="date_visite" value="<?= $rapport_edit['date_visite'] ?>" required></div>
            <div style="flex:1"><label>Lieu de la visite</label><input type="text" name="lieu_visite" value="<?= htmlspecialchars(string: $rapport_edit['lieu_visite']) ?>" required></div>
        </div>
        <label>Produit(s) présenté(s) (maintenir CTRL pour sélectionner plusieurs)</label>
        <select name="produits[]" multiple size="5">
            <?php foreach ($produits as $p): ?>
                <option value="<?= $p['id'] ?>" <?= in_array($p['id'], $produits_edit) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(string: $p['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label>Bilan / Remarques</label>
        <textarea name="bilan_edit" rows="6" required style="width:100%; border:1px solid #ddd;"><?= htmlspecialchars(string: $rapport_edit['bilan']) ?></textarea>
        <div style="display:flex; gap:10px; margin-top:0.5rem;">
            <button type="submit" name="modifier_rapport" class="btn" style="background:#f39c12;">💾 Enregistrer les modifications</button>
            <a href="rapports-activites.php" class="btn" style="background:#95a5a6;">Annuler</a>
        </div>
    </form>
</div>
<?php else: ?>
<!-- ===== FORMULAIRE AJOUT ===== -->
<div class="card">
    <h3>Rédiger un rapport</h3>
    <form method="POST" action="rapports-activites.php">
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
<?php endif; ?>

<div class="card">
    <h3>Mes rapports soumis</h3>
    <table>
        <thead>
            <tr>
                <th>Date soumission</th>
                <th>Praticien</th>
                <th>Date visite</th>
                <th>Produits</th>
                <th>Statut</th>
                <th>Bilan</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rapports as $r): ?>
            <?php $valide = ($r['statut'] ?? '') === 'Validé'; ?>
            <tr>
                <td><?= date(format: "d/m/Y", timestamp: strtotime(datetime: $r['date_creation'])) ?></td>
                <td><?= htmlspecialchars(string: $r['praticien_prenom'] . ' ' . $r['praticien_nom']) ?></td>
                <td><?= date(format: "d/m/Y", timestamp: strtotime(datetime: $r['date_visite'])) ?></td>
                <td><?= htmlspecialchars(string: $r['produits_noms'] ?? 'Aucun') ?></td>
                <td>
                    <?php if ($valide): ?>
                        <span style="color:green; font-weight:bold;">✅ Validé</span>
                    <?php else: ?>
                        <span style="color:orange;">⏳ En attente</span>
                    <?php endif; ?>
                </td>
                <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?= htmlspecialchars(string: substr(string: $r['bilan'], offset: 0, length: 60)) ?>...
                </td>
                <td style="white-space:nowrap;">
                    <?php if (!$valide): ?>
                        <a href="rapports-activites.php?edit_id=<?= $r['id'] ?>" class="btn" style="padding:4px 8px; font-size:0.8rem;">✏️ Modifier</a>
                        <button class="btn" style="background:#e74c3c; padding:4px 8px; font-size:0.8rem;"
                            onclick="if(confirm('Supprimer ce rapport ?')){
                                var f=document.createElement('form');
                                f.method='POST';
                                f.action='rapports-activites.php';
                                var i=document.createElement('input'); i.type='hidden'; i.name='id_rapport'; i.value='<?= $r['id'] ?>'; f.appendChild(i);
                                var b=document.createElement('input'); b.type='hidden'; b.name='supprimer_rapport'; b.value='1'; f.appendChild(b);
                                document.body.appendChild(f); f.submit();
                            }">🗑️ Supprimer</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>