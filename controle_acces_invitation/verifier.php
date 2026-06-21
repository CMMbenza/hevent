<?php
require_once '../config/database.php';

$code = trim($_GET['code'] ?? '');
$event_id = !empty($_GET['event_id']) ? (int) $_GET['event_id'] : null;

if ($code === '') {
    exit('<p class="text-danger">Code manquant</p>');
}

/**
 * LOGIQUE :
 * Si event_id est fourni, on filtre par événement.
 * Sinon, on cherche le code dans toute la table (ou on peut déduire l'événement).
 */
if ($event_id) {

    $sql = "SELECT i.id, i.fullname
            FROM invites i
            WHERE i.invite_code = ?
            AND i.generat_event = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$code, $event_id]);

} else {

    $sql = "SELECT i.id, i.fullname
            FROM invites i
            WHERE i.invite_code = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$code]);
}

$invite = $stmt->fetch();

if (!$invite) {
    echo '<p class="text-danger fs-4 fw-bold">❌ Code invalide !</p>';
    exit;
}

$check = $pdo->prepare("SELECT id FROM qr_scans WHERE invite_id = ?");
$check->execute([$invite['id']]);

if ($check->fetch()) {
    echo '<p class="text-warning fs-4 fw-bold">⚠️ Déjà présent :<br>'
        . htmlspecialchars($invite['fullname']) . '</p>';
    exit;
}

$pdo->prepare("UPDATE invites SET checked_in = 1 WHERE id = ?")
    ->execute([$invite['id']]);

$pdo->prepare("INSERT INTO qr_scans (invite_id) VALUES (?)")
    ->execute([$invite['id']]);

echo '<p class="text-success fs-4 fw-bold">✅ Bienvenue :<br>'
    . htmlspecialchars($invite['fullname']) . '</p>';

?>