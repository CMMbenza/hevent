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

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

$info = $_SESSION['info'] ?? null;
unset($_SESSION['info']);

/*
----------------------------------
FLASH MESSAGE
----------------------------------
*/
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

/*
----------------------------------
EVENT LIST
----------------------------------
*/
$stmt = $pdo->prepare("
    SELECT * FROM events
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparation des données pour le Graphique de la Charte
$chartLabels = [];
$chartCurrentData = [];
$chartLimitData = [];

$totalEvents = count($events);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if ($success): ?>
<div id="successPopup" class="success-popup">
    <div class="success-box">
        <div class="check">✔</div>
        <h4><?= $success ?></h4>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="fw-bold" style="color: var(--dark-slate);">
                <i class="bi bi-calendar-plus me-2" style="color: var(--primary-rose);"></i>
                Evénement
            </h2>
            <small class="text-muted">
                Événements au total
            </small>
        </div>

        <a href="../events/form_event.php" class="btn btn-primary shadow-sm"
            style="background-color: var(--dark-slate); border-color: var(--dark-slate); color: var(--primary-rose); font-weight: 500; border-radius: 12px; padding: 10px 20px;">
            <i class="bi bi-plus-circle me-2"></i>
            Nouvel événement
        </a>
    </div>

    <div class="row p-3 g-4">
        <div class="col-12 col-lg-8 col-xl-9">
            <div class="row g-3">
                <?php foreach ($events as $event): ?>
                <?php
                $pack = getPack($pdo, $event['pack_code']);
                $current = countInvitesByEvent($pdo, $event['generat']);
                $limit = $pack['max_invites'] ?? 0;
                $percent = ($limit <= 0 || $limit == -1) ? 0 : ($current / $limit) * 100;

                // Injection des données pour Chart.js
                $chartLabels[] = htmlspecialchars($event['title']);
                $chartCurrentData[] = $current;
                $chartLimitData[] = ($limit == -1) ? $current : $limit;
                ?>

                <div class="col-12 col-md-6 col-xl-6">
                    <div class="dashboard-card shadow-sm p-3 rounded h-100"
                        style="background-color: #ffffff; border: 1px solid rgba(0,0,0,0.05); position: relative; display: flex; flex-direction: column; justify-content: space-between;">

                        <div>
                            <span class="badge position-absolute bg-light text-primary fw-bold border"
                                style="z-index: 10; font-size: 0.8rem; padding: 6px 10px; border-radius: 8px; right: 15px; top: 15px;">
                                #<?= $event['generat'] ?>
                            </span>

                            <?php if (!empty($event['cover_image'])): ?>
                            <img src="../../uploads/covers/<?= $event['cover_image'] ?>"
                                style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:12px;">
                            <?php else: ?>
                            <div
                                style="width:100%;height:160px;border-radius:10px;margin-bottom:12px; background:#f8f9fa;display:flex;align-items:center;justify-content:center; border: 1px dashed #dee2e6;">
                                <span class="text-muted"><i class="bi bi-image me-1"></i>Aucune image</span>
                            </div>
                            <?php endif; ?>

                            <h5 class="fw-bold mb-1" style="color: var(--dark-slate);">
                                <?= htmlspecialchars($event['title']) ?></h5>

                            <p class="mb-1 text-truncate" style="font-size: 0.9rem; color: #495057;">
                                <i
                                    class="bi bi-geo-alt-fill text-danger me-1"></i><strong><?= htmlspecialchars($event['lieu'] ?? '') ?></strong>
                            </p>
                            <p class="text-muted text-truncate mb-2"
                                style="font-size: 0.82rem; padding-left: 17px; font-style: italic;">
                                <?= htmlspecialchars($event['location']) ?>
                            </p>

                            <div class="mb-2" style="font-size: 0.85rem; color: #6c757d;">
                                <i class="bi bi-clock me-1"></i><?= $event['event_date'] ?> à
                                <?= $event['event_time'] ?>
                            </div>

                            <div class="mt-2 mb-3">
                                <span class="badge bg-primary" style="opacity: 0.85;"><?= $event['event_type'] ?></span>
                                <span class="badge bg-secondary"
                                    style="opacity: 0.85;"><?= $pack['name'] ?? 'Aucun Pack' ?></span>
                            </div>
                        </div>

                        <div>
                            <div class="mt-3 pt-2 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-1 mt-2">
                                    <small class="text-muted">Jauge d'invités :</small>
                                    <small class="fw-bold <?= $percent >= 100 ? 'text-danger' : 'text-success' ?>">
                                        <?= $current ?> / <?= $limit == -1 ? "∞" : $limit ?>
                                    </small>
                                </div>
                                <div class="progress" style="height:8px; border-radius: 4px;">
                                    <div class="progress-bar <?= $percent >= 100 ? 'bg-danger' : 'bg-success' ?>"
                                        style="width: <?= min($percent, 100) ?>%"></div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2 pt-2 border-top">
                                <a href="event_show.php?action=show&id=<?= $event['generat'] ?>"
                                    class="btn btn-sm btn-outline-primary flex-grow-1" style="border-radius: 8px;">
                                    <i class="bi bi-eye me-1"></i>Voir
                                </a>
                                <a href="form_event.php?action=edit&id=<?= $event['generat'] ?>"
                                    class="btn btn-sm btn-outline-warning" style="border-radius: 8px;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="event_delete.php?action=delete&id=<?= $event['generat'] ?>"
                                    class="btn btn-sm btn-outline-danger" style="border-radius: 8px;"
                                    onclick="return confirm('Supprimer cet événement définitivement ?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow-sm h-100 border-0" style="background-color: #ffffff; border-radius: 15px;">
                <div class="card-header bg-white border-0 pt-3 fw-bold" style="color: var(--dark-slate, #1e293b);">
                    📊 Aperçu de la Jauge d'Invités par Événement
                </div>
                <div class="card-body p-3">
                    <?php if ($totalEvents > 0): ?>
                    <div style="position: relative; width: 100%; height: 180px;">
                        <canvas id="globalEventsChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">Aucune statistique disponible</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.success-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn .3s ease;
}

.success-box {
    background: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: pop .4s ease;
}

.success-box .check {
    width: 60px;
    height: 60px;
    background: #28a745;
    color: white;
    font-size: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px auto;
}

@keyframes pop {
    0% {
        transform: scale(0.5);
        opacity: 0
    }

    100% {
        transform: scale(1);
        opacity: 1
    }
}

@keyframes fadeIn {
    from {
        opacity: 0
    }

    to {
        opacity: 1
    }
}
</style>

<script>
// Exécution sécurisée après le chargement complet du DOM
document.addEventListener("DOMContentLoaded", function() {
    <?php if ($totalEvents > 0): ?>
    const ctx = document.getElementById('globalEventsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                    label: 'Invités Actuels',
                    data: <?= json_encode($chartCurrentData) ?>,
                    backgroundColor: '#198754', // Vert conforme Bootstrap
                    borderRadius: 5
                },
                {
                    label: 'Limite du Pack',
                    data: <?= json_encode($chartLimitData) ?>,
                    backgroundColor: '#e2e8f0', // Gris clair épuré
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
    <?php endif; ?>
});

setTimeout(() => {
    const popup = document.getElementById('successPopup');
    if (popup) {
        popup.style.transition = "0.5s";
        popup.style.opacity = "0";
        setTimeout(() => popup.remove(), 500);
    }
}, 2500);
</script>

<?php include '../../includes/footer.php'; ?>