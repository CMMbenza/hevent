<?php
session_start();

// 1. SÉCURITÉ ET REDIRECTION (Toujours en premier avant tout affichage ou include HTML)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

// 2. ÉVITE LE RETOUR EN ARRIÈRE APRÈS DÉCONNEXION (Configuration des Headers)
include '../../includes/constant.php';

require_once '../../config/database.php';

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| EVENEMENTS
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM events
    WHERE user_id=?
");
$stmt->execute([$user_id]);
$total_events = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| INVITATIONS TOTALES (i.generat_event)
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM invites i
    INNER JOIN events e ON e.generat = i.generat_event
    WHERE e.user_id=?
");
$stmt->execute([$user_id]);
$total_invites = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PRESENTS (i.generat_event)
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM invites i
    INNER JOIN events e ON e.generat = i.generat_event
    WHERE e.user_id=?
    AND i.rsvp_status='Present'
");
$stmt->execute([$user_id]);
$present = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| ABSENTS (i.generat_event)
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM invites i
    INNER JOIN events e ON e.generat = i.generat_event
    WHERE e.user_id=?
    AND i.rsvp_status='Absent'
");
$stmt->execute([$user_id]);
$absent = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| PENDING (i.generat_event)
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM invites i
    INNER JOIN events e ON e.generat = i.generat_event
    WHERE e.user_id=?
    AND (
        i.rsvp_status='Pending'
        OR i.rsvp_status IS NULL
        OR i.rsvp_status=''
    )
");
$stmt->execute([$user_id]);
$pending = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| TAUX RSVP
|--------------------------------------------------------------------------
*/
$response_rate = 0;
if ($total_invites > 0) {
    $response_rate = round((($present + $absent) * 100) / $total_invites);
}

/*
|--------------------------------------------------------------------------
| CHECK-IN (i.generat_event)
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM invites i
    INNER JOIN events e ON e.generat = i.generat_event
    WHERE e.user_id=?
    AND i.checked_in=1
");
$stmt->execute([$user_id]);
$total_checkin = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| TABLES
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM event_tables t
    INNER JOIN events e ON e.generat = t.generat_event
    WHERE e.user_id=?
");
$stmt->execute([$user_id]);
$total_tables = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| DERNIER EVENEMENT
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT title, event_date
    FROM events
    WHERE user_id=?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$last_event = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. INCLUSIONS VISUELLES (Inclus seulement après validation de l'utilisateur)
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<div class="container-fluid">

    <div class="card shadow-sm mb-4 border-0 mt-4">
        <div class="card-body">
            <h3 class="mb-0 fw-bold" style="color: var(--dark-slate);">
                <i class="bi bi-bar-chart-line-fill me-2"></i>
                Mes statistiques
            </h3>
            <small class="text-muted">
                Vue globale et analytique de votre activité
            </small>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-md-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid var(--dark-slate);">
                <div class="card-body text-center py-4">
                    <i class="bi bi-calendar4-event fs-1 mb-2 d-block" style="color: var(--dark-slate);"></i>
                    <h2 class="fw-bold mb-1" style="color: var(--dark-slate);"><?= $total_events ?></h2>
                    <small class="text-muted fw-bold text-uppercase small">Événements totaux</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid #198754;">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-lines-fill fs-1 text-success mb-2 d-block"></i>
                    <h2 class="fw-bold text-success mb-1"><?= $total_invites ?></h2>
                    <small class="text-muted fw-bold text-uppercase small">Invitations totales</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid #ffc107;">
                <div class="card-body text-center py-4">
                    <i class="bi bi-pie-chart fs-1 text-warning mb-2 d-block"></i>
                    <h2 class="fw-bold text-warning mb-1"><?= $response_rate ?>%</h2>
                    <small class="text-muted fw-bold text-uppercase small">Taux RSVP</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100" style="border-left: 4px solid #0dcaf0;">
                <div class="card-body text-center py-4">
                    <i class="bi bi-qr-code-scan fs-1 text-info mb-2 d-block"></i>
                    <h2 class="fw-bold text-info mb-1"><?= $total_checkin ?></h2>
                    <small class="text-muted fw-bold text-uppercase small">Scans Check-in</small>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-1">

        <div class="col-md-4">
            <div class="card border-0 bg-light shadow-sm">
                <div class="card-body text-center py-3">
                    <i class="bi bi-check2-circle fs-2 text-success me-1 vertical-middle"></i>
                    <span class="fs-3 fw-bold text-success align-middle"><?= $present ?></span>
                    <div class="text-muted small fw-semibold mt-1">Confirmés (Présents)</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 bg-light shadow-sm">
                <div class="card-body text-center py-3">
                    <i class="bi bi-x-circle fs-2 text-danger me-1 vertical-middle"></i>
                    <span class="fs-3 fw-bold text-danger align-middle"><?= $absent ?></span>
                    <div class="text-muted small fw-semibold mt-1">Déclinés (Absents)</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 bg-light shadow-sm">
                <div class="card-body text-center py-3">
                    <i class="bi bi-clock-history fs-2 text-secondary me-1 vertical-middle"></i>
                    <span class="fs-3 fw-bold text-secondary align-middle"><?= $pending ?></span>
                    <div class="text-muted small fw-semibold mt-1">En attente de réponse</div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-3 mt-2">

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header fw-bold"
                    style="background-color: var(--dark-slate); color: var(--primary-rose);">
                    <i class="bi bi-collection-play me-2"></i>Résumé de la logistique
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span class="text-muted fw-semibold"><i class="bi bi-grid-3x3-gap me-2"></i>Nombre de tables
                            configurées :</span>
                        <span class="badge bg-dark px-2.5 py-1.5"><?= $total_tables ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span class="text-muted fw-semibold"><i class="bi bi-bookmark-star me-2"></i>Dernier événement
                            :</span>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($last_event['title'] ?? '-') ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fw-semibold"><i class="bi bi-calendar-check me-2"></i>Date de
                            l'événement :</span>
                        <span
                            class="text-muted fw-bold"><?= htmlspecialchars($last_event['event_date'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header fw-bold text-white shadow-sm" style="background-color: #198754;">
                    <i class="bi bi-activity me-2"></i>Répartition des réponses globales
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="progress mb-3" style="height:25px; border-radius: 6px; overflow:hidden;">
                        <div class="progress-bar bg-success text-white fw-bold small"
                            style="width:<?= $total_invites ? ($present * 100 / $total_invites) : 0 ?>%">
                            <?= $total_invites ? round($present * 100 / $total_invites).'%' : '' ?> Présents
                        </div>
                        <div class="progress-bar bg-danger text-white fw-bold small"
                            style="width:<?= $total_invites ? ($absent * 100 / $total_invites) : 0 ?>%">
                            <?= $total_invites ? round($absent * 100 / $total_invites).'%' : '' ?> Absents
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i> Taux global de réponse calculé sur invitations :
                        <strong><?= $response_rate ?>%</strong>
                    </small>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
// Sécurité JS contre le retour historique sur navigateur
window.addEventListener('pageshow', function(event) {
    if (event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type ===
            2)) {
        window.location.reload();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>