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

// 2. Récupération de la liste des invités ayant répondu
$stmt = $pdo->prepare("
    SELECT i.*, t.table_name 
    FROM invites i
    LEFT JOIN event_tables t ON i.table_id = t.id
    WHERE i.generat_event = ?
    ORDER BY i.created_at DESC
");
$stmt->execute([$event_id]);
$guests_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Calcul des statistiques pour le graphique
$total_presents = 0;
$total_absents = 0;

foreach ($guests_list as $guest) {
    if ($guest['rsvp_status'] === 'present') {
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

        /* Ajuste cette couleur selon ta charte exacte */
        --dark-slate: #212529;
        /* Ajuste cette couleur selon ta charte exacte */
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

    /* Personnalisation de la scrollbar pour le tableau de droite */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="row g-4 justify-content-center">

            <div class="col-12 col-xl-4">
                <div class="card rsvp-card p-4 h-100 text-center d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="fw-bold text-secondary text-uppercase tracking-wider small mb-4">Aperçu des présences
                        </h6>

                        <div class="chart-container mb-4">
                            <canvas id="rsvpChart"></canvas>
                        </div>
                    </div>

                    <div class="d-flex justify-content-around border-top pt-3 mt-auto">
                        <div class="text-center">
                            <span class="fs-3 fw-bold text-success"><?= $total_presents ?></span>
                            <div class="text-muted small">
                                <i class="bi bi-circle-fill text-success me-1" style="font-size: 8px;"></i> Présents
                            </div>
                        </div>
                        <div class="text-center">
                            <span class="fs-3 fw-bold text-danger"><?= $total_absents ?></span>
                            <div class="text-muted small">
                                <i class="bi bi-circle-fill text-danger me-1" style="font-size: 8px;"></i> Absents
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="card rsvp-card h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-people-fill me-2" style="color: var(--primary-rose);"></i>
                            <span>Liste des réponses</span>
                            <span
                                class="badge ms-2 px-2 py-1 rounded-pill bg-light text-dark border small fw-normal"><?= count($guests_list) ?></span>
                        </h5>
                    </div>

                    <div class="card-body px-4 pb-4 pt-2">
                        <div class="table-responsive custom-scrollbar" style="max-height: 440px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary sticky-top"
                                    style="top: 0; z-index: 5; box-shadow: 0 1px 0 rgba(0,0,0,0.05);">
                                    <tr>
                                        <th class="py-3">Nom Invité</th>
                                        <th class="py-3">Statut</th>
                                        <th class="py-3">Table assignée</th>
                                        <th class="py-3">Date réponse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($guests_list)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i
                                                class="bi bi-chat-left-dots fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                            Aucune réponse enregistrée pour le moment.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach($guests_list as $guest): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark py-3">
                                            <?= htmlspecialchars($guest['fullname']) ?>
                                        </td>
                                        <td class="py-3">
                                            <?php if ($guest['rsvp_status'] === 'present'): ?>
                                            <span
                                                class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill small border border-success-subtle">
                                                <i class="bi bi-check-circle-fill me-1"></i> Présent
                                            </span>
                                            <?php else: ?>
                                            <span
                                                class="badge bg-danger-subtle text-danger px-2.5 py-1.5 rounded-pill small border border-danger-subtle">
                                                <i class="bi bi-x-circle-fill me-1"></i> Absent
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3">
                                            <?php if (!empty($guest['table_name'])): ?>
                                            <span
                                                class="text-secondary small fw-medium bg-light px-2.5 py-1.5 rounded border">
                                                <i class="bi bi-grid-3x3-gap me-1 text-muted"></i>
                                                <?= htmlspecialchars($guest['table_name']) ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted fst-italic small opacity-75">Non assigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small py-3">
                                            <?= date('d/m Y à H:i', strtotime($guest['created_at'])) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
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
            type: 'doughnut', // Remplacement de 'pie' par 'doughnut' pour plus de modernité
            data: {
                labels: ['Présent', 'Absent'],
                datasets: [{
                    data: [<?= $total_presents ?>, <?= $total_absents ?>],
                    backgroundColor: [
                        '#198754', // Vert bootstrap success
                        '#dc3545' // Rouge bootstrap danger
                    ],
                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', // Épaisseur de l'anneau du doughnut
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        cornerRadius: 8,
                        padding: 10
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