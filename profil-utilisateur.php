<?php
require 'bd_connexion.php';
require 'accueil.php';
$u = $_SESSION['user'];
$msg = '';

if (isset($_POST['update_profile'])) {
    if (!empty($_POST['new_mdp'])) {
        $hashed_mdp = password_hash(password: $_POST['new_mdp'], algo: PASSWORD_BCRYPT);
        $req = $pdo->prepare(query: "UPDATE utilisateurs SET mdp = ? WHERE id = ?");
        $req->execute(params: [$hashed_mdp, $u['id']]);
        $msg = "Mot de passe mis à jour avec succès.";
    }
}
?>

<h2>Profil Utilisateur</h2>

<div class="card">
    <?php if($msg): ?><p style="color:green;"><?= $msg ?></p><?php endif; ?>
    <form method="POST">
    <label>Nom</label>
    <input type="text" value="<?= htmlspecialchars(string: $u['nom']) ?>" readonly>
    
    <label>Prénom</label>
    <input type="text" value="<?= htmlspecialchars(string: $u['prenom']) ?>" readonly>
    
    <label>Email</label>
    <input type="text" value="<?= htmlspecialchars(string: $u['email']) ?>" readonly>
    
    <label>Mot de passe</label>
    <input type="password" name="new_mdp" placeholder="Nouveau mot de passe (laisser vide pour ne pas changer)">
    
    <button type="submit" name="update_profile" class="btn">Sauvegarder les modifications</button>
    </form>
</div>

</body>
</html>