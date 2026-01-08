<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$success_msg = '';

if (isset($_POST['supprimer_produit'])) {
    $req = $pdo->prepare(query: "DELETE FROM produits WHERE id = ?");
    $req->execute(params: [$_POST['id_produit']]);
    $success_msg = "Produit supprimé.";
}

if (isset($_POST['modifier_produit'])) {
    $req = $pdo->prepare(query: "UPDATE produits SET nom = ? WHERE id = ?");
    $req->execute(params: [$_POST['nom'], $_POST['id_produit']]);
    $success_msg = "Produit modifié.";
}

if (isset($_POST['ajout_produit'])) {
    $sql = "INSERT INTO produits (nom) VALUES (?)";
    $requete = $pdo->prepare(query: $sql);
    $requete->execute(params: [$_POST['nom']]);
    $success_msg = "Produit ajouté avec succès !";
}

$produits = $pdo->query(query: "SELECT * FROM produits ORDER BY nom")->fetchAll();
?>

<h2>Gestion des Produits</h2>

<div class="card">
    <h3>Ajouter un produit</h3>
    <?php if ($success_msg): ?><p style="color: green; margin-bottom: 1rem;"><?= $success_msg ?></p><?php endif; ?>
    <form method="POST">
        <label>Nom du produit</label>
        <input type="text" name="nom" required>
        <button type="submit" name="ajout_produit" class="btn">Ajouter</button>
    </form>
</div>

<div class="card">
    <h3>Liste des produits</h3>
    <table>
        <thead><tr><th>Nom du produit</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($produits as $p): ?>
            <tr>
                <form method="POST">
                    <td><input type="text" name="nom" value="<?= htmlspecialchars($p['nom']) ?>" style="width: 100%;"></td>
                    <td style="width: 150px;">
                        <input type="hidden" name="id_produit" value="<?= $p['id'] ?>">
                        <button type="submit" name="modifier_produit" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">OK</button>
                        <button type="submit" name="supprimer_produit" class="btn" style="background: #e74c3c; padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Supprimer ?')">X</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>