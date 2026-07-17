<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/constant.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

/* --- VÉRIFICATION ÉVÉNEMENT --- */
$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ? AND user_id = ? LIMIT 1");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) { die("Événement introuvable ou accès refusé."); }

/* --- LOGIQUE STATISTIQUES RSVP --- */
$stmt = $pdo->prepare("SELECT rsvp_status, COUNT(*) as total FROM invites WHERE generat_event = ? GROUP BY rsvp_status");
$stmt->execute([$event_id]);
$data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$total   = array_sum($data);
$present = $data['Present'] ?? 0;
$absent  = $data['Absent'] ?? 0;
$pending = $data['En attente'] ?? ($data['Pending'] ?? ($total - $present - $absent));

$pct_present = $total > 0 ? round(($present / $total) * 100, 1) : 0;
$pct_absent  = $total > 0 ? round(($absent / $total) * 100, 1) : 0;
$pct_pending = $total > 0 ? round(($pending / $total) * 100, 1) : 0;

/* --- DONNÉES DÉTAILLÉES (Invités + Réponses + Boissons) --- */
$stmt = $pdo->prepare("
    SELECT i.fullname, i.rsvp_status, d.drink_name, tb.table_name
    FROM invites i
    LEFT JOIN guest_drink_choices gc ON i.id = gc.invite_id
    LEFT JOIN event_drinks d ON gc.drink_id = d.id
    LEFT JOIN event_tables tb ON i.table_id = tb.id
    WHERE i.generat_event = ?
    ORDER BY i.fullname ASC
");
$stmt->execute([$event_id]);
$invites_list = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT d.drink_name, COUNT(gc.id) as total_choix
    FROM event_drinks d
    LEFT JOIN guest_drink_choices gc ON d.id = gc.drink_id
    WHERE d.generat_event = ?
    GROUP BY d.id
");
$stmt->execute([$event_id]);
$drinks_stats = $stmt->fetchAll();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-bar-chart-fill text-primary"></i> Statistiques de
                l'événement</h1>
            <p class="mb-0 text-muted">Événement : <strong><?= htmlspecialchars($event['title']) ?></strong></p>
        </div>
        <button onclick="history.back()" class="btn btn-outline-secondary btn-md"
            style="border-radius: 10px; font-weight: 600; font-size: 20px;">
            <i class="bi bi-arrow-left"></i>
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div><span class="text-uppercase text-muted small fw-bold">Total Invités</span>
                        <h2 class="display-6 fw-bold text-primary mb-0"><?= $total ?></h2>
                    </div><i class="bi bi-people fs-2 text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div><span class="text-uppercase text-muted small fw-bold">Présents</span>
                        <h2 class="display-6 fw-bold text-success mb-0"><?= $present ?></h2>
                    </div><i class="bi bi-check-circle-fill fs-2 text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-danger bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div><span class="text-uppercase text-muted small fw-bold">Absents</span>
                        <h2 class="display-6 fw-bold text-danger mb-0"><?= $absent ?></h2>
                    </div><i class="bi bi-x-circle-fill fs-2 text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div><span class="text-uppercase text-muted small fw-bold">En attente</span>
                        <h2 class="display-6 fw-bold text-warning mb-0"><?= $pending ?></h2>
                    </div><i class="bi bi-hourglass-split fs-2 text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-pie-chart"></i> Répartition des réponses</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-sm-6 text-center">
                            <div style="height:220px; width:220px; margin:auto;"><canvas id="rsvpChart"></canvas></div>
                        </div>
                        <div class="col-sm-6">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between border-0">
                                    <span>Présents</span> <span class="fw-bold"><?= $present ?>
                                        (<?= $pct_present ?>%)</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between border-0"><span>Absents</span>
                                    <span class="fw-bold"><?= $absent ?> (<?= $pct_absent ?>%)</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between border-0"><span>En
                                        attente</span> <span class="fw-bold"><?= $pending ?>
                                        (<?= $pct_pending ?>%)</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0 fw-bold"><i class="bi bi-cup-hot-fill"></i> Préférences
                    boissons</div>
                <div class="card-body">
                    <?php foreach($drinks_stats as $d): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span><?= htmlspecialchars($d['drink_name']) ?></span><span
                                class="fw-bold"><?= $d['total_choix'] ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info"
                                style="width: <?= ($total > 0) ? ($d['total_choix']/$total)*100 : 0 ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill"></i> Liste des réponses</h5>
                    <div class="d-flex gap-2">
                        <input type="text" id="searchInput" class="form-control form-control-sm"
                            placeholder="Rechercher un invité...">
                        <button onclick="exportTableToExcel('invitesTable', 'liste_invites')"
                            class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="invitesTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Nom</th>
                                    <th>Table</th>
                                    <th>Statut</th>
                                    <th class="pe-3">Boisson</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php foreach($invites_list as $i): 
                                $badge = ($i['rsvp_status'] == 'Present') ? 'bg-success' : (($i['rsvp_status'] == 'Absent') ? 'bg-danger' : 'bg-warning');
                            ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?= htmlspecialchars($i['fullname']) ?></td>
                                    <td><span
                                            class="badge bg-primary"><?= htmlspecialchars($i['table_name'] ?? 'N/A') ?></span>
                                    </td>
                                    <td><span class="badge <?= $badge ?>"><?= $i['rsvp_status'] ?></span></td>
                                    <td class="pe-3"><span
                                            class="badge bg-danger border"><?= htmlspecialchars($i['drink_name'] ?? 'Non défini') ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('rsvpChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Présents', 'Absents', 'En attente'],
            datasets: [{
                data: [<?= $present ?>, <?= $absent ?>, <?= $pending ?>],
                backgroundColor: ['#198754', '#dc3545', '#ffc107']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '70%'
        }
    });
});

// Fonction de recherche en temps réel
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#tableBody tr');

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Fonction d'export Excel
function exportTableToExcel(tableID, filename = '') {
    let downloadLink;
    let dataType = 'application/vnd.ms-excel';
    let tableSelect = document.getElementById(tableID);
    let tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

    filename = filename ? filename + '.xls' : 'excel_data.xls';
    downloadLink = document.createElement("a");

    document.body.appendChild(downloadLink);

    if (navigator.msSaveOrOpenBlob) {
        let blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>