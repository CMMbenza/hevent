<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) exit;

$event_id = $_POST['event_id'] ?? 0;

// Créer
if (isset($_POST['create'])) {
    $stmt = $pdo->prepare("INSERT INTO event_drinks (generat_event, drink_name) VALUES (?, ?)");
    $stmt->execute([$event_id, $_POST['drink_name']]);
}

// Modifier
if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE event_drinks SET drink_name = ? WHERE id = ?");
    $stmt->execute([$_POST['drink_name'], $_POST['id']]);
}

// Supprimer
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM event_drinks WHERE id = ?");
    $stmt->execute([$_POST['id']]);
}

header("Location: ../drinks/?event_id=" . $event_id);
exit;