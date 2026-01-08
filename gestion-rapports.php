<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Delegue') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$id_delegue = $_SESSION['user']['id'];

if (isset($_POST['valider_rapport'])) {
    $req = $pdo->prepare(query: "UPDATE rapports SET statut = 'Validé' WHERE id = ?");
    $req->execute(params: [$_POST['id_rapport']]);
    $msg = "Rapport validé.";
}

$requete = $pdo->prepare(query: "
    SELECT r.*, u.prenom, u.nom, p.nom as praticien_nom
    FROM rapports r
    JOIN utilisateurs u ON r.id_utilisateur = u.id
    JOIN praticiens p ON r.id_praticien = p.id
    WHERE u.id_delegue = ?
    ORDER BY r.date_creation DESC
");
$requete->execute(params: [$id_delegue]);
$rapports_equipe = $requete->fetchAll();
$details = null;
$produits_rapport = [];
if (isset($_GET['id_details'])) {
    $stmt = $pdo->prepare(query: "SELECT r.*, p.nom as p_nom, p.prenom as p_prenom, u.nom as u_nom, u.prenom as u_prenom FROM rapports r JOIN praticiens p ON r.id_praticien = p.id JOIN utilisateurs u ON r.id_utilisateur = u.id WHERE r.id = ?");
    $stmt->execute(params: [$_GET['id_details']]);
    $details = $stmt->fetch();

    $stmtProd = $pdo->prepare(query: "SELECT p.nom FROM produits p JOIN rapport_produits rp ON p.id = rp.id_produit WHERE rp.id_rapport = ?");
    $stmtProd->execute(params: [$_GET['id_details']]);
    $produits_rapport = $stmtProd->fetchAll(PDO::FETCH_COLUMN);
}
?>

<h2>Rapports de l'équipe</h2>

<?php if ($details): ?>
<div class="card" style="border-left: 5px solid #3498db;">
    <h3>Détails du rapport #<?= $details['id'] ?></h3>
    <p><strong>Visiteur :</strong> <?= htmlspecialchars(string: $details['u_prenom'] . ' ' . $details['u_nom']) ?></p>
    <p><strong>Praticien :</strong> <?= htmlspecialchars(string: $details['p_prenom'] . ' ' . $details['p_nom']) ?></p>
    <p><strong>Date visite :</strong> <?= date(format: "d/m/Y", timestamp: strtotime(datetime: $details['date_visite'])) ?></p>
    <p><strong>Bilan :</strong> <br> <?= nl2br(string: htmlspecialchars(string: $details['bilan'])) ?></p>
    <p><strong>Produits présentés :</strong> 
        <?= empty($produits_rapport) ? 'Aucun' : htmlspecialchars(string: implode(separator: ', ', array: $produits_rapport)) ?>
    </p>
    
    <?php if (($details['statut'] ?? 'Soumis') != 'Validé'): ?>
    <form method="POST">
        <input type="hidden" name="id_rapport" value="<?= $details['id'] ?>">
        <button type="submit" name="valider_rapport" class="btn" style="background:#27ae60;">Valider ce rapport</button>
    </form>
    <?php else: ?>
        <p style="color:green; font-weight:bold;">Ce rapport est validé.</p>
    <?php endif; ?>
    <br><a href="gestion-rapports.php">Fermer les détails</a>
</div>
<?php endif; ?>

<div class="card">
    <table>
        <thead><tr><th>Visiteur</th><th>Praticien</th><th>Date Visite</th><th>Bilan</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($rapports_equipe as $r): ?>
            <tr>
                <td><?= htmlspecialchars(string: $r['prenom']) ?></td>
                <td><?= htmlspecialchars(string: $r['praticien_nom']) ?></td>
                <td><?= date(format: "d/m/Y", timestamp: strtotime(datetime: $r['date_visite'])) ?></td>
                <td><?= htmlspecialchars(string: substr(string: $r['bilan'], offset: 0, length: 50)) ?>...</td>
                <td>
                    <?php if(($r['statut'] ?? '') == 'Validé'): ?>
                        <span style="color:green;">Validé</span>
                    <?php else: ?>
                        <span style="color:orange;">Soumis</span>
                    <?php endif; ?>
                </td>
                <td><a href="?id_details=<?= $r['id'] ?>" class="btn">Détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>