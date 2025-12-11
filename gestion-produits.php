<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$success_msg = '';

if (isset($_POST['ajout_produit'])) {
    $sql = "INSERT INTO produits (nom) VALUES (?)";
    $requete = $pdo->prepare($sql);
    $requete->execute([$_POST['nom']]);
    $success_msg = "Produit ajouté avec succès !";
}

$produits = $pdo->query("SELECT * FROM produits ORDER BY nom")->fetchAll();
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
        <thead><tr><th>Nom du produit</th></tr></thead>
        <tbody>
            <?php foreach ($produits as $p): ?>
            <tr><td><?= htmlspecialchars($p['nom']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>