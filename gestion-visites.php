<?php
require 'bd_connexion.php';
require 'accueil.php';

$id_utilisateur = $_SESSION['user']['id'];

if (isset($_POST['delete_visite'])) {
    $requete = $pdo->prepare("DELETE FROM visites WHERE id = ?");
    $requete->execute([$_POST['id_visite']]);
    header("Location: gestion-visites.php");
    exit();
}

if ($role == 'Delegue') {
    if (isset($_POST['ajout_visite'])) {
        $sql = "INSERT INTO visites (id_utilisateur, praticien, date_visite, heure_visite, lieu, motif) VALUES (?, ?, ?, ?, ?, ?)";
        $requete = $pdo->prepare($sql);
        $requete->execute([$_POST['id_visiteur'], $_POST['praticien'], $_POST['date'], $_POST['heure'], $_POST['lieu'], $_POST['motif']]);
        header("Location: gestion-visites.php");
        exit();
    }
    $requete = $pdo->prepare("SELECT v.*, u.prenom, u.nom FROM visites v JOIN utilisateurs u ON v.id_utilisateur = u.id WHERE u.id_delegue = ? ORDER BY v.date_visite DESC");
    $requete->execute([$id_utilisateur]);
    $visites_equipe = $requete->fetchAll();

    $requeteVisiteurs = $pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE id_delegue = ? AND role = 'Visiteur'");
    $requeteVisiteurs->execute([$id_utilisateur]);
    $equipe = $requeteVisiteurs->fetchAll();
} else {
    $requeteFutur = $pdo->prepare("SELECT * FROM visites WHERE id_utilisateur = ? AND date_visite >= CURDATE() ORDER BY date_visite ASC");
    $requeteFutur->execute([$id_utilisateur]);
    $visites_futures = $requeteFutur->fetchAll();

    $requetePasse = $pdo->prepare("SELECT * FROM visites WHERE id_utilisateur = ? AND date_visite < CURDATE() ORDER BY date_visite DESC");
    $requetePasse->execute([$id_utilisateur]);
    $visites_passees = $requetePasse->fetchAll();
}
?>

<h2>Gestion des Visites</h2>

<?php if ($role == 'Delegue'): ?>
    <div class="card">
        <h3>Planifier une visite</h3>
        <form method="POST">
            <label>Assigner à</label>
            <select name="id_visiteur" required>
                <option value="">-- Choisir un visiteur --</option>
                <?php foreach ($equipe as $visiteur): ?>
                    <option value="<?= $visiteur['id'] ?>"><?= htmlspecialchars($visiteur['prenom'] . ' ' . $visiteur['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        <div>
            <label>Praticien</label>
            <select name="id_praticien" required>
                <option value="">-- Choisir un praticien --</option>
                <?php foreach ($equipe as $praticien): ?>
                    <option value="<?= $praticien['id'] ?>"><?= htmlspecialchars($praticien['prenom'] . ' ' . $praticien['nom']) ?></option>
                <?php endforeach; ?>
                </div>
                <div style="flex:1"><label>Date</label><input type="date" name="date" required></div>
                <div style="flex:1"><label>Heure</label><input type="time" name="heure" required></div>
            <label>Lieu</label>
            <input type="text" name="lieu" required>
            <label>Motif</label>
            <input type="text" name="motif" required>
            <button type="submit" name="ajout_visite" class="btn">Soumettre</button>
        </form>
    </div>

    <div class="card">
        <h3>Visites de l'équipe</h3>
        <table>
            <thead><tr><th>Visiteur</th><th>Praticien</th><th>Date</th><th>Lieu</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($visites_equipe as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['prenom']) ?></td>
                    <td><?= htmlspecialchars($v['praticien']) ?></td>
                    <td><?= date("d/m/Y", strtotime($v['date_visite'])) ?></td>
                    <td><?= htmlspecialchars($v['lieu']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_visite" value="<?= $v['id'] ?>">
                            <button type="submit" name="delete_visite" class="btn" style="background:#e74c3c;" onclick="return confirm('Sûr ?');">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="card">
        <h3>Prochaines visites</h3>
        <table>
            <thead><tr><th>Praticien</th><th>Date</th><th>Lieu</th><th>Motif</th><th>Statut</th></tr></thead>
            <tbody>
                <?php if (empty($visites_futures)): ?>
                    <tr><td colspan="5">Aucune visite à venir.</td></tr>
                <?php else: ?>
                    <?php foreach ($visites_futures as $v): ?>
                    <tr>
                        <td><?= htmlspecialchars($v['praticien']) ?></td>
                        <td><?= date("d/m/Y", strtotime($v['date_visite'])) ?> à <?= htmlspecialchars($v['heure_visite']) ?></td>
                        <td><?= htmlspecialchars($v['lieu']) ?></td>
                        <td><?= htmlspecialchars($v['motif']) ?></td>
                        <td><?= htmlspecialchars($v['statut'] ?? 'Planifiée') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Visites passées</h3>
        <table>
            <thead><tr><th>Praticien</th><th>Date</th><th>Lieu</th><th>Motif</th></tr></thead>
            <tbody>
                <?php foreach ($visites_passees as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['praticien']) ?></td>
                    <td><?= date("d/m/Y", strtotime($v['date_visite'])) ?></td>
                    <td><?= htmlspecialchars($v['lieu']) ?></td>
                    <td><?= htmlspecialchars($v['motif']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

</body>
</html>