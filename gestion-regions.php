<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Responsable' && $role != 'Admin') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$success_msg = '';

// Affecter un utilisateur à une région
if (isset($_POST['affecter_region'])) {
    $pdo->prepare("UPDATE utilisateurs SET id_region = ? WHERE id = ?")
        ->execute([
            !empty($_POST['id_region']) ? (int)$_POST['id_region'] : null,
            (int)$_POST['id_utilisateur']
        ]);
    $success_msg = "Affectation mise à jour.";
}

$regions    = $pdo->query("SELECT * FROM regions ORDER BY nom")->fetchAll();
$utilisateurs = $pdo->query("
    SELECT u.*, r.nom as region_nom
    FROM utilisateurs u
    LEFT JOIN regions r ON u.id_region = r.id
    WHERE u.role IN ('Visiteur', 'Delegue', 'Responsable')
    ORDER BY u.role, u.nom, u.prenom
")->fetchAll();

// Regrouper par région pour les stats
$stats = [];
foreach ($regions as $reg) {
    $stmt = $pdo->prepare("
        SELECT role, COUNT(*) as nb
        FROM utilisateurs
        WHERE id_region = ?
        GROUP BY role
    ");
    $stmt->execute([$reg['id']]);
    $stats[$reg['id']] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
$sansRegion = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE id_region IS NULL AND role IN ('Visiteur','Delegue','Responsable')")->fetchColumn();
?>

<h2>Gestion des Régions</h2>

<?php if ($success_msg): ?>
    <p style="color:green; padding:0.5rem 1rem; background:#eaffea; border-left:4px solid green; margin-bottom:1rem;">
        <?= $success_msg ?>
    </p>
<?php endif; ?>

<!-- Cartes statistiques par région -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <?php
    $couleurs = ['Nord' => '#3FA0DF', 'Sud' => '#27AE60', 'Est' => '#F39C12', 'Ouest' => '#9B59B6'];
    foreach ($regions as $reg):
        $couleur = $couleurs[$reg['nom']] ?? '#263A4D';
        $nbVisiteurs = $stats[$reg['id']]['Visiteur'] ?? 0;
        $nbDelegues  = $stats[$reg['id']]['Delegue']  ?? 0;
        $nbResp      = $stats[$reg['id']]['Responsable'] ?? 0;
    ?>
    <div class="card" style="border-left: 5px solid <?= $couleur ?>;">
        <h3 style="color:<?= $couleur ?>; font-size:1.3rem; margin-bottom:0.5rem;">
            <?= htmlspecialchars($reg['nom']) ?>
        </h3>
        <p style="font-size:0.85rem; color:#555; margin:2px 0;">👤 <?= $nbVisiteurs ?> visiteur(s)</p>
        <p style="font-size:0.85rem; color:#555; margin:2px 0;">🧑‍💼 <?= $nbDelegues ?> délégué(s)</p>
        <p style="font-size:0.85rem; color:#555; margin:2px 0;">🏢 <?= $nbResp ?> responsable(s)</p>
    </div>
    <?php endforeach; ?>
    <div class="card" style="border-left: 5px solid #e74c3c;">
        <h3 style="color:#e74c3c; font-size:1.3rem; margin-bottom:0.5rem;">Non affectés</h3>
        <p style="font-size:0.85rem; color:#555;"><?= $sansRegion ?> utilisateur(s) sans région</p>
    </div>
</div>

<!-- Tableau d'affectation -->
<div class="card">
    <h3>Affecter les utilisateurs à une région</h3>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Rôle</th>
                <th>Région actuelle</th>
                <th>Changer la région</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $roleColors = [
                'Visiteur'    => '#3FA0DF',
                'Delegue'     => '#F39C12',
                'Responsable' => '#9B59B6',
            ];
            foreach ($utilisateurs as $u):
                $couleurRole = $roleColors[$u['role']] ?? '#263A4D';
            ?>
            <tr>
                <td><?= htmlspecialchars($u['nom']) ?></td>
                <td><?= htmlspecialchars($u['prenom']) ?></td>
                <td>
                    <span style="background:<?= $couleurRole ?>; color:white; padding:2px 8px; border-radius:4px; font-size:0.8rem;">
                        <?= htmlspecialchars($u['role']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($u['region_nom']): ?>
                        <strong><?= htmlspecialchars($u['region_nom']) ?></strong>
                    <?php else: ?>
                        <span style="color:#999; font-style:italic;">Non affecté</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:flex; gap:6px; align-items:center;">
                        <input type="hidden" name="id_utilisateur" value="<?= $u['id'] ?>">
                        <select name="id_region" style="padding:4px;">
                            <option value="">-- Aucune --</option>
                            <?php foreach ($regions as $reg): ?>
                                <option value="<?= $reg['id'] ?>" <?= $u['id_region'] == $reg['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($reg['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="affecter_region" class="btn" style="padding:4px 10px; font-size:0.8rem;">
                            ✔ Affecter
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
