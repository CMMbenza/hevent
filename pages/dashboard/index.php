<?php
session_start();

// ÉVITE LE RETOUR EN ARRIÈRE APRÈS DÉCONNEXION
include '../../includes/constant.php';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/

$totalEvents = $pdo->prepare("
SELECT COUNT(*)
FROM events
");
$totalEvents->execute();
$totalEvents = $totalEvents->fetchColumn();

$totalInvites = $pdo->prepare("
SELECT COUNT(*)
FROM invites i
JOIN events e ON e.generat=i.generat_event
");
$totalInvites->execute();
$totalInvites = $totalInvites->fetchColumn();

$present = $pdo->prepare("
SELECT COUNT(*)
FROM invites i
JOIN events e ON e.generat=i.generat_event
AND i.rsvp_status='Present'
");
$present->execute();
$present = $present->fetchColumn();

$absent = $pdo->prepare("
SELECT COUNT(*)
FROM invites i
JOIN events e ON e.generat=i.generat_event
WHERE i.rsvp_status='Absent'
");
$absent->execute();
$absent = $absent->fetchColumn();

$pending = $pdo->prepare("
SELECT COUNT(*)
FROM invites i
JOIN events e ON e.generat=i.generat_event
WHERE i.rsvp_status='En attente'
");
$pending->execute();
$pending = $pending->fetchColumn();

$gallery = $pdo->prepare("
SELECT COUNT(*)
FROM gallery g
JOIN events e ON e.generat=g.generat_event
");
$gallery->execute();
$gallery = $gallery->fetchColumn();

$guestbook = $pdo->prepare("
SELECT COUNT(*)
FROM guestbook g
JOIN events e ON e.generat=g.generat_event
");
$guestbook->execute();
$guestbook = $guestbook->fetchColumn();

/*
|--------------------------------------------------------------------------
| EVENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
SELECT e.*,
(
    SELECT COUNT(*)
    FROM invites i
    WHERE i.generat_event=e.id
) total_invites
FROM events e
ORDER BY e.event_date DESC
LIMIT 10
");

$stmt->execute();
$events = $stmt->fetchAll();

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold" style="color: var(--dark-slate);">
                <i class="bi bi-grid-1x2-fill me-2" style="color: var(--primary-rose);"></i>
                Tableau de bord
            </h2>
            <small class="text-muted">
                Bienvenue dans votre espace H-Event
            </small>
        </div>

        <a href="../events/form_event.php" class="btn btn-primary shadow-sm"
            style="background-color: var(--dark-slate); border-color: var(--dark-slate); color: var(--primary-rose); font-weight: 500; border-radius: 12px; padding: 10px 20px;">
            <i class="bi bi-plus-circle me-2"></i>
            Nouvel événement
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-box orange">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-semibold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Événements</h6>
                        <h2 class="fw-bold mb-0"><?= $totalEvents ?></h2>
                    </div>
                    <i class="bi bi-calendar-check fs-2 opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-box purple">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-semibold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Invités</h6>
                        <h2 class="fw-bold mb-0"><?= $totalInvites ?></h2>
                    </div>
                    <i class="bi bi-people fs-2 opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-box green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-semibold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Présents</h6>
                        <h2 class="fw-bold mb-0"><?= $present ?></h2>
                    </div>
                    <i class="bi bi-check-circle fs-2 opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-box pink">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase fw-semibold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                            Absents</h6>
                        <h2 class="fw-bold mb-0"><?= $absent ?></h2>
                    </div>
                    <i class="bi bi-x-circle fs-2 opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="dashboard-card">
                <h5 class="fw-bold mb-4" style="color: var(--dark-slate);">Suivi des RSVP</h5>

                <?php
                $totalRsvp = $present + $absent + $pending;
                $presentPct = $totalRsvp ? round(($present/$totalRsvp)*100) : 0;
                $absentPct = $totalRsvp ? round(($absent/$totalRsvp)*100) : 0;
                $pendingPct = $totalRsvp ? round(($pending/$totalRsvp)*100) : 0;
                ?>

                <div class="mb-3">
                    <div class="d-flex justify-content-between font-weight-500 mb-1" style="font-size: 0.9rem;">
                        <span>Présents</span>
                        <span class="fw-bold text-success"><?= $presentPct ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 4px; background-color: #f0f0f0;">
                        <div class="progress-bar bg-success" style="width:<?= $presentPct ?>%; border-radius: 4px;">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between font-weight-500 mb-1" style="font-size: 0.9rem;">
                        <span>Absents</span>
                        <span class="fw-bold text-danger"><?= $absentPct ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 4px; background-color: #f0f0f0;">
                        <div class="progress-bar bg-danger" style="width:<?= $absentPct ?>%; border-radius: 4px;"></div>
                    </div>
                </div>

                <div class="mb-0">
                    <div class="d-flex justify-content-between font-weight-500 mb-1" style="font-size: 0.9rem;">
                        <span>En attente</span>
                        <span class="fw-bold text-warning"><?= $pendingPct ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 4px; background-color: #f0f0f0;">
                        <div class="progress-bar bg-warning" style="width:<?= $pendingPct ?>%; border-radius: 4px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card text-center d-flex flex-column justify-content-center align-items-center py-4">
                <div class="mb-2 d-inline-flex p-3 rounded-circle"
                    style="background-color: var(--bg-light); color: var(--primary-rose);">
                    <i class="bi bi-images fs-2"></i>
                </div>
                <h2 class="fw-bold mb-1" style="color: var(--dark-slate);"><?= $gallery ?></h2>
                <div class="text-muted" style="font-size: 0.95rem; font-weight: 500;">Photos de la galerie</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dashboard-card text-center d-flex flex-column justify-content-center align-items-center py-4">
                <div class="mb-2 d-inline-flex p-3 rounded-circle" style="background-color: #fff5f5; color: #e05252;">
                    <i class="bi bi-chat-heart-fill fs-2"></i>
                </div>
                <h2 class="fw-bold mb-1" style="color: var(--dark-slate);"><?= $guestbook ?></h2>
                <div class="text-muted" style="font-size: 0.95rem; font-weight: 500;">Messages reçus (Livre d'or)</div>
            </div>
        </div>
    </div>

    <div class="d-none dashboard-card p-0 overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
            style="background-color: #fafbfc; border-color: rgba(212, 163, 150, 0.12) !important;">
            <h5 class="fw-bold mb-0" style="color: var(--dark-slate);">
                <i class="bi bi-calendar3 me-2 text-muted"></i>
                Mes événements récents
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                <thead class="table-light text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                    <tr>
                        <th class="ps-4 py-3 text-muted">Titre</th>
                        <th class="py-3 text-muted">Type</th>
                        <th class="py-3 text-muted">Date</th>
                        <th class="py-3 text-muted">Invités</th>
                        <th class="py-3 text-muted">Statut</th>
                        <th class="pe-4 py-3 text-end text-muted">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                            Aucun événement créé pour le moment.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($events as $e): ?>
                    <tr>
                        <td class="ps-4 fw-semibold" style="color: var(--dark-slate);">
                            <?= htmlspecialchars($e['title']) ?></td>
                        <td><span class="text-secondary"><?= htmlspecialchars($e['event_type']) ?></span></td>
                        <td><i
                                class="bi bi-calendar3-event me-2 text-muted"></i><?= date('d/m/Y', strtotime($e['event_date'])) ?>
                        </td>
                        <td>
                            <span class="badge px-2.5 py-1.5"
                                style="background-color: var(--bg-light); color: var(--dark-slate); border: 1px solid rgba(212, 163, 150, 0.2); font-weight: 600; border-radius: 6px;">
                                <?= $e['total_invites'] ?> invités
                            </span>
                        </td>
                        <td>
                            <?php if (strtolower($e['STATUS']) == 'publie' || strtolower($e['STATUS']) == 'publié'): ?>
                            <span class="badge bg-success-subtle text-success px-3 py-2"
                                style="border-radius: 8px; font-weight: 500;">
                                <?= htmlspecialchars($e['STATUS']) ?>
                            </span>
                            <?php elseif (strtolower($e['STATUS']) == 'termine' || strtolower($e['STATUS']) == 'terminé'): ?>
                            <span class="badge bg-danger-subtle text-danger px-3 py-2"
                                style="border-radius: 8px; font-weight: 500;">
                                <?= htmlspecialchars($e['STATUS']) ?>
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2"
                                style="border-radius: 8px; font-weight: 500;">
                                <?= htmlspecialchars($e['STATUS']) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <a href="../events/event_show.php?id=<?= $e['generat'] ?>"
                                class="btn btn-sm btn-light border"
                                style="border-radius: 8px; color: var(--dark-slate); transition: all 0.2s;">
                                <i class="bi bi-eye-fill"></i> Voir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../../includes/footer.php'; ?>