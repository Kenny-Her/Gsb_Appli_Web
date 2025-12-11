<?php
require 'bd_connexion.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit();
}

require 'accueil.php';

$id_utilisateur = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

$requeteStats = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN date_visite < CURDATE() THEN 1 ELSE 0 END) as visites_effectuees,
        SUM(CASE WHEN date_visite >= CURDATE() THEN 1 ELSE 0 END) as visites_a_venir,
        SUM(CASE WHEN MONTH(date_visite) = MONTH(CURRENT_DATE()) AND YEAR(date_visite) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as visites_du_mois
    FROM visites 
    WHERE id_utilisateur = ?
");
$requeteStats->execute([$id_utilisateur]);
$stats = $requeteStats->fetch(PDO::FETCH_ASSOC);

$visitesEffectuees = $stats['visites_effectuees'] ?? 0;
$visitesAVenir = $stats['visites_a_venir'] ?? 0;
$visitesDuMois = $stats['visites_du_mois'] ?? 0;

$objectifFixe = 20; 
$pourcentage = ($visitesDuMois / $objectifFixe) * 100;
if($pourcentage > 100) $pourcentage = 100;
?>

<h2>Tableau de Bord</h2>

<p style="font-size: 1.1rem;">
    Bonjour <strong><?= htmlspecialchars($_SESSION['user']['prenom']) ?> <?= htmlspecialchars($_SESSION['user']['nom']) ?></strong>.
</p>
<p style="margin-bottom: 2rem; color: #666;">
    Vous êtes connecté en tant que : <span style="background: var(--secondary); color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.9rem;"><?= htmlspecialchars($role) ?></span>
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
    
    <!-- <div class="card">
        <h3 style="color: var(--secondary); font-size: 2.5rem; margin-bottom: 0.5rem;">
            <?= (int)$visitesEffectuees ?>
        </h3>
        <p style="color: #7f8c8d; font-weight: bold;">Visites effectuées</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Total historique</p>
    </div> -->

    <!-- <div class="card" style="border-left: 5px solid #f39c12;">
        <h3 style="color: #f39c12; font-size: 2.5rem; margin-bottom: 0.5rem;">
            <?= (int)$visitesAVenir ?>
        </h3>
        <p style="color: #7f8c8d; font-weight: bold;">À venir</p>
        <p style="font-size: 0.8rem; color: #bdc3c7;">Planifiées dans le futur</p>
    </div> -->
<!-- 
    <div class="card" style="border-left: 5px solid #27ae60;">
        <h3 style="color: #27ae60; font-size: 2.5rem; margin-bottom: 0.5rem;">
            <?= number_format($pourcentage, 0) ?>%
        </h3>
        <p style="color: #7f8c8d; font-weight: bold;">Objectif mensuel</p>
        <div style="background: #eee; height: 8px; border-radius: 4px; margin-top: 10px; overflow: hidden;">
            <div style="background: #27ae60; height: 100%; width: <?= $pourcentage ?>%;"></div>
        </div>
        <p style="font-size: 0.8rem; color: #bdc3c7; margin-top: 5px;">
            <?= (int)$visitesDuMois ?> visites sur <?= $objectifFixe ?>
        </p>
    </div>

</div> -->

</body>
</html>