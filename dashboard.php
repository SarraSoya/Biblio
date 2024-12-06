<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: index.php');
    exit;
}

$conn = new mysqli('127.0.0.1', 'root', '', 'bibliotheque');
$user = $_SESSION['user'];

$search = $_GET['search'] ?? '';

// Utilisation d'une requête préparée pour la recherche
$searchParam = "%" . $conn->real_escape_string($search) . "%";
$query = "
    SELECT l.*, 
           (SELECT COUNT(*) FROM emprunts e WHERE e.id_livre = l.id AND (e.date_retour IS NULL OR e.date_retour > NOW())) AS emprunt_count 
    FROM livres l 
    WHERE l.titre LIKE ? 
    ORDER BY l.titre";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $searchParam);
$stmt->execute();
$result = $stmt->get_result();

$historyQuery = "SELECT l.titre, e.date_emprunt, e.date_retour
                 FROM emprunts e
                 JOIN livres l ON e.id_livre = l.id
                 WHERE e.id_utilisateur = {$user['id']}";
$historyResult = $conn->query($historyQuery);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tableau de bord</title>
    <head>
    <title>Tableau de bord</title>
    <link rel="stylesheet" href="css/dash.css"> <!-- Update the path accordingly -->
</head>
</head>
<body>
    <header>
        <h1>Bienvenue, <?= htmlspecialchars($user['nom']) ?></h1>
        <form method="GET">
            <input type="text" name="search" placeholder="Rechercher un livre..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Rechercher</button>
        </form>
    </header>

    <h2>Livres disponibles</h2>
    <table>
        <tr>
            <th>Titre</th>
            <th>Auteur</th>
            <th>Genre</th>
            <th>Disponibilité</th>
            <th>Action</th>
        </tr>
        <?php while ($livre = $result->fetch_assoc()): ?>
<tr>
    <td><a href='book_details.php?id=<?= $livre['id'] ?>'><?= htmlspecialchars($livre['titre']) ?></a></td>
    <td><?= htmlspecialchars($conn->query("SELECT nom FROM auteurs WHERE id={$livre['auteur_id']}")->fetch_assoc()['nom']) ?></td>
    <td><?= htmlspecialchars($livre['genre']) ?></td>
    <td><?= $livre['emprunt_count'] > 0 ? 'Indisponible' : 'Disponible' ?></td>
    <td>
        <?php if ($livre['emprunt_count'] > 0): ?>
            Indisponible
        <?php else: ?>
            <a href='borrow.php?id=<?= $livre['id'] ?>'>Emprunter</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

    </table>

    <h2>Historique des emprunts</h2>
    <table>
        <tr>
            <th>Titre</th>
            <th>Date d'emprunt</th>
            <th>Date de retour</th>
        </tr>
        <?php while ($history = $historyResult->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($history['titre']) ?></td>
                <td><?= htmlspecialchars($history['date_emprunt']) ?></td>
                <td><?= htmlspecialchars($history['date_retour']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>