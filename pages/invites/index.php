<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../includes/constant.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

$event_id = $_GET['event_id'] ?? 0;

/*
----------------------------------
EVENT
----------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM events
    WHERE generat=? AND user_id=?
");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch();

if (!$event) {
    die("Evénement introuvable");
}

/*
----------------------------------
INVITES EVENT
----------------------------------
*/
$sql = "
SELECT i.*, e.title AS event_title, t.table_name
FROM invites i
JOIN events e ON e.generat = i.generat_event
LEFT JOIN event_tables t ON t.id = i.table_id
WHERE i.generat_event = ?
ORDER BY i.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$event['generat']]);
$invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
----------------------------------
STATS
----------------------------------
*/
$total = count($invites);

$present = 0;
$absent = 0;
$pending = 0;

foreach ($invites as $i) {
    if ($i['rsvp_status'] == 'Present') {
        $present++;
    } elseif ($i['rsvp_status'] == 'Absent') {
        $absent++;
    } else {
        $pending++;
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">

    <div class="card shadow-sm mb-4 border-0 mt-3" style="border-radius: 15px;">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="mb-1 fw-bold" style="color: var(--dark-slate, #1e293b);">
                    <i class="bi bi-people-fill me-2" style="color: var(--primary-rose, #ffafcc);"></i>
                    Gestion des invités
                </h4>
                <div class="text-muted fw-semibold">
                    <i class="bi bi-bookmark-star me-1"></i> <?= htmlspecialchars($event['title']) ?>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="form_invite.php?event_id=<?= $event['generat'] ?>" class="btn btn-success px-3"
                    style="border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-person-plus-fill me-1"></i> Ajouter invité
                </a>
                <a href="../rsvp/?event_id=<?= $event['generat'] ?>" class="btn btn-warning px-3 text-dark"
                    style="border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-check2-square me-1"></i> RSVP
                </a>
                <a href="../tables/?event_id=<?= $event['generat'] ?>" class="btn btn-dark px-3"
                    style="background-color: var(--dark-slate, #1e293b); border: none; border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-table me-1"></i> Tables
                </a>
                <a href="export_excel.php?event_id=<?= $event['generat'] ?>" class="btn btn-outline-success px-3"
                    style="border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </a>
                <a href="../events/event_show.php?id=<?= $event['generat'] ?>" class="btn btn-outline-secondary px-3"
                    style="border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-xl-8 col-lg-7">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm h-100"
                        style="border-radius: 12px; background-color: rgba(13, 110, 253, 0.05);">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-people-fill fs-2 text-primary"></i>
                            <h3 class="fw-bold mt-2 mb-0 text-primary"><?= $total ?></h3>
                            <small class="text-muted fw-semibold">Total invités</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm h-100"
                        style="border-radius: 12px; background-color: rgba(25, 135, 84, 0.05);">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                            <h3 class="fw-bold mt-2 mb-0 text-success"><?= $present ?></h3>
                            <small class="text-muted fw-semibold">Présents</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm h-100"
                        style="border-radius: 12px; background-color: rgba(220, 53, 69, 0.05);">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-x-circle-fill fs-2 text-danger"></i>
                            <h3 class="fw-bold mt-2 mb-0 text-danger"><?= $absent ?></h3>
                            <small class="text-muted fw-semibold">Absents</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm h-100"
                        style="border-radius: 12px; background-color: rgba(255, 193, 7, 0.08);">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                            <h3 class="fw-bold mt-2 mb-0 text-warning"><?= $pending ?></h3>
                            <small class="text-muted fw-semibold">En attente</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
                <div class="card-header bg-white fw-bold text-center border-0 pt-3"
                    style="color: var(--dark-slate, #1e293b);">
                    📊 Répartition des RSVP
                </div>
                <div class="card-body d-flex align-items-center justify-content-center p-3">
                    <?php if ($total > 0): ?>
                    <div style="position: relative; width: 100%; height: 200px;">
                        <canvas id="rsvpChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><i class="bi bi-pie-chart me-1"></i>Aucune donnée</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="input-group mb-4 shadow-sm" style="border-radius: 10px; overflow: hidden;">
        <span class="input-group-text border-0 bg-white text-muted px-3"><i class="bi bi-search"></i></span>
        <input type="text" id="liveSearch" class="form-control border-0 py-2"
            placeholder="Rechercher un invité par nom, téléphone, code ou table...">
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover align-middle mb-0" id="inviteTable">
                <thead style="background-color: var(--dark-slate, #1e293b); color: #ffffff;">
                    <tr>
                        <th class="ps-4 py-3">Nom</th>
                        <!-- <th class="py-3">Événement</th> -->
                        <th class="py-3">Téléphone</th>
                        <th class="py-3">Code</th>
                        <th class="py-3">Table</th>
                        <th class="py-3">RSVP</th>
                        <th class="pe-4 py-3 text-end" width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invites)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-person-x fs-3 d-block mb-2"></i> Aucun invité trouvé pour cet événement.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($invites as $i): ?>
                    <tr>
                        <td class="searchable ps-4 fw-bold" style="color: #495057;">
                            <i class="bi bi-person-circle me-2 text-muted"></i><?= htmlspecialchars($i['fullname']) ?>
                        </td>
                        <!-- <td class="searchable">
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($i['event_title']) ?>
                                </span>
                            </td> -->
                        <td class="searchable text-muted"><?= htmlspecialchars($i['phone'] ?? '-') ?></td>
                        <td class="searchable">
                            <span class="badge bg-secondary opacity-75"><?= $i['invite_code'] ?></span>
                        </td>
                        <td class="searchable">
                            <span class="badge text-dark bg-light border"><i
                                    class="bi bi-layout-text-sidebar-reverse me-1"></i><?= htmlspecialchars($i['table_name'] ?? '-') ?></span>
                        </td>
                        <td>
                            <?php if ($i['rsvp_status'] == 'Present'): ?>
                            <span class="badge bg-success px-2 py-1" style="border-radius: 6px;"><i
                                    class="bi bi-check-lg me-1"></i>Présent</span>
                            <?php elseif ($i['rsvp_status'] == 'Absent'): ?>
                            <span class="badge bg-danger px-2 py-1" style="border-radius: 6px;"><i
                                    class="bi bi-x-lg me-1"></i>Absent</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark px-2 py-1" style="border-radius: 6px;"><i
                                    class="bi bi-hourglass-split me-1"></i>En attente</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-1">

                                <?php
                                    $phone = preg_replace('/[^0-9]/', '', $i['phone'] ?? '');

                                    $eventId = urlencode($event['generat']);
                                    $code = urlencode($i['invite_code']);

                                    $link = "https://" . $_SERVER['HTTP_HOST'] . "/guest/invitation.php?event_id=$eventId&code=$code";

                                    $eventTitle = $event['title'] ?? 'Invitation événement';
                                    $guestName = $i['fullname'] ?? 'Invité';

                                    $message = "🎉 *$eventTitle*\n"
                                        . "Bonjour $guestName 👋\n\n"
                                        . "Vous êtes invité(e) à notre événement.\n\n"
                                        . "📌 Voir votre invitation :\n"
                                        . "$link\n\n"
                                        . "Merci 🙏";

                                    $waLink = !empty($phone)
                                        ? "https://wa.me/$phone?text=" . rawurlencode($message)
                                        : null;
                                    ?>

                                <!-- WHATSAPP -->
                                <?php if ($waLink): ?>
                                <a href="<?= $waLink ?>" target="_blank" class="btn btn-sm btn-success"
                                    style="border-radius: 6px;">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php endif; ?>

                                <!-- COPY LINK -->
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick="copyInvite('<?= htmlspecialchars($link, ENT_QUOTES) ?>')"
                                    title="Copier le lien" style="border-radius: 6px;">
                                    <i class="bi bi-clipboard"></i>
                                </button>

                                <!-- SHARE -->
                                <button class="btn btn-sm btn-outline-success"
                                    onclick="shareInvite('<?= htmlspecialchars($link, ENT_QUOTES) ?>')">
                                    <i class="bi bi-share"></i>
                                </button>

                                <!-- EDIT -->
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                                    data-bs-target="#edit<?= $i['id'] ?>" style="border-radius: 6px;">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <!-- DELETE -->
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#delete<?= $i['id'] ?>" style="border-radius: 6px;">
                                    <i class="bi bi-trash"></i>
                                </button>

                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="edit<?= $i['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                                <form method="POST" action="invite_action.php">
                                    <div class="modal-header border-0 pt-4 px-4">
                                        <h5 class="modal-title fw-bold" style="color: var(--dark-slate, #1e293b);"><i
                                                class="bi bi-pencil-square text-warning me-2"></i>Modifier l'invité</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body px-4">
                                        <input type="hidden" name="update" value="1">
                                        <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                        <input type="hidden" name="event_id" value="<?= $event['generat'] ?>">

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nom complet</label>
                                            <input type="text" name="fullname"
                                                value="<?= htmlspecialchars($i['fullname']) ?>" class="form-control"
                                                style="border-radius: 8px;" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold">Numéro de téléphone</label>
                                            <input type="text" name="phone"
                                                value="<?= htmlspecialchars($i['phone'] ?? '') ?>" class="form-control"
                                                style="border-radius: 8px;">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pb-4 px-4">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                                            style="border-radius: 8px;">Annuler</button>
                                        <button class="btn btn-warning" style="border-radius: 8px; font-weight:600;"><i
                                                class="bi bi-check-circle me-1"></i>Sauvegarder</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="delete<?= $i['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                                <div class="modal-header border-0 pt-4 px-4">
                                    <h5 class="modal-title fw-bold text-danger"><i
                                            class="bi bi-exclamation-triangle-fill me-2"></i>Suppression définitive</h5>
                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body px-4 py-3">
                                    Êtes-vous sûr de vouloir retirer
                                    <strong><?= htmlspecialchars($i['fullname']) ?></strong> de la liste des invités ?
                                </div>
                                <div class="modal-footer border-0 pb-4 px-4">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                                        style="border-radius: 8px;">Annuler</button>
                                    <form action="invite_action.php" method="POST" class="d-inline">
                                        <input type="hidden" name="delete" value="1">
                                        <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                        <input type="hidden" name="event_id" value="<?= $event['generat'] ?>">
                                        <button class="btn btn-danger" style="border-radius: 8px; font-weight:600;"><i
                                                class="bi bi-trash me-1"></i>Confirmer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function shareInvite(link) {
    if (navigator.share) {
        navigator.share({
            title: "Invitation Hevent",
            url: link
        });
    } else {
        navigator.clipboard.writeText(link).then(() => {
            showToast("Lien copié");
        });
    }
}

document.addEventListener("DOMContentLoaded", function() {
    <?php if ($total > 0): ?>
    const ctx = document.getElementById('rsvpChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Présents', 'Absents', 'En attente'],
            datasets: [{
                data: [<?= $present ?>, <?= $absent ?>, <?= $pending ?>],
                backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: {
                            weight: '600'
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
    <?php endif; ?>
});

// Moteur de recherche globale (Live Search)
document.getElementById("liveSearch").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#inviteTable tbody tr");
    rows.forEach(row => {
        if (row.cells.length > 1) {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? "" : "none";
        }
    });
});

function showToast(msg) {
    let toast = document.createElement('div');
    toast.className = "position-fixed bottom-0 end-0 bg-dark text-white p-3 m-3 rounded shadow-lg";
    toast.style.zIndex = "9999";
    toast.style.borderRadius = "10px";
    toast.innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i> ${msg}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

function copyInvite(link) {
    navigator.clipboard.writeText(link);
    showToast("Lien d'invitation copié !");
}
</script>

<?php include '../../includes/footer.php'; ?>