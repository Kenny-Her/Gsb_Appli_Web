<?php
require 'bd_connexion.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
    $mdp_confirm = $_POST['mdp_confirm'] ?? '';

    if (empty($nom) || empty($prenom) || empty($email) || empty($mdp)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($mdp !== $mdp_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        $domain_roles = [
            '@gsb-visiteur.com' => 'Visiteur',
            '@gsb-delegue.com' => 'Delegue',
            '@gsb-responsable.com' => 'Responsable',
            '@gsb-admin.com' => 'Admin'
        ];
        $domain = substr($email, strrpos($email, '@'));

        if (!array_key_exists($domain, $domain_roles)) {
            $error = "L'adresse email doit appartenir à un domaine GSB autorisé.";
        } else {
            $requete = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
            $requete->execute([$email]);
            if ($requete->fetchColumn() > 0) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                $role = $domain_roles[$domain];
                $hashed_mdp = password_hash($mdp, PASSWORD_BCRYPT);

                $requete = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mdp, role) VALUES (?, ?, ?, ?, ?)");
                if ($requete->execute([$nom, $prenom, $email, $hashed_mdp, $role])) {
                    $success = "Inscription réussie ! Vous allez être redirigé vers la page de connexion.";
                    header("refresh:3;url=index.php");
                } else {
                    $error = "Une erreur est survenue lors de l'inscription.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>GSB - Inscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h2>INSCRIPTION</h2>
        <?php if(!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
        <?php if(!empty($success)) echo "<p style='color:green'>$success</p>"; ?>
        <form method="POST">
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <input type="password" name="mdp_confirm" placeholder="Confirmer le mot de passe" required>
            <button type="submit" class="btn">S'inscrire</button>
        </form>
        <p style="text-align: center; margin-top: 1rem;"><a href="index.php">Déjà un compte ? Connectez-vous</a></p>
    </div>
</body>
</html>