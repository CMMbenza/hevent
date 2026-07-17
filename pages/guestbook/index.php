<?php
session_start();

require_once '../../config/database.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$event_id = $_GET['event_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT *
    FROM events
    WHERE generat=?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Événement introuvable");
}

/*
----------------------------------
MESSAGES
----------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM guestbook
    WHERE generat_event=?
    ORDER BY created_at DESC
");
$stmt->execute([$event_id]);
$messages = $stmt->fetchAll();

?>

<div class="container-fluid">

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h4><i class="bi bi-journal-text"></i> Livre d'or</h4>
            <small><?= htmlspecialchars($event['title']) ?></small>
        </div>
    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <?php foreach ($messages as $m): ?>

            <div class="border-bottom py-2">

                <b><?= htmlspecialchars($m['name']) ?></b><br>
                <small><?= htmlspecialchars($m['message']) ?></small>

            </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>

<?php include '../../includes/footer.php'; ?>