<?php

function getPack($pdo, $code){

    $stmt = $pdo->prepare("
        SELECT *
        FROM packs
        WHERE code = ?
    ");

    $stmt->execute([$code]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function countInvitesByEvent($pdo, $generat_event){

    // Correction ici : la colonne s'appelle 'generat_event' dans la table invites
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM invites
        WHERE generat_event = ?
    ");

    $stmt->execute([$generat_event]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int)($result['total'] ?? 0);
}