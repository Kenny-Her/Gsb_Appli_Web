<?php
require 'bd_connexion.php';
require 'accueil.php';
$u = $_SESSION['user'];
?>

<h2>Profil Utilisateur</h2>

<div class="card">
    <label>Nom</label>
    <input type="text" value="<?= htmlspecialchars($u['nom']) ?>" readonly>
    
    <label>Prénom</label>
    <input type="text" value="<?= htmlspecialchars($u['prenom']) ?>" readonly>
    
    <label>Email</label>
    <input type="text" value="<?= htmlspecialchars($u['email']) ?>" readonly>
    
    <label>Mot de passe</label>
    <input type="password" value="********">
    
    <button class="btn">Sauvegarder les modifications</button>
</div>

</body>
</html>