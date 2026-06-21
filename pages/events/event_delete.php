<?php
session_start();

require_once '../../config/database.php';

/*
----------------------------------
SECURITY CHECK
----------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

/*
----------------------------------
GET PARAMS
----------------------------------
*/
$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['error'] = "❌ ID événement manquant";
    header("Location: ../events/");
    exit;
}

/*
----------------------------------
DELETE EVENT
----------------------------------
*/
$stmt = $pdo->prepare("
    SELECT cover_image 
    FROM events 
    WHERE generat = ? AND user_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['error'] = "❌ Événement introuvable ou non autorisé";
    header("Location: ../events/");
    exit;
}

/* delete cover image if exists */
if (!empty($event['cover_image'])) {
    $file = "../../uploads/covers/" . $event['cover_image'];
    if (file_exists($file)) {
        unlink($file);
    }
}

/* delete event */
$stmt = $pdo->prepare("
    DELETE FROM events
    WHERE generat = ? AND user_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);

/*
----------------------------------
SUCCESS MESSAGE + REDIRECT
----------------------------------
*/
$_SESSION['success'] = "✔ Événement supprimé avec succès";

header("Location: ../events/?action=list");
exit;