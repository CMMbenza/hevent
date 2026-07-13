<?php
session_start();

require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$event_id = $_POST['event_id'] ?? 0;

/*
----------------------------------
UPDATE INVITE
----------------------------------
*/
if (isset($_POST['update'])) {

    $id = $_POST['id'] ?? null;
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $table_id = $_POST['table_id'] ?? '';

    // Si aucune table sélectionnée, on passe la valeur à null pour respecter la bdd (SET NULL)
    $table_id = (!empty($table_id)) ? (int)$table_id : null;

    if (!$id || empty($fullname)) {
        $_SESSION['error'] = "❌ Données invalides";
        header("Location:index.php?event_id=".$event_id);
        exit;
    }

    // CORRECTION SECURITY CHECK : Jointure sur generat_event et generat
    $stmt = $pdo->prepare("
        SELECT i.id
        FROM invites i
        JOIN events e ON e.generat = i.generat_event
        WHERE i.id = ? AND e.user_id = ?
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = "❌ Accès refusé";
        header("Location:index.php?event_id=".$event_id);
        exit;
    }

    // UPDATE : Ajout de table_id dans la requête
    $stmt = $pdo->prepare("
        UPDATE invites
        SET fullname = ?, phone = ?, table_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$fullname, $phone, $table_id, $id]);

    $_SESSION['success'] = "✔ Invité modifié avec succès";

    header("Location:index.php?event_id=".$event_id);
    exit;
}

/*
----------------------------------
DELETE INVITE
----------------------------------
*/
if (isset($_POST['delete'])) {

    $id = $_POST['id'] ?? null;

    if (!$id) {
        $_SESSION['error'] = "❌ Invité introuvable";
        header("Location:index.php?event_id=".$event_id);
        exit;
    }

    // CORRECTION SECURITY CHECK : Jointure sur generat_event et generat
    $stmt = $pdo->prepare("
        SELECT i.id
        FROM invites i
        JOIN events e ON e.generat = i.generat_event
        WHERE i.id = ? AND e.user_id = ?
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = "❌ Accès refusé";
        header("Location:index.php?event_id=".$event_id);
        exit;
    }

    // DELETE
    $stmt = $pdo->prepare("DELETE FROM invites WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "✔ Invité supprimé avec succès";

    header("Location:index.php?event_id=".$event_id);
    exit;
}

/*
----------------------------------
FALLBACK
----------------------------------
*/
$_SESSION['error'] = "❌ Action invalide";
header("Location:index.php?event_id=".$event_id);
exit;