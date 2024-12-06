<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: index.php');
    exit;
}

$conn = new mysqli('127.0.0.1', 'root', '', 'bibliotheque');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the book ID from the URL
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = intval($_POST['rating']);
    $comment = $conn->real_escape_string($_POST['comment']);
    $utilisateur_id = $_SESSION['user']['id']; // Assuming the user ID is stored in session

    // Insert rating into the database
    $stmt = $conn->prepare("INSERT INTO ratings (livre_id, utilisateur_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $book_id, $utilisateur_id, $rating, $comment);
    $stmt->execute();
    echo "<p>Thank you for rating!</p>"; // Simple confirmation message
}

// Fetch book details
$query = $conn->prepare("SELECT l.*, a.nom AS auteur_nom, a.biographie FROM livres l JOIN auteurs a ON l.auteur_id = a.id WHERE l.id = ?");
$query->bind_param("i", $book_id);
$query->execute();
$result = $query->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    echo "Livre non trouvé";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du livre</title>
    <link rel="stylesheet" href="css/styles.css">  <!-- Assuming your CSS file is in a folder named css -->
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($book['titre']) ?></h1>
        <p><strong>Auteur:</strong> <?= htmlspecialchars($book['auteur_nom']) ?></p>
        <p><strong>Genre:</strong> <?= htmlspecialchars($book['genre']) ?></p>
        <p><strong>Biographie:</strong> <?= nl2br(htmlspecialchars($book['biographie'])) ?></p>
        <p><strong>Disponibilité:</strong> <?= $book['disponibilite'] ? 'Disponible' : 'Indisponible' ?></p>

        <h2>Rate This Book</h2>
        <form method="POST">
            <div class="form-group">
                <label for="rating">Rating (1-5):</label>
                <select name="rating" id="rating" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>
            <div class="form-group">
                <label for="comment">Comment:</label>
                <textarea name="comment" id="comment"></textarea>
            </div>
            <button type="submit">Submit Rating</button>
        </form>
    </div>
</body>


</html>
