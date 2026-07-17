<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

$event_id = $_GET['event_id'] ?? 0;

// Vérification de l'existence de l'événement
$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) { die("Événement introuvable ou accès refusé."); }

// Récupération des boissons
$stmt = $pdo->prepare("
    SELECT d.*, 
    (SELECT COUNT(*) FROM guest_drink_choices gc WHERE gc.drink_id = d.id) AS total_choix
    FROM event_drinks d
    WHERE d.generat_event = ?
    ORDER BY d.drink_name ASC
");
$stmt->execute([$event_id]);
$drinks = $stmt->fetchAll();

// Données pour Chart.js
$chartLabels = [];
$chartData = [];
foreach ($drinks as $d) {
    $chartLabels[] = htmlspecialchars($d['drink_name'], ENT_QUOTES, 'UTF-8');
    $chartData[] = (int)$d['total_choix'];
}
?>

<style>
/* CSS pour garantir l'opacité des modales */
.modal-backdrop {
    z-index: 1050;
}

.modal {
    z-index: 1060;
}
</style>

<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1 fw-bold" style="color: var(--primary-rose);">
                    <i class="bi bi-cup-hot me-2"></i>Gestion des boissons
                </h4>
                <p class="text-muted mb-0 small">Événement : <strong><?= htmlspecialchars($event['title']) ?></strong>
                </p>
            </div>
            <div><button class="btn btn-success px-3" data-bs-toggle="modal" data-bs-target="#addDrinkModal"
                    style="background-color: var(--dark-slate); border-color: var(--dark-slate); color: var(--primary-rose); border-radius: 12px; padding: 10px 20px;">
                    <i class="bi bi-plus-circle me-1"></i> Nouvelle boisson
                </button>
                <button onclick="history.back()" class="btn btn-outline-secondary btn-md"
                    style="border-radius: 10px; font-weight: 600; font-size: 20px;">
                    <i class="bi bi-arrow-left"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="ps-4">Nom de la boisson</th>
                        <th>Choix des invités</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($drinks)): ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i>
                            Aucune boisson enregistrée.</td>
                    </tr>
                    <?php else: foreach($drinks as $d): ?>
                    <tr>
                        <td class="ps-4 fw-semibold text-dark"><i class="bi bi-cup-straw me-2 text-muted"></i>
                            <?= htmlspecialchars($d['drink_name']) ?></td>
                        <td><span class="badge bg-info rounded-pill"><?= $d['total_choix'] ?> choix</span></td>
                        <td class="pe-4 text-end">
                            <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal"
                                data-bs-target="#editDrink<?= $d['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteDrink<?= $d['id'] ?>"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($drinks)): ?>
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body"><canvas id="drinksChart" height="200"></canvas></div>
    </div>
    <?php endif; ?>
</div>

<?php foreach($drinks as $d): ?>
<div class="modal fade" id="editDrink<?= $d['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="drink_action.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la boisson</h5>
            </div>
            <div class="modal-body">
                <input type="hidden" name="update" value="1">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                <input type="text" name="drink_name" value="<?= htmlspecialchars($d['drink_name']) ?>"
                    class="form-control" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning">Enregistrer</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteDrink<?= $d['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="drink_action.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Confirmation</h5>
            </div>
            <div class="modal-body">
                <input type="hidden" name="delete" value="1">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                Êtes-vous sûr de vouloir supprimer <strong><?= htmlspecialchars($d['drink_name']) ?></strong> ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="addDrinkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="drink_action.php" method="POST" class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une boisson</h5>
            </div>
            <div class="modal-body">
                <input type="hidden" name="create" value="1">
                <input type="hidden" name="event_id" value="<?= $event_id ?>">
                <input type="text" name="drink_name" class="form-control" placeholder="Ex: Champagne" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Ajouter</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('drinksChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            data: <?= json_encode($chartData) ?>,
            backgroundColor: ['#d4a396', '#1a2232', '#6c757d']
        }]
    }
});
</script>

<?php include '../../includes/footer.php'; ?>