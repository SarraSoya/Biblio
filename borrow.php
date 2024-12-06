<?php
session_start();
$conn = new mysqli('127.0.0.1', 'root', '', 'bibliotheque');

$id = $_GET['id'];
$user = $_SESSION['user'];

$query = $conn->prepare("SELECT * FROM livres WHERE id = ? AND disponibilite = 1");
$query->bind_param("i", $id);
$query->execute();
$livre = $query->get_result()->fetch_assoc();

if (!$livre) {
    die("Livre non disponible.");
}

$conn->query("UPDATE livres SET disponibilite = 0, date_retour = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id = $id");
$conn->query("INSERT INTO emprunts (id_livre, id_utilisateur, date_emprunt, date_retour) VALUES ($id, {$user['id']}, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))");

header("Location: dashboard.php");
exit;

