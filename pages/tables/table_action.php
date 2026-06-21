<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

/*
====================================
CREATE TABLE
====================================
*/
if (isset($_POST['create'])) {

    $event_id = (int)($_POST['event_id'] ?? 0);
    $table_name = trim($_POST['table_name'] ?? '');

    if (!$event_id || empty($table_name)) {

        $_SESSION['error'] = "Informations invalides";

        header("Location: index.php?event_id=".$event_id);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO event_tables(
            generat_event,
            table_name
        )
        VALUES(
            ?,
            ?
        )
    ");

    $stmt->execute([
        $event_id,
        $table_name
    ]);

    $_SESSION['success'] = "✔ Table ajoutée";

    header("Location: index.php?event_id=".$event_id);
    exit;
}

/*
====================================
UPDATE TABLE
====================================
*/
if (isset($_POST['update'])) {

    $id = (int)($_POST['id'] ?? 0);
    $event_id = (int)($_POST['event_id'] ?? 0);
    $table_name = trim($_POST['table_name'] ?? '');

    if (!$id || !$event_id || empty($table_name)) {

        $_SESSION['error'] = "Informations invalides";

        header("Location: index.php?event_id=".$event_id);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE event_tables
        SET table_name=?
        WHERE id=?
    ");

    $stmt->execute([
        $table_name,
        $id
    ]);

    $_SESSION['success'] = "✔ Table modifiée";

    header("Location: index.php?event_id=".$event_id);
    exit;
}

/*
====================================
DELETE TABLE
====================================
*/
if (isset($_POST['delete'])) {

    $id = (int)($_POST['id'] ?? 0);
    $event_id = (int)($_POST['event_id'] ?? 0);

    if (!$id || !$event_id) {

        $_SESSION['error'] = "Informations invalides";

        header("Location: index.php");
        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM event_tables
        WHERE id=?
    ");

    $stmt->execute([$id]);

    $_SESSION['success'] = "✔ Table supprimée";

    header("Location: index.php?event_id=".$event_id);
    exit;
}

/*
====================================
FALLBACK
====================================
*/

$_SESSION['error'] = "Action non autorisée";

header("Location: index.php");
exit;