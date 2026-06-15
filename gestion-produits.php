<?php
// ════════════════════════════════════════════════
//  CONTRÔLEUR — Gestion des produits
//  Pattern MVC : ce fichier orchestre Model + Vue
// ════════════════════════════════════════════════
require 'bd_connexion.php';
require 'models/ProduitModel.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

// ── MODEL ──────────────────────────────────────
$model       = new ProduitModel($pdo);
$success_msg = '';

// ── CONTROLLER : traitement des actions POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

if (isset($_POST['supprimer_produit'])) {
    $model->delete((int)$_POST['id_produit']);
    $success_msg = "Produit supprimé.";
}

if (isset($_POST['modifier_produit'])) {
    $id_famille = !empty($_POST['id_famille']) ? (int)$_POST['id_famille'] : null;
    $model->update((int)$_POST['id_produit'], $_POST['nom'], $id_famille);
    $success_msg = "Produit modifié.";
}

if (isset($_POST['ajout_produit'])) {
    $id_famille = !empty($_POST['id_famille']) ? (int)$_POST['id_famille'] : null;
    $model->create($_POST['nom'], $id_famille);
    $success_msg = "Produit ajouté avec succès !";
}

// ── MODEL : récupération des données pour la vue
$produits = $model->findAll();
$familles = $pdo->query("SELECT * FROM familles ORDER BY libelle")->fetchAll();

// ── VUE : affichage HTML ci-dessous ────────────
?>

<h2>Gestion des Produits</h2>

<div class="card">
    <h3>Ajouter un produit</h3>
    <?php if ($success_msg): ?><p style="color:green; margin-bottom:1rem;"><?= htmlspecialchars($success_msg) ?></p><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div style="display:flex; gap:10px; align-items:flex-end;">
            <div style="flex:2;">
                <label>Nom du produit *</label>
                <input type="text" name="nom" required>
            </div>
            <div style="flex:2;">
                <label>Famille</label>
                <select name="id_famille">
                    <option value="">-- Choisir une famille --</option>
                    <?php foreach ($familles as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" name="ajout_produit" class="btn">Ajouter</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h3>Liste des produits</h3>
    <table>
        <thead>
            <tr><th>Nom du produit</th><th>Famille</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($produits as $p): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <td><input type="text" name="nom" value="<?= htmlspecialchars($p['nom']) ?>" style="width:100%;"></td>
                    <td>
                        <select name="id_famille">
                            <option value="">-- Aucune --</option>
                            <?php foreach ($familles as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= $p['id_famille'] == $f['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="white-space:nowrap; width:130px;">
                        <input type="hidden" name="id_produit" value="<?= $p['id'] ?>">
                        <button type="submit" name="modifier_produit" class="btn" style="padding:4px 8px; font-size:0.8rem;">✔ OK</button>
                        <button type="submit" name="supprimer_produit" class="btn" style="background:#e74c3c; padding:4px 8px; font-size:0.8rem;" onclick="return confirm('Supprimer ?')">✖</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
