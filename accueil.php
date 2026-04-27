<?php
if (!isset($_SESSION['user']) && basename($_SERVER['PHP_SELF']) != 'index.php') {
    header(header: 'Location: index.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? 'Visiteur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GSB App - <?= $role ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if (isset($_SESSION['user'])): ?>
<nav class="sidebar">
    <div class="sidebar-logo">
        <img src="assets/Gsb_Logo.png" alt="Logo GSB" style="width: 100px;">
    </div>
    
    <div class="user-info">
        <?= htmlspecialchars(string: $_SESSION['user']['prenom']) ?><br>
        <span style="background: rgba(255,255,255,0.2); padding: 2px 5px; border-radius: 3px; display: inline-block; margin-top: 5px;">
            <?= $role ?>
        </span>
    </div>

    <ul class="nav-links">
        <li><a href="tableau-de-bord.php">Tableau de bord</a></li>

        <?php if ($role == 'Visiteur'): ?>
            <li><a href="gestion-visites.php">Mes Visites</a></li>
            <li><a href="rapports-activites.php">Rédiger Rapport</a></li>
        <?php endif; ?>

        <?php if ($role == 'Delegue'): ?>
            <li><a href="gestion-visites.php">Gérer les Visites</a></li>
            <li><a href="gestion-rapports.php">Valider les Rapports</a></li>
        <?php endif; ?>

        <?php if ($role == 'Responsable'): ?>
            <li><a href="gestion-praticiens.php">Gestion Praticiens</a></li>
            <li><a href="gestion-produits.php">Gestion Produits</a></li>
            <li><a href="gestion-regions.php">Gestion Régions</a></li>
            <li><a href="inscription.php">Créer un compte</a></li>
        <?php endif; ?>

        <?php if ($role == 'Admin'): ?>
            <li><a href="gestion-praticiens.php">Gestion Praticiens</a></li>
            <li><a href="gestion-produits.php">Gestion Produits</a></li>
            <li><a href="gestion-regions.php">Gestion Régions</a></li>
            <li><a href="inscription.php">Créer un compte</a></li>
        <?php endif; ?>

        <li><a href="profil-utilisateur.php">Profil Utilisateur</a></li>
        <li><a href="index.php?logout=1" class="logout-link">Déconnexion</a></li>
    </ul>
</nav>
<div class="main-content">
<?php endif; ?>