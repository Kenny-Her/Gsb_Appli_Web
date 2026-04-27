<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$success_msg = '';

if (isset($_POST['supprimer_produit'])) {
    $pdo->prepare("DELETE FROM produits WHERE id = ?")->execute([$_POST['id_produit']]);
    $success_msg = "Produit supprimé.";
}

if (isset($_POST['modifier_produit'])) {
    $id_famille = !empty($_POST['id_famille']) ? (int)$_POST['id_famille'] : null;
    $pdo->prepare("UPDATE produits SET nom=?, id_famille=? WHERE id=?")
        ->execute([$_POST['nom'], $id_famille, $_POST['id_produit']]);
    $success_msg = "Produit modifié.";
}

if (isset($_POST['ajout_produit'])) {
    $id_famille = !empty($_POST['id_famille']) ? (int)$_POST['id_famille'] : null;
    $pdo->prepare("INSERT INTO produits (nom, id_famille) VALUES (?, ?)")
        ->execute([$_POST['nom'], $id_famille]);
    $success_msg = "Produit ajouté avec succès !";
}

$produits = $pdo->query("
    SELECT p.*, f.libelle as famille_libelle
    FROM produits p
    LEFT JOIN familles f ON p.id_famille = f.id
    ORDER BY f.libelle, p.nom
")->fetchAll();

$familles = $pdo->query("SELECT * FROM familles ORDER BY libelle")->fetchAll();
?>

<h2>Gestion des Produits</h2>

<div class="card">
    <h3>Ajouter un produit</h3>
    <?php if ($success_msg): ?><p style="color:green; margin-bottom:1rem;"><?= $success_msg ?></p><?php endif; ?>
    <form method="POST">
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
