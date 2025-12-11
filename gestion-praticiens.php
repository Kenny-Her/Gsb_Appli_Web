<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$success_msg = '';

if (isset($_POST['ajout_praticien'])) {
    $sql = "INSERT INTO praticiens (nom, prenom ) VALUES (?, ?, ?)";
    $requete = $pdo->prepare($sql);
    $requete->execute([
        $_POST['nom'],
        $_POST['prenom'],
    ]);
    $success_msg = "Praticien ajouté avec succès !";
}

$praticiens = $pdo->query("SELECT * FROM praticiens ORDER BY nom, prenom")->fetchAll();
?>

<h2>Gestion des Praticiens</h2>

<div class="card">
    <h3>Ajouter un praticien</h3>
    <?php if ($success_msg): ?><p style="color: green; margin-bottom: 1rem;"><?= $success_msg ?></p><?php endif; ?>
    <form method="POST">
        <div style="display:flex; gap:10px;">
            <div style="flex:1"><label>Nom</label><input type="text" name="nom" required></div>
            <div style="flex:1"><label>Prénom</label><input type="text" name="prenom" required></div>
        </div>
        <button type="submit" name="ajout_praticien" class="btn">Ajouter</button>
    </form>
</div>

<div class="card">
    <h3>Liste des praticiens</h3>
    <table>
        <thead><tr><th>Nom</th><th>Prénom</th></tr></thead>
        <tbody>
            <?php foreach ($praticiens as $p): ?>
            <tr><td><?= htmlspecialchars($p['nom']) ?></td><td><?= htmlspecialchars($p['prenom']) ?></td><td><?= htmlspecialchars($p['specialite']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>