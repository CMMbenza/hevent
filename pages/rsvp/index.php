<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Récupération de l'ID de l'événement depuis l'URL
$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    die("L'identifiant de l'événement est manquant.");
}

// 1. Vérification de l'existence de l'événement
$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Événement introuvable.");
}

// 2. Récupération de la liste des invités
$stmt = $pdo->prepare("
    SELECT i.*, t.table_name 
    FROM invites i
    LEFT JOIN event_tables t ON i.table_id = t.id
    WHERE i.generat_event = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$event_id]);
$guests_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Calcul des statistiques (Correction de la casse et des espaces)
$total_presents = 0;
$total_absents = 0;

foreach ($guests_list as $guest) {
    $status = trim(strtolower($guest['rsvp_status']));
    if ($status === 'present') {
        $total_presents++;
    } else {
        $total_absents++;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponses RSVP - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --dark-slate: #212529;
        --bg-light: #f8fafc;
    }

    body {
        background-color: var(--bg-light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .rsvp-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        background-color: #ffffff;
    }

    .chart-container {
        position: relative;
        max-width: 200px;
        height: 200px;
        margin: 0 auto;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="card shadow-sm mb-4 border-0 mt-3" style="border-radius: 15px;">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1 fw-bold" style="color: var(--dark-slate, #1e293b);">
                        <i class="bi bi-people-fill me-2" style="color: var(--primary-rose, #ffafcc);"></i>
                        Gestion des réponses/invités
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

                    <a href="../stats/?event_id=<?= $event['generat'] ?>" class="btn btn-outline-success px-3"
                        style="border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-file-earmark-excel me-1"></i> Stats
                    </a>

                    <button onclick="history.back()" class="btn btn-outline-secondary btn-md"
                        style="border-radius: 10px; font-weight: 600; font-size: 20px;">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-12 col-xl-4">
                <div class="card rsvp-card p-4 h-100 text-center d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="fw-bold text-secondary text-uppercase tracking-wider small mb-4">Aperçu des présences
                        </h6>
                        <div class="chart-container mb-4"><canvas id="rsvpChart"></canvas></div>
                    </div>
                    <div class="d-flex justify-content-around border-top pt-3 mt-auto">
                        <div class="text-center">
                            <span class="fs-3 fw-bold text-success"><?= $total_presents ?></span>
                            <div class="text-muted small"><i class="bi bi-circle-fill text-success me-1"></i> Présents
                            </div>
                        </div>
                        <div class="text-center">
                            <span class="fs-3 fw-bold text-danger"><?= $total_absents ?></span>
                            <div class="text-muted small"><i class="bi bi-circle-fill text-danger me-1"></i> Absents
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card rsvp-card h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold text-dark mb-0">Liste des réponses <span
                                class="badge ms-2 bg-light text-dark border rounded-pill"><?= count($guests_list) ?></span>
                        </h5>
                    </div>
                    <div class="card-body px-4 pb-4 pt-2">
                        <div class="table-responsive custom-scrollbar" style="max-height: 440px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top" style="top: 0; z-index: 5;">
                                    <tr>
                                        <th>Nom Invité</th>
                                        <th>Statut</th>
                                        <th>Table assignée</th>
                                        <th>Date réponse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($guests_list as $guest): 
                                        $status = trim(strtolower($guest['rsvp_status']));
                                    ?>
                                    <tr>
                                        <td class="fw-semibold text-dark py-3">
                                            <?= htmlspecialchars($guest['fullname']) ?></td>
                                        <td class="py-3">
                                            <?php if ($status === 'present'): ?>
                                            <span
                                                class="badge bg-success-subtle text-success px-2 py-1 rounded-pill small border border-success-subtle"><i
                                                    class="bi bi-check-circle-fill me-1"></i> Présent</span>
                                            <?php else: ?>
                                            <span
                                                class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill small border border-danger-subtle"><i
                                                    class="bi bi-x-circle-fill me-1"></i> Absent</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3">
                                            <?= !empty($guest['table_name']) ? '<span class="text-secondary small bg-light px-2 py-1 rounded border">'.$guest['table_name'].'</span>' : '<span class="text-muted small">Non assigné</span>' ?>
                                        </td>
                                        <td class="text-muted small py-3">
                                            <?= date('d/m Y à H:i', strtotime($guest['created_at'])) ?></td>
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

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('rsvpChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Présent', 'Absent'],
                datasets: [{
                    data: [<?= $total_presents ?>, <?= $total_absents ?>],
                    backgroundColor: ['#198754', '#dc3545'],
                    borderWidth: 4,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../../includes/footer.php'; ?>
</body>

</html>