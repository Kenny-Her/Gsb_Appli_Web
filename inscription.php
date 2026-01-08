<?php
require 'bd_connexion.php';
require 'accueil.php';

if ($role != 'Admin' && $role != 'Responsable') {
    echo "</div></body></html>";
    die("<p style='margin-left: 270px; padding: 2rem;'>Accès non autorisé.</p>");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
    $mdp_confirm = $_POST['mdp_confirm'] ?? '';
    $role_compte = $_POST['role'] ?? 'Visiteur';

    if (empty($nom) || empty($prenom) || empty($email) || empty($mdp)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($mdp !== $mdp_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!filter_var(value: $email, filter: FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        $requete = $pdo->prepare(query: "SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
        $requete->execute(params: [$email]);
        if ($requete->fetchColumn() > 0) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            $hashed_mdp = password_hash(password: $mdp, algo: PASSWORD_BCRYPT);

            $requete = $pdo->prepare(query: "INSERT INTO utilisateurs (nom, prenom, email, mdp, role) VALUES (?, ?, ?, ?, ?)");
            if ($requete->execute(params: [$nom, $prenom, $email, $hashed_mdp, $role_compte])) {
                $success = "Compte créé avec succès !";
            } else {
                $error = "Une erreur est survenue lors de la création.";
            }
        }
    }
}
?>

<h2>Création de compte utilisateur</h2>

<div class="card">
    <?php if(!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
    <?php if(!empty($success)) echo "<p style='color:green'>$success</p>"; ?>
    <form method="POST">
        <label>Rôle</label>
        <select name="role" required>
            <option value="Visiteur">Visiteur</option>
            <option value="Delegue">Délégué</option>
            <option value="Responsable">Responsable</option>
            <option value="Admin">Admin</option>
        </select>

        <div style="display:flex; gap:10px;">
            <div style="flex:1"><label>Nom</label><input type="text" name="nom" required></div>
            <div style="flex:1"><label>Prénom</label><input type="text" name="prenom" required></div>
        </div>
        
        <label>Email</label>
        <input type="email" name="email" required>
        
        <label>Mot de passe</label>
        <input type="password" name="mdp" required>
        
        <label>Confirmer le mot de passe</label>
        <input type="password" name="mdp_confirm" required>
        
        <button type="submit" class="btn">Créer le compte</button>
    </form>
</div>

</body>
</html>