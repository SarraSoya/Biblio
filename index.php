<?php
session_start();
$conn = new mysqli('127.0.0.1', 'root', '', 'bibliotheque');

// Vérifiez la connexion à la base de données
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password']; // Mot de passe en clair, pas de hashage

    // Préparation de la requête sécurisée
    $query = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($user = $result->fetch_assoc()) {
        // Vérification du mot de passe en clair
        if ($password === $user['mot_de_passe']) {
            $_SESSION['user'] = $user;

            // Redirection selon le rôle de l'utilisateur
            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php'); // Redirection pour les admins
            } else {
                header('Location: dashboard.php'); // Redirection pour les utilisateurs
            }
            exit;
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Authentification</title>
    <link rel="stylesheet" href="css/index.css">  <!-- Make sure the path is correct -->

</head>
<body>
    <form method="POST">
        <h2>Connexion</h2>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br><br>
        <button type="submit">Se connecter</button>
    </form>
</body>
</html>
