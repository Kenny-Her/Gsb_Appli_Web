<?php
require 'bd_connexion.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $mdp = $_POST['mdp'];

    $requete = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $requete->execute([$email]);
    $user = $requete->fetch();

    if ($user && password_verify($mdp, $user['mdp'])) {
        $_SESSION['user'] = $user;
        header('Location: tableau-de-bord.php');
        exit;
    } else {
        $error = "Identifiants incorrects";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>GSB Identification</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <img src="assets/Gsb_Logo.png" alt="Logo GSB" style="width: 120px; margin-bottom: 1rem;">
        <h2>IDENTIFICATION</h2>
        <?php if(isset($_GET['error']) && $_GET['error'] == 'inactivity'): ?>
            <p style='color:orange'>Vous avez été déconnecté pour inactivité.</p>
        <?php endif; ?>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email">
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit" class="btn">Se connecter</button>
        </form>
        <p style="text-align: center; margin-top: 1rem;"><a href="inscription.php">Pas de compte ? S'inscrire</a></p>
    </div>
</body>
</html>