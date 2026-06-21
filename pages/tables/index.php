<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$event_id = $_GET['event_id'] ?? 0;

// Vérification de l'existence de l'événement et droits de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ? AND user_id = ?");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch();

if (!$event) {
    die("Événement introuvable ou accès refusé.");
}

// Récupération des tables associées
$stmt = $pdo->prepare("
    SELECT t.*,
    (
        SELECT COUNT(*)
        FROM invites i
        WHERE i.table_id = t.id
    ) AS guests
    FROM event_tables t
    WHERE t.generat_event = ?
    ORDER BY t.table_name ASC
");
$stmt->execute([$event_id]);
$tables = $stmt->fetchAll();

// Préparation des données pour le Graphique Chart.js
$chartLabels = [];
$chartData = [];
foreach ($tables as $t) {
    $chartLabels[] = htmlspecialchars($t['table_name'], ENT_QUOTES, 'UTF-8');
    $chartData[] = (int)$t['guests'];
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<div class="container-fluid py-4">

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1 fw-bold" style="color: var(--primary-rose);">
                    <i class="bi bi-table me-2" style="color: var(--primary-rose);"></i>Gestion des tables
                </h4>
                <p class="text-muted mb-0 small">
                    Événement : <strong class="text-dark"><?= htmlspecialchars($event['title']) ?></strong>
                </p>
            </div>
            
            <button class="btn btn-success px-3" data-bs-toggle="modal" data-bs-target="#addTableModal"
            style="background-color: var(--dark-slate); border-color: var(--dark-slate); color: var(--primary-rose); font-weight: 500; border-radius: 12px; padding: 10px 20px;">
                <i class="bi bi-plus-circle me-1"></i> Nouvelle table
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th class="ps-4">Nom de la Table</th>
                            <th>Invités installés</th>
                            <th>Date de création</th>
                            <th class="pe-4 text-end" width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tables)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-info-circle me-1"></i> Aucune table configurée pour le moment.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($tables as $t): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">
                                <i class="bi bi-grid-3x3-gap me-2 text-muted"></i>
                                <?= htmlspecialchars($t['table_name']) ?>
                            </td>
                            <td>
                                <span class="badge bg-primary px-2.5 py-1.5 rounded-pill">
                                    <?= $t['guests'] ?> invité(s)
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?= date('d/m/Y', strtotime($t['created_at'])) ?>
                            </td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal"
                                    data-bs-target="#edit<?= $t['id'] ?>" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#delete<?= $t['id'] ?>" title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
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

 <?php if (!empty($tables)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-white" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold" style="color: var(--primary-rose);">
                        <i class="bi bi-bar-chart-fill me-2"></i>Occupation des tables (Nombre d'invités)
                    </h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height:240px; width:100%">
                        <canvas id="tablesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
<?php foreach($tables as $t): ?>
<div class="modal fade" id="edit<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="table_action.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2 text-warning"></i>Modifier la table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update" value="1">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nom ou numéro de la table</label>
                        <input type="text" name="table_name" value="<?= htmlspecialchars($t['table_name']) ?>"
                            class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold">Enregistrer les
                        modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="delete<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Confirmation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                Êtes-vous sûr de vouloir supprimer définitivement la table
                <strong><?= htmlspecialchars($t['table_name']) ?></strong> ? Les invités associés y seront désassignés.
            </div>
            <div class="modal-footer bg-light border-0">
                <form action="table_action.php" method="POST" class="w-100 d-flex gap-2">
                    <input type="hidden" name="delete" value="1">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">

                    <button type="button" class="btn btn-secondary flex-grow-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger flex-grow-1"><i class="bi bi-trash me-1"></i>
                        Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="table_action.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-success"></i>Ajouter une
                        nouvelle table</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="create" value="1">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nom de la table</label>
                        <input type="text" name="table_name" class="form-control"
                            placeholder="Ex: Table d'honneur, Table 5..." required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-sm btn-success">Créer la table</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const el = document.getElementById('tablesChart');
    if (el) {
        const ctx = el.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Nombre d\'invités',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                    borderColor: '#0d6efd',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
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
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: '#6c757d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            borderDash: [5, 5]
                        }
                    },
                    x: {
                        ticks: {
                            color: '#6c757d'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>