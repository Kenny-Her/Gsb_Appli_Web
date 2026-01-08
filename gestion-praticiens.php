<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$success_msg = '';

if (isset($_POST['supprimer_praticien'])) {
    $req = $pdo->prepare(query: "DELETE FROM praticiens WHERE id = ?");
    $req->execute(params: [$_POST['id_praticien']]);
    $success_msg = "Praticien supprimé.";
}

if (isset($_POST['modifier_praticien'])) {
    $req = $pdo->prepare(query: "UPDATE praticiens SET nom = ?, prenom = ?, specialite = ? WHERE id = ?");
    $req->execute(params: [$_POST['nom'], $_POST['prenom'], $_POST['specialite'], $_POST['id_praticien']]);
    $success_msg = "Praticien modifié.";
}

if (isset($_POST['ajout_praticien'])) {
    $sql = "INSERT INTO praticiens (nom, prenom, specialite) VALUES (?, ?, ?)";
    $requete = $pdo->prepare(query: $sql);
    $requete->execute(params: [
        $_POST['nom'],
        $_POST['prenom'],
        $_POST['specialite']
    ]);
    $success_msg = "Praticien ajouté avec succès !";
}

$praticiens = $pdo->query(query: "SELECT * FROM praticiens ORDER BY nom, prenom")->fetchAll();
?>

<h2>Gestion des Praticiens</h2>

<div class="card">
    <h3>Ajouter un praticien</h3>
    <?php if ($success_msg): ?><p style="color: green; margin-bottom: 1rem;"><?= $success_msg ?></p><?php endif; ?>
    <form method="POST">
        <div style="display:flex; gap:10px;">
            <div style="flex:1"><label>Nom</label><input type="text" name="nom" placeholder="Nom" required></div>
            <div style="flex:1"><label>Prénom</label><input type="text" name="prenom" placeholder="Prénom" required></div>
            <div style="flex:1"><label>Spécialité</label><input type="text" name="specialite" placeholder="Ex: Cardiologue"></div>
        </div>
        <button type="submit" name="ajout_praticien" class="btn">Ajouter</button>
    </form>
</div>

<div class="card">
    <h3>Liste des praticiens</h3>
    <table>
        <thead><tr><th>Nom</th><th>Prénom</th><th>Spécialité</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($praticiens as $p): ?>
            <tr>
                <form method="POST">
                    <td><input type="text" name="nom" value="<?= htmlspecialchars(string: $p['nom']) ?>"></td>
                    <td><input type="text" name="prenom" value="<?= htmlspecialchars(string: $p['prenom']) ?>"></td>
                    <td><input type="text" name="specialite" value="<?= htmlspecialchars(string: $p['specialite'] ?? '') ?>"></td>
                    <td>
                        <input type="hidden" name="id_praticien" value="<?= $p['id'] ?>">
                        <button type="submit" name="modifier_praticien" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Modif.</button>
                        <button type="submit" name="supprimer_praticien" class="btn" style="background: #e74c3c; padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Supprimer ?')">X</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>