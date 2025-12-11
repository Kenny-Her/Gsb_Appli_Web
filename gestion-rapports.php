<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Delegue') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$id_delegue = $_SESSION['user']['id'];

$requete = $pdo->prepare("
    SELECT r.*, u.prenom, u.nom, p.nom as praticien_nom
    FROM rapports r
    JOIN utilisateurs u ON r.id_utilisateur = u.id
    JOIN praticiens p ON r.id_praticien = p.id
    WHERE u.id_delegue = ?
    ORDER BY r.date_creation DESC
");
$requete->execute([$id_delegue]);
$rapports_equipe = $requete->fetchAll();
?>

<h2>Rapports de l'équipe</h2>

<div class="card">
    <table>
        <thead><tr><th>Visiteur</th><th>Praticien</th><th>Date Visite</th><th>Bilan</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($rapports_equipe as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['prenom']) ?></td>
                <td><?= htmlspecialchars($r['praticien_nom']) ?></td>
                <td><?= date("d/m/Y", strtotime($r['date_visite'])) ?></td>
                <td><?= htmlspecialchars(substr($r['bilan'], 0, 50)) ?>...</td>
                <td>Soumis</td>
                <td><a href="#" class="btn">Détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>