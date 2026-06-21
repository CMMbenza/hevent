<?php
require_once '../../includes/functions.php';

// event
$stmt = $pdo->prepare("SELECT pack_code FROM events WHERE id=?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

$pack = getPack($pdo, $event['pack_code']);

$limit = $pack['max_invites'];

$current = countInvitesByEvent($pdo, $event_id);

// ❌ BLOQUAGE
if($limit != -1 && $current >= $limit){

    die("❌ Limite d'invités atteinte pour cet événement (Pack ".$pack['name'].")");
}
?>