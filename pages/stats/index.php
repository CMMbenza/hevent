<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/constant.php';

// Redirection immédiate si non connecté (avant d'inclure le HTML)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0) {
    header("Location: dashboard.php"); // Ou ta page par défaut
    exit;
}

/*
----------------------------------
VÉRIFICATION DE L'ÉVÉNEMENT
----------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM events
    WHERE generat = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Événement introuvable ou accès refusé.");
}

/*
----------------------------------
RÉCUPÉRATION DES STATISTIQUES RSVP
----------------------------------
*/
$stmt = $pdo->prepare("
    SELECT rsvp_status, COUNT(*) as total
    FROM invites
    WHERE generat_event = ?
    GROUP BY rsvp_status
");
$stmt->execute([$event_id]);
$data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Calcul des compteurs
$total   = array_sum($data);
$present = $data['Present'] ?? 0;
$absent  = $data['Absent'] ?? 0;
// Prise en compte de 'En attente' ou 'Pending' selon ton énumération en BDD
$pending = $data['En attente'] ?? ($data['Pending'] ?? ($total - $present - $absent));

// Calcul des pourcentages pour la légende du graphique
$pct_present = $total > 0 ? round(($present / $total) * 100, 1) : 0;
$pct_absent  = $total > 0 ? round(($absent / $total) * 100, 1) : 0;
$pct_pending = $total > 0 ? round(($pending / $total) * 100, 1) : 0;

// Inclusions graphiques après les traitements logiques
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<div class="container-fluid px-4">

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-bar-chart-fill text-primary"></i> Statistiques du site
            </h1>
            <p class="mb-0 text-muted">Événement : <strong><?= htmlspecialchars($event['title']) ?></strong></p>
        </div>
        <a href="../events/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <span class="text-uppercase tracking-wider text-muted small fw-bold">Total Invités</span>
                        <h2 class="display-6 fw-bold text-primary mb-0 mt-1"><?= $total ?></h2>
                    </div>
                    <div class="bg-primary text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px;">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <span class="text-uppercase tracking-wider text-muted small fw-bold">Présents</span>
                        <h2 class="display-6 fw-bold text-success mb-0 mt-1"><?= $present ?></h2>
                    </div>
                    <div class="bg-success text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px;">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-danger bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <span class="text-uppercase tracking-wider text-muted small fw-bold">Absents</span>
                        <h2 class="display-6 fw-bold text-danger mb-0 mt-1"><?= $absent ?></h2>
                    </div>
                    <div class="bg-danger text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px;">
                        <i class="bi bi-x-circle-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <span class="text-uppercase tracking-wider text-muted small fw-bold">En attente</span>
                        <h2 class="display-6 fw-bold text-warning mb-0 mt-1"><?= $pending ?></h2>
                    </div>
                    <div class="bg-warning text-dark rounded-circle p-3 d-inline-flex align-items-center justify-content-center"
                        style="width: 60px; height: 60px;">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-12 col-lg-12 mx-auto">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title mb-0 text-gray-800 fw-bold"><i class="bi bi-pie-chart"></i> Répartition des
                        réponses</h5>
                </div>
                <div class="card-body">
                    <?php if ($total > 0): ?>
                    <div class="row align-items-center">
                        <div class="col-sm-6 text-center">
                            <div style="position: relative; height:220px; width:220px; margin:auto;">
                                <canvas id="rsvpChart"></canvas>
                            </div>
                        </div>
                        <div class="col-sm-6 mt-3 mt-sm-0">
                            <ul class="list-group list-group-flush">
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                    <span><i class="bi bi-circle-fill text-success me-2"></i> Présents</span>
                                    <span class="fw-bold"><?= $present ?> (<?= $pct_present ?>%)</span>
                                </li>
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                    <span><i class="bi bi-circle-fill text-danger me-2"></i> Absents</span>
                                    <span class="fw-bold"><?= $absent ?> (<?= $pct_absent ?>%)</span>
                                </li>
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                    <span><i class="bi bi-circle-fill text-warning me-2"></i> En attente</span>
                                    <span class="fw-bold"><?= $pending ?> (<?= $pct_pending ?>%)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-exclamation-circle fs-2 d-block mb-2"></i>
                        Aucun invité enregistré pour le moment. Le graphique s'affichera dès que vous aurez des invités.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if ($total > 0): ?>
    const ctx = document.getElementById('rsvpChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Présents', 'Absents', 'En attente'],
            datasets: [{
                data: [<?= $present ?>, <?= $absent ?>, <?= $pending ?>],
                backgroundColor: [
                    '#198754', // Vert (success)
                    '#dc3545', // Rouge (danger)
                    '#ffc107' // Jaune (warning)
                ],
                borderWidth: 2,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Désactivé car on a notre propre légende personnalisée en HTML à côté
                }
            },
            cutout: '70%' // Donne l'effet anneau moderne
        }
    });
    <?php endif; ?>
});
</script>

<?php include '../../includes/footer.php'; ?>