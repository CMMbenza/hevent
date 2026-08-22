<?php
require_once '../config/database.php';

$code = trim($_GET['code'] ?? '');
$event_id = !empty($_GET['event_id']) ? (int) $_GET['event_id'] : null;

if ($code === '') {
    echo '
    <div class="text-center p-3">
        <i class="bi bi-exclamation-triangle-fill text-warning display-3"></i>
        <h4 class="mt-3 text-danger fw-bold">Code manquant</h4>
        <p class="text-muted mb-0">Veuillez scanner un QR code valide.</p>
    </div>';
    exit;
}

// Requête enrichie pour récupérer l'invité, sa table et ses boissons
$sql = "SELECT 
            i.id, 
            i.fullname, 
            i.phone, 
            i.rsvp_status, 
            i.checked_in,
            t.table_name,
            GROUP_CONCAT(COALESCE(d.drink_name, gdc.custom_drink_name) SEPARATOR '||') AS drinks_list
        FROM invites i
        LEFT JOIN event_tables t ON i.table_id = t.id
        LEFT JOIN guest_drink_choices gdc ON i.id = gdc.invite_id
        LEFT JOIN event_drinks d ON gdc.drink_id = d.id
        WHERE i.invite_code = ?";

$params = [$code];

if ($event_id) {
    $sql .= " AND i.generat_event = ?";
    $params[] = $event_id;
}

$sql .= " GROUP BY i.id LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

// 1. Si le code n'existe pas
if (!$invite) {
    echo '
    <div class="text-center p-4">
        <div class="mb-3">
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
        </div>
        <h3 class="fw-bold text-danger">Code Invalide</h3>
        <p class="text-muted">Cet invité n\'existe pas dans le système pour cet événement.</p>
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
    </div>';
    exit;
}

// Vérifier si l'invité a déjà été scanné
$checkScan = $pdo->prepare("SELECT scanned_at FROM qr_scans WHERE invite_id = ? ORDER BY scanned_at DESC LIMIT 1");
$checkScan->execute([$invite['id']]);
$previousScan = $checkScan->fetch(PDO::FETCH_ASSOC);

// Préparation de la liste des boissons
$drinks = !empty($invite['drinks_list']) ? explode('||', $invite['drinks_list']) : [];

// 2. CAS : Déjà présent (Déjà scanné)
if ($previousScan) {
    $scannedAt = date('H:i:s', strtotime($previousScan['scanned_at']));
    ?>
<div class="text-center p-2">
    <div class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill mb-3">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> DÉJÀ PRÉSENT
    </div>

    <div class="avatar-placeholder bg-warning-subtle text-warning mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
        style="width: 70px; height: 70px;">
        <i class="bi bi-person-fill display-5"></i>
    </div>

    <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($invite['fullname']) ?></h3>
    <p class="text-muted small mb-3">Entré(e) précédemment à <strong><?= $scannedAt ?></strong></p>

    <div class="row g-2 text-start my-3">
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-light">
                <small class="text-muted d-block">Table</small>
                <strong class="text-dark"><i
                        class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($invite['table_name'] ?? 'Non assignée') ?></strong>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 border rounded-3 bg-light">
                <small class="text-muted d-block">RSVP</small>
                <strong class="text-dark"><i
                        class="bi bi-check-circle me-1"></i><?= htmlspecialchars($invite['rsvp_status']) ?></strong>
            </div>
        </div>
        <div class="col-12">
            <div class="p-2 border rounded-3 bg-light">
                <small class="text-muted d-block mb-1">Boissons choisies :</small>
                <?php if (!empty($drinks)): ?>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($drinks as $drink): ?>
                    <span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($drink) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <span class="text-muted small">Aucune préférence enregistrée</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-outline-secondary w-100 rounded-pill mt-2"
        data-bs-dismiss="modal">Fermer</button>
</div>
<?php
    exit;
}

// 3. CAS : Validation de l'entrée (Premier scan)
$pdo->prepare("UPDATE invites SET checked_in = 1 WHERE id = ?")->execute([$invite['id']]);
$pdo->prepare("INSERT INTO qr_scans (invite_id) VALUES (?)")->execute([$invite['id']]);
?>

<div class="text-center p-2">
    <div class="badge bg-success fs-6 px-3 py-2 rounded-pill mb-3">
        <i class="bi bi-check-circle-fill me-1"></i> ACCÈS AUTORISÉ
    </div>

    <div class="avatar-placeholder bg-success-subtle text-success mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
        style="width: 70px; height: 70px;">
        <i class="bi bi-person-check-fill display-5"></i>
    </div>

    <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($invite['fullname']) ?></h3>
    <?php if (!empty($invite['phone'])): ?>
    <p class="text-muted small mb-3"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($invite['phone']) ?></p>
    <?php endif; ?>

    <div class="card border-0 bg-light p-3 my-3 rounded-4">
        <div class="row g-2 text-start">
            <div class="col-12 mb-2">
                <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded-3 border">
                    <span class="text-muted"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Table attribuée
                        :</span>
                    <span
                        class="fw-bold fs-5 text-primary"><?= htmlspecialchars($invite['table_name'] ?? 'Non assignée') ?></span>
                </div>
            </div>

            <div class="col-12">
                <div class="p-2 bg-white rounded-3 border">
                    <span class="text-muted d-block mb-2"><i class="bi bi-cup-straw me-2 text-success"></i>Boissons
                        commandées :</span>
                    <?php if (!empty($drinks)): ?>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($drinks as $drink): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-2 fs-6">
                            <i class="bi bi-check2 me-1"></i><?= htmlspecialchars($drink) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <span class="text-muted small">Aucun choix spécifié</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-success w-100 rounded-pill py-2 fw-bold" data-bs-dismiss="modal">
        Valider et Continuer <i class="bi bi-arrow-right me-1"></i>
    </button>
</div>