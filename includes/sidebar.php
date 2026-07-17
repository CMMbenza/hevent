<?php
// On récupère le 'generat' si c'est un utilisateur simple
$event_generat = null;
$has_event = false;

if ($_SESSION['role'] === 'user') {
    $stmt = $pdo->prepare("SELECT generat FROM events WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($event) {
        $event_generat = $event['generat'];
        $has_event = true;
    }
}
?>

<style>
.logo-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
}

.close-sidebar-btn {
    display: none;
    background: none;
    border: none;
    color: #ffffff;
    font-size: 1.8rem;
    cursor: pointer;
}

@media (max-width: 768px) {
    .close-sidebar-btn {
        display: block;
    }
}
</style>

<!-- Modal d'alerte création événement -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer votre événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Vous n'avez pas encore créé d'événement. Veuillez en créer un pour accéder à toutes les fonctionnalités
                de H-Event.
            </div>
            <div class="modal-footer">
                <a href="../events/form_event.php" class="btn btn-primary">Créer mon événement maintenant</a>
            </div>
        </div>
    </div>
</div>

<div class="sidebar" id="appSidebar">
    <div class="logo-box">
        <a href="<?= $has_event ? '../events/event_show.php?action=show&id='.urlencode($event_generat) : '#' ?>"
            onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)" class="logo-text"
            id="logoText">H-Event</a>
        <button class="close-sidebar-btn" onclick="toggleSidebar()">
            <i class="bi bi-x"></i>
        </button>
    </div>

    <ul class="menu">
        <?php if ($_SESSION['role'] === 'user'): ?>
        <li><a href="<?= $has_event ? '../events/event_show.php?action=show&id='.urlencode($event_generat) : '#' ?>"
                onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)"><i class="bi bi-grid"></i>
                <span>Dashboard</span></a></li>
        <li><a href="<?= $has_event ? '../invites/?event_id='.urlencode($event_generat) : '#' ?>"
                onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)"><i class="bi bi-people"></i>
                <span>Invités</span></a></li>
        <li><a href="<?= $has_event ? '../tables/?event_id='.urlencode($event_generat) : '#' ?>"
                onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)"><i class="bi bi-table"></i>
                <span>Table</span></a></li>
        <li><a href="<?= $has_event ? '../rsvp/?event_id='.urlencode($event_generat) : '#' ?>"
                onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)"><i class="bi bi-envelope-check"></i>
                <span>RSVP</span></a></li>
        <li><a href="<?= $has_event ? '../gallery/?event_id='.urlencode($event_generat) : '#' ?>"
                onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)"><i class="bi bi-images"></i>
                <span>Galerie</span></a></li>
        <li><a href="<?= $has_event ? '../stats/?event_id='.urlencode($event_generat) : '#' ?>"
                onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)"><i class="bi bi-graph-up"></i>
                <span>Statistique</span></a></li>
        <li><a href="../events/form_event.php?action=edit&id=<?= urlencode($event_generat) ?>"><i
                    class="bi bi-pencil"></i> <span>Modifier event</span></a></li>
        <li><a href="<?= $has_event ? '../drinks/?event_id='.urlencode($event_generat) : '#' ?>"
                onclick="checkEvent(event, <?= $has_event ? 'true' : 'false' ?>)"><i class="bi bi-cup-hot"></i>
                <span>Boissons</span></a></li>
        <?php else: ?>
        <li class="active"><a href="../dashboard/"><i class="bi bi-grid"></i> <span>Dashboard</span></a></li>
        <li><a href="../events/"><i class="bi bi-calendar-event"></i> <span>Événements</span></a></li>
        <li><a href="../stats/general_account.php"><i class="bi bi-bar-chart"></i> <span>Statistiques</span></a></li>
        <li><a href="../profile/"><i class="bi bi-person"></i> <span>Profil</span></a></li>
        <?php endif; ?>

        <li class="mt-4"><a href="../../auth/logout.php" style="color: #c94a4a;"><i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span></a></li>
    </ul>
</div>

<div class="main-content">
    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('appSidebar');
        sidebar.classList.toggle('active');
    }

    function checkEvent(event, hasEvent) {
        if (!hasEvent) {
            event.preventDefault();
            var myModal = new bootstrap.Modal(document.getElementById('createEventModal'));
            myModal.show();
        }
    }
    </script>