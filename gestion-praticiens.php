<?php
// ════════════════════════════════════════════════
//  CONTRÔLEUR — Gestion des praticiens
//  Pattern MVC : ce fichier orchestre Model + Vue
// ════════════════════════════════════════════════
require 'bd_connexion.php';
require 'models/PraticienModel.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

// ── MODEL ──────────────────────────────────────
$model       = new PraticienModel($pdo);
$success_msg = '';

// ── CONTROLLER : traitement des actions POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

if (isset($_POST['supprimer_praticien'])) {
    $model->delete((int)$_POST['id_praticien']);
    $success_msg = "Praticien supprimé.";
}

if (isset($_POST['modifier_praticien'])) {
    $model->update((int)$_POST['id_praticien'], $_POST);
    $success_msg = "Praticien modifié.";
}

if (isset($_POST['ajout_praticien'])) {
    $model->create($_POST);
    $success_msg = "Praticien ajouté avec succès !";
}

// ── MODEL : récupération des données pour la vue
$praticiens   = $model->findAll();
$types        = $pdo->query("SELECT * FROM type_praticiens ORDER BY libelle")->fetchAll();
$regions_list = $pdo->query("SELECT * FROM regions ORDER BY nom")->fetchAll();

// ── VUE : affichage HTML ci-dessous ────────────
?>

<h2>Gestion des Praticiens</h2>

<div class="card">
    <h3>Ajouter un praticien</h3>
    <?php if ($success_msg): ?><p style="color:green; margin-bottom:1rem;"><?= htmlspecialchars($success_msg) ?></p><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <div style="flex:1; min-width:150px;"><label>Nom *</label><input type="text" name="nom" required></div>
            <div style="flex:1; min-width:150px;"><label>Prénom *</label><input type="text" name="prenom" required></div>
            <div style="flex:1; min-width:150px;">
                <label>Type de praticien</label>
                <select name="id_type">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1; min-width:150px;">
                <label>Région</label>
                <select name="region">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($regions_list as $r): ?>
                        <option value="<?= htmlspecialchars($r['nom']) ?>"><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;">
            <div style="flex:1; min-width:150px;"><label>Adresse</label><input type="text" name="adresse"></div>
            <div style="flex:1; min-width:150px;"><label>Email</label><input type="email" name="email"></div>
            <div style="flex:1; min-width:150px;"><label>Téléphone</label><input type="text" name="telephone"></div>
        </div>
        <button type="submit" name="ajout_praticien" class="btn" style="margin-top:12px;">Ajouter</button>
    </form>
</div>

<div class="card">
    <h3>Liste des praticiens</h3>
    <table>
        <thead>
            <tr>
                <th>Nom</th><th>Prénom</th><th>Type</th><th>Région</th>
                <th>Email</th><th>Téléphone</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($praticiens as $p): ?>
            <tr>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <td><input type="text" name="nom" value="<?= htmlspecialchars($p['nom']) ?>"></td>
                    <td><input type="text" name="prenom" value="<?= htmlspecialchars($p['prenom']) ?>"></td>
                    <td>
                        <select name="id_type">
                            <option value="">--</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $p['id_type'] == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="region">
                            <option value="">--</option>
                            <?php foreach ($regions_list as $r): ?>
                                <option value="<?= htmlspecialchars($r['nom']) ?>" <?= $p['region'] == $r['nom'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="email" name="email" value="<?= htmlspecialchars($p['email'] ?? '') ?>"></td>
                    <td><input type="text" name="telephone" value="<?= htmlspecialchars($p['telephone'] ?? '') ?>"></td>
                    <td style="white-space:nowrap;">
                        <input type="hidden" name="adresse" value="<?= htmlspecialchars($p['adresse'] ?? '') ?>">
                        <input type="hidden" name="id_praticien" value="<?= $p['id'] ?>">
                        <button type="submit" name="modifier_praticien" class="btn" style="padding:4px 8px; font-size:0.8rem;">✔ Modif.</button>
                        <button type="submit" name="supprimer_praticien" class="btn" style="background:#e74c3c; padding:4px 8px; font-size:0.8rem;" onclick="return confirm('Supprimer ?')">✖</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
