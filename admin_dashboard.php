<?php
session_start();

// Vérifier si l'utilisateur est un administrateur
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$conn = new mysqli('127.0.0.1', 'root', '', 'bibliotheque');

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Actions pour CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // Gestion des Livres
            case 'add_livre':
                $titre = $_POST['titre'];
                $genre = $_POST['genre'];
                $auteur_id = $_POST['auteur_id'];
                $nouvelle_biographie = $_POST['biographie']; // Récupérer la biographie du formulaire

                // Vérifier si l'auteur existe
                $auteur_query = $conn->query("SELECT * FROM auteurs WHERE id = $auteur_id");
                
                if ($auteur_query->num_rows > 0) {
                    // L'auteur existe, mise à jour de la biographie
                    $conn->query("UPDATE auteurs SET biographie = '$nouvelle_biographie' WHERE id = $auteur_id");
                }

                // Ajouter le livre
                $conn->query("INSERT INTO livres (titre, genre, auteur_id, disponibilite, date_retour) VALUES ('$titre', '$genre', $auteur_id, 1, NULL)");
                break;
            case 'delete_livre':
                $id = $_POST['id'];
                $conn->query("DELETE FROM livres WHERE id = $id");
                break;
            case 'update_livre':
                $id = $_POST['id'];
                $titre = $_POST['titre'];
                $genre = $_POST['genre'];
                $auteur_id = $_POST['auteur_id'];
                $disponibilite = $_POST['disponibilite']; // Nouvelle ligne ajoutée
                $conn->query("UPDATE livres SET titre = '$titre', genre = '$genre', auteur_id = $auteur_id, disponibilite = $disponibilite WHERE id = $id");
                break;

            // Gestion des Auteurs
            case 'add_auteur':
                $nom = $_POST['nom'];
                $biographie = $_POST['biographie'];
                $photo = $_POST['photo'];
                $conn->query("INSERT INTO auteurs (nom, biographie, photo) VALUES ('$nom', '$biographie', '$photo')");
                break;
            case 'delete_auteur':
                $id = $_POST['id'];
                $conn->query("DELETE FROM auteurs WHERE id = $id");
                break;
            case 'update_auteur':
                $id = $_POST['id'];
                $nom = $_POST['nom'];
                $biographie = $_POST['biographie'];
                $photo = $_POST['photo'];
                $conn->query("UPDATE auteurs SET nom = '$nom', biographie = '$biographie', photo = '$photo' WHERE id = $id");
                break;

            // Gestion des Utilisateurs
            case 'add_user':
                $nom = $_POST['nom'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $mot_de_passe = md5($_POST['mot_de_passe']);
                $conn->query("INSERT INTO utilisateurs (nom, email, role, mot_de_passe) VALUES ('$nom', '$email', '$role', '$mot_de_passe')");
                break;
            case 'delete_user':
                $id = $_POST['id'];
                $conn->query("DELETE FROM utilisateurs WHERE id = $id");
                break;
            case 'update_user':
                $id = $_POST['id'];
                $nom = $_POST['nom'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $conn->query("UPDATE utilisateurs SET nom = '$nom', email = '$email', role = '$role' WHERE id = $id");
                break;

            // Gestion des Emprunts
            // Gestion des Emprunts
            case 'add_emprunt':
                $id_livre = $_POST['id_livre'];
                // Vérifiez d'abord si le livre est disponible
                $livre_query = $conn->query("SELECT disponibilite FROM livres WHERE id = $id_livre");
                $livre = $livre_query->fetch_assoc();

                if ($livre['disponibilite']) {
                    $id_utilisateur = $_POST['id_utilisateur'];
                    $date_emprunt = $_POST['date_emprunt'];
                    $date_retour = $_POST['date_retour'];
                    $conn->query("INSERT INTO emprunts (id_livre, id_utilisateur, date_emprunt, date_retour) VALUES ($id_livre, $id_utilisateur, '$date_emprunt', '$date_retour')");
                    $conn->query("UPDATE livres SET disponibilite = 0 WHERE id = $id_livre");
                } else {
                    echo "Le livre n'est pas disponible.";
                }
                break;
            case 'delete_emprunt':
                $id_emprunt = $_POST['id_emprunt'];
                $id_livre = $_POST['id_livre'];
                $conn->query("DELETE FROM emprunts WHERE id_emprunt = $id_emprunt");
                $conn->query("UPDATE livres SET disponibilite = 1 WHERE id = $id_livre");
                break;
            case 'update_emprunt':
                $id_emprunt = $_POST['id_emprunt'];
                $date_retour = $_POST['date_retour'];
                $conn->query("UPDATE emprunts SET date_retour = '$date_retour' WHERE id_emprunt = $id_emprunt");
                break;
            case 'return_book':
                $id_emprunt = $_POST['id_emprunt'];
                $date_retour = date('Y-m-d');
                
                // Mettre à jour la date de retour dans la table des emprunts
                $conn->query("UPDATE emprunts SET date_retour = '$date_retour' WHERE id_emprunt = $id_emprunt");
                
                // Récupérer l'ID du livre associé à l'emprunt
                $id_livre = $_POST['id_livre'];
                
                // Mettre à jour la disponibilité du livre
                $conn->query("UPDATE livres SET disponibilite = 1 WHERE id = $id_livre");
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Tableau de bord admin </title>
    <head>
    <title>Tableau de bord</title>
    <link rel="stylesheet" href="css/admin.css"> <!-- Update the path accordingly -->
</head>

<body>
    <header>
        <h1>Tableau de bord Admin</h1>
    </header>

    <!-- Section Livres -->
    <div class="section">
        <h2>Livres</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_livre">
            <input type="text" name="titre" placeholder="Titre" required>
            <input type="text" name="genre" placeholder="Genre" required>
            <select name="auteur_id" required>
                <option value="">Sélectionner un auteur</option>
                <?php
                $auteurs = $conn->query("SELECT * FROM auteurs");
                while ($auteur = $auteurs->fetch_assoc()) {
                    echo "<option value='{$auteur['id']}'>{$auteur['nom']}</option>";
                }
                ?>
            </select>
            <textarea name="biographie" placeholder="Nouvelle biographie (facultatif)"></textarea>
            <button type="submit">Ajouter</button>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Genre</th>
                <th>Auteur</th>
                <th>Disponibilité</th>
                <th>Actions</th>
            </tr>
            <?php
            $livres = $conn->query("SELECT l.*, a.nom AS auteur FROM livres l LEFT JOIN auteurs a ON l.auteur_id = a.id");
            while ($livre = $livres->fetch_assoc()): ?>
                <tr>
                    <td><?= $livre['id'] ?></td>
                    <td><?= $livre['titre'] ?></td>
                    <td><?= $livre['genre'] ?></td>
                    <td><?= $livre['auteur'] ?></td>
                    <td><?= $livre['disponibilite'] ? 'Disponible' : 'Indisponible' ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_livre">
                            <input type="hidden" name="id" value="<?= $livre['id'] ?>">
                            <button type="submit">Supprimer</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_livre">
                            <input type="hidden" name="id" value="<?= $livre['id'] ?>">
                            <input type="text" name="titre" value="<?= $livre['titre'] ?>" required>
                            <input type="text" name="genre" value="<?= $livre['genre'] ?>" required>
                            <select name="auteur_id" required>
                                <option value="">Sélectionner un auteur</option>
                                <?php
                                $auteurs = $conn->query("SELECT * FROM auteurs");
                                while ($auteur = $auteurs->fetch_assoc()) {
                                    $selected = $auteur['id'] == $livre['auteur_id'] ? 'selected' : '';
                                    echo "<option value='{$auteur['id']}' $selected>{$auteur['nom']}</option>";
                                }
                                ?>
                            </select>
                            <select name="disponibilite" required>
                                <option value="1" <?= $livre['disponibilite'] ? 'selected' : '' ?>>Disponible</option>
                                <option value="0" <?= !$livre['disponibilite'] ? 'selected' : '' ?>>Indisponible</option>
                            </select>
                            <button type="submit">Modifier</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Section Auteurs -->
    <div class="section">
        <h2>Auteurs</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_auteur">
            <input type="text" name="nom" placeholder="Nom de l'auteur" required>
            <textarea name="biographie" placeholder="Biographie" required></textarea>
            <input type="text" name="photo" placeholder="URL de la photo (facultatif)">
            <button type="submit">Ajouter</button>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Biographie</th>
                <th>Photo</th>
                <th>Actions</th>
            </tr>
            <?php
            $auteurs = $conn->query("SELECT * FROM auteurs");
            while ($auteur = $auteurs->fetch_assoc()): ?>
                <tr>
                    <td><?= $auteur['id'] ?></td>
                    <td><?= $auteur['nom'] ?></td>
                    <td><?= $auteur['biographie'] ?></td>
                    <td><img src="<?= $auteur['photo'] ?>" alt="Photo" width="50"></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_auteur">
                            <input type="hidden" name="id" value="<?= $auteur['id'] ?>">
                            <button type="submit">Supprimer</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_auteur">
                            <input type="hidden" name="id" value="<?= $auteur['id'] ?>">
                            <input type="text" name="nom" value="<?= $auteur['nom'] ?>" required>
                            <textarea name="biographie" required><?= $auteur['biographie'] ?></textarea>
                            <input type="text" name="photo" value="<?= $auteur['photo'] ?>" placeholder="URL de la photo">
                            <button type="submit">Modifier</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Section Utilisateurs -->
    <div class="section">
        <h2>Utilisateurs</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
            <select name="role" required>
                <option value="user">Utilisateur</option>
                <option value="admin">Administrateur</option>
            </select>
            <button type="submit">Ajouter</button>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Actions</th>
            </tr>
            <?php
            $utilisateurs = $conn->query("SELECT * FROM utilisateurs");
            while ($utilisateur = $utilisateurs->fetch_assoc()): ?>
                <tr>
                    <td><?= $utilisateur['id'] ?></td>
                    <td><?= $utilisateur['nom'] ?></td>
                    <td><?= $utilisateur['email'] ?></td>
                    <td><?= $utilisateur['role'] ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" value="<?= $utilisateur['id'] ?>">
                            <button type="submit">Supprimer</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="id" value="<?= $utilisateur['id'] ?>">
                            <input type="text" name="nom" value="<?= $utilisateur['nom'] ?>" required>
                            <input type="email" name="email" value="<?= $utilisateur['email'] ?>" required>
                            <select name="role" required>
                                <option value="user" <?= $utilisateur['role'] == 'user' ? 'selected' : '' ?>>Utilisateur</option>
                                <option value="admin" <?= $utilisateur['role'] == 'admin' ? 'selected' : '' ?>>Administrateur</option>
                            </select>
                            <button type="submit">Modifier</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Section Emprunts -->
    <div class="section">
        <h2>Emprunts</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_emprunt">
            <select name="id_livre" required>
                <option value="">Sélectionner un livre</option>
                <?php
                $livres_disponibles = $conn->query("SELECT * FROM livres WHERE disponibilite = 1");
                while ($livre = $livres_disponibles->fetch_assoc()) {
                    echo "<option value='{$livre['id']}'>{$livre['titre']}</option>";
                }
                ?>
            </select>
            <select name="id_utilisateur" required>
                <option value="">Sélectionner un utilisateur</option>
                <?php
                $utilisateurs = $conn->query("SELECT * FROM utilisateurs WHERE role = 'user'");
                while ($utilisateur = $utilisateurs->fetch_assoc()) {
                    echo "<option value='{$utilisateur['id']}'>{$utilisateur['nom']}</option>";
                }
                ?>
            </select>
            <input type="date" name="date_emprunt" required>
            <input type="date" name="date_retour" required>
            <button type="submit">Emprunter</button>
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Livre</th>
                <th>Utilisateur</th>
                <th>Date d'emprunt</th>
                <th>Date de retour</th>
                <th>Actions</th>
            </tr>
            <?php
            $emprunts = $conn->query("SELECT e.*, l.titre AS livre, u.nom AS utilisateur FROM emprunts e 
                                JOIN livres l ON e.id_livre = l.id
                                JOIN utilisateurs u ON e.id_utilisateur = u.id");
            while ($emprunt = $emprunts->fetch_assoc()): ?>
                <tr>
                    <td><?= $emprunt['id_emprunt'] ?></td>
                    <td><?= $emprunt['livre'] ?></td>
                    <td><?= $emprunt['utilisateur'] ?></td>
                    <td><?= $emprunt['date_emprunt'] ?></td>
                    <td><?= $emprunt['date_retour'] ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="return_book">
                            <input type="hidden" name="id_emprunt" value="<?= $emprunt['id_emprunt'] ?>">
                            <input type="hidden" name="id_livre" value="<?= $emprunt['id_livre'] ?>">
                            <button type="submit">Retour</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_emprunt">
                            <input type="hidden" name="id_emprunt" value="<?= $emprunt['id_emprunt'] ?>">
                            <input type="date" name="date_retour" value="<?= $emprunt['date_retour'] ?>" required>
                            <button type="submit">Modifier</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_emprunt">
                            <input type="hidden" name="id_emprunt" value="<?= $emprunt['id_emprunt'] ?>">
                            <input type="hidden" name="id_livre" value="<?= $emprunt['id_livre'] ?>">
                            <button type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>