<?php
require 'bd_connexion.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header(header: 'Location: index.php');
    exit();
}

require 'accueil.php';

$id_utilisateur = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$requeteStats = $pdo->prepare(query: "
    SELECT 
        SUM(CASE WHEN date_visite < CURDATE() THEN 1 ELSE 0 END) as visites_effectuees,
        SUM(CASE WHEN date_visite >= CURDATE() THEN 1 ELSE 0 END) as visites_a_venir,
        SUM(CASE WHEN MONTH(date_visite) = MONTH(CURRENT_DATE()) AND YEAR(date_visite) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as visites_du_mois
    FROM visites 
    WHERE id_utilisateur = ?
");
$requeteStats->execute(params: [$id_utilisateur]);
$stats = $requeteStats->fetch(mode: PDO::FETCH_ASSOC);

$visitesEffectuees = $stats['visites_effectuees'] ?? 0;
$visitesAVenir = $stats['visites_a_venir'] ?? 0;
$visitesDuMois = $stats['visites_du_mois'] ?? 0;

$objectifFixe = 20;
$pourcentage = $objectifFixe > 0 ? min(($visitesDuMois / $objectifFixe) * 100, 100) : 0;

if ($role == 'Admin' || $role == 'Responsable') {
    $nbPraticiens  = $pdo->query(query: "SELECT COUNT(*) FROM praticiens")->fetchColumn();
    $nbProduits    = $pdo->query(query: "SELECT COUNT(*) FROM produits")->fetchColumn();
    $nbVisiteurs   = $pdo->query(query: "SELECT COUNT(*) FROM utilisateurs WHERE role = 'Visiteur'")->fetchColumn();
}

if ($role == 'Delegue') {
    $stmtRapports = $pdo->prepare(query: "
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN r.statut IS NULL OR r.statut != 'Validé' THEN 1 ELSE 0 END) as en_attente
        FROM rapports r
        JOIN utilisateurs u ON r.id_utilisateur = u.id
        WHERE u.id_delegue = ?
    ");
    $stmtRapports->execute(params: [$id_utilisateur]);
    $statsRapports = $stmtRapports->fetch(mode: PDO::FETCH_ASSOC);

    $stmtEquipe = $pdo->prepare(query: "SELECT COUNT(*) FROM utilisateurs WHERE id_delegue = ? AND role = 'Visiteur'");
    $stmtEquipe->execute(params: [$id_utilisateur]);
    $nbEquipe = $stmtEquipe->fetchColumn();

    $stmtVisitesEquipe = $pdo->prepare(query: "
        SELECT COUNT(*) FROM visites v
        JOIN utilisateurs u ON v.id_utilisateur = u.id
        WHERE u.id_delegue = ? AND v.date_visite >= CURDATE()
    ");
    $stmtVisitesEquipe->execute(params: [$id_utilisateur]);
    $nbVisitesPlanifiees = $stmtVisitesEquipe->fetchColumn();
}
?>

<h2>Tableau de Bord</h2>

<p style="font-size: 1.1rem;">
    Bonjour <strong><?= htmlspecialchars(string: $_SESSION['user']['prenom']) ?> <?= htmlspecialchars(string: $_SESSION['user']['nom']) ?></strong>.
</p>
<p style="margin-bottom: 2rem; color: #666;">
    Vous êtes connecté en tant que : <span style="background: var(--secondary); color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.9rem;"><?= htmlspecialchars($role) ?></span>
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
    
    <div class="card">
        <h3 style="color: var(--secondary); font-size: 2.5rem; margin-bottom: 0.5rem;">
            <?= (int)$visitesEffectuees ?>
        </h3>
        <p style="color: #7f8c8d; font-weight: bold;">Visites effectuées</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Total historique</p>
    </div>

    <div class="card" style="border-left: 5px solid #f39c12;">
        <h3 style="color: #f39c12; font-size: 2.5rem; margin-bottom: 0.5rem;">
            <?= (int)$visitesAVenir ?>
        </h3>
        <p style="color: #7f8c8d; font-weight: bold;">À venir</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Planifiées dans le futur</p>
    </div>

    <div class="card" style="border-left: 5px solid #27ae60;">
        <h3 style="color: #27ae60; font-size: 2.5rem; margin-bottom: 0.5rem;">
            <?= number_format(num: $pourcentage, decimals: 0) ?>%
        </h3>
        <p style="color: #7f8c8d; font-weight: bold;">Objectif mensuel</p>
        <div style="background: #eee; height: 8px; border-radius: 4px; margin-top: 10px; overflow: hidden;">
            <div style="background: #27ae60; height: 100%; width: <?= $pourcentage ?>%;"></div>
        </div>
        <p style="font-size: 0.8rem; color: #bdc3c7; margin-top: 5px;">
            <?= (int)$visitesDuMois ?> visites sur <?= $objectifFixe ?>
        </p>
    </div>

    <?php if ($role == 'Admin' || $role == 'Responsable'): ?>
    <div class="card" style="border-left: 5px solid #9b59b6;">
        <h3 style="color: #9b59b6; font-size: 2.5rem; margin-bottom: 0.5rem;"><?= $nbPraticiens ?></h3>
        <p style="color: #7f8c8d; font-weight: bold;">Praticiens</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Enregistrés</p>
    </div>
    <div class="card" style="border-left: 5px solid #34495e;">
        <h3 style="color: #34495e; font-size: 2.5rem; margin-bottom: 0.5rem;"><?= $nbProduits ?></h3>
        <p style="color: #7f8c8d; font-weight: bold;">Produits</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Au catalogue</p>
    </div>
    <div class="card" style="border-left: 5px solid #3fa0df;">
        <h3 style="color: #3fa0df; font-size: 2.5rem; margin-bottom: 0.5rem;"><?= $nbVisiteurs ?></h3>
        <p style="color: #7f8c8d; font-weight: bold;">Visiteurs</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Comptes actifs</p>
    </div>
    <?php endif; ?>

    <?php if ($role == 'Delegue'): ?>
    <div class="card" style="border-left: 5px solid #3fa0df;">
        <h3 style="color: #3fa0df; font-size: 2.5rem; margin-bottom: 0.5rem;"><?= (int)$nbEquipe ?></h3>
        <p style="color: #7f8c8d; font-weight: bold;">Visiteurs dans l'équipe</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Sous ta responsabilité</p>
    </div>
    <div class="card" style="border-left: 5px solid #f39c12;">
        <h3 style="color: #f39c12; font-size: 2.5rem; margin-bottom: 0.5rem;"><?= (int)$nbVisitesPlanifiees ?></h3>
        <p style="color: #7f8c8d; font-weight: bold;">Visites planifiées</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">À venir pour l'équipe</p>
    </div>
    <div class="card" style="border-left: 5px solid <?= ($statsRapports['en_attente'] ?? 0) > 0 ? '#e74c3c' : '#27ae60' ?>;">
        <h3 style="color: <?= ($statsRapports['en_attente'] ?? 0) > 0 ? '#e74c3c' : '#27ae60' ?>; font-size: 2.5rem; margin-bottom: 0.5rem;">
            <?= (int)($statsRapports['en_attente'] ?? 0) ?>
        </h3>
        <p style="color: #7f8c8d; font-weight: bold;">Rapports à valider</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Sur <?= (int)($statsRapports['total'] ?? 0) ?> rapport(s) au total</p>
        <?php if (($statsRapports['en_attente'] ?? 0) > 0): ?>
            <a href="gestion-rapports.php" style="font-size:0.85rem; color:#e74c3c; font-weight:bold;">→ Valider maintenant</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

</body>
</html>