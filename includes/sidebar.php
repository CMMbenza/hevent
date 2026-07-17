<?php
// On récupère le 'generat' si c'est un utilisateur simple
$event_generat = null;
if ($_SESSION['role'] === 'user') {
    $stmt = $pdo->prepare("SELECT generat FROM events WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($event) {
        $event_generat = $event['generat'];
    }
}
?>

<style>
.logo-box {
    display: flex;
    justify-content: space-between;
    /* Espace entre le texte et le bouton */
    align-items: center;
    padding: 20px;
}

.close-sidebar-btn {
    display: none;
    /* Caché par défaut */
    background: none;
    border: none;
    color: #ffffff;
    /* Couleur du X */
    font-size: 1.8rem;
    cursor: pointer;
}

@media (max-width: 768px) {
    .close-sidebar-btn {
        display: block;
        /* Affiché sur mobile */
    }
}
</style>

<div class="sidebar" id="appSidebar">
    <div class="logo-box">
        <a href="../events/event_show.php?action=show&id=<?= urlencode($event_generat) ?>" class="logo-text"
            id="logoText">H-Event</a>
        <!-- Bouton de fermeture pour mobile (visible uniquement en petit écran via CSS) -->
        <button class="close-sidebar-btn" onclick="toggleSidebar()">
            <i class="bi bi-x"></i>
        </button>
    </div>

    <ul class="menu">
        <?php if ($_SESSION['role'] === 'user'): ?>
        <!-- Menu spécifique pour rôle 'user' -->
        <li><a href="../events/event_show.php?action=show&id=<?= urlencode($event_generat) ?>"><i
                    class="bi bi-grid"></i>
                <span>Dashboard</span></a></li>
        <li><a href="../invites/?event_id=<?= urlencode($event_generat) ?>"><i class="bi bi-people"></i>
                <span>Invités</span></a></li>
        <li><a href="../tables/?event_id=<?= urlencode($event_generat) ?>"><i class="bi bi-table"></i>
                <span>Table</span></a></li>
        <li><a href="../rsvp/?event_id=<?= urlencode($event_generat) ?>"><i class="bi bi-envelope-check"></i>
                <span>RSVP</span></a></li>
        <li><a href="../gallery/?event_id=<?= urlencode($event_generat) ?>"><i class="bi bi-images"></i>
                <span>Galerie</span></a></li>
        <li><a href="../stats/?event_id=<?= urlencode($event_generat) ?>"><i class="bi bi-graph-up"></i>
                <span>Statistique</span></a></li>
        <li><a href="../events/form_event.php?action=edit&id=<?= urlencode($event_generat) ?>"><i
                    class="bi bi-pencil"></i> <span>Modifier event</span></a></li>
        <li><a href="../drinks/?event_id=<?= urlencode($event_generat) ?>"><i class="bi bi-cup-hot"></i>
                <span>Boissons</span></a></li>
        <?php else: ?>
        <!-- Menu pour admin/super-admin -->
        <li class="active"><a href="../dashboard/"><i class="bi bi-grid"></i> <span>Dashboard</span></a></li>
        <li><a href="../events/"><i class="bi bi-calendar-event"></i> <span>Événements</span></a></li>
        <li><a href="../stats/general_account.php"><i class="bi bi-bar-chart"></i> <span>Statistiques</span></a></li>
        <li><a href="../profile/"><i class="bi bi-person"></i> <span>Profil</span></a></li>
        <?php endif; ?>

        <!-- Menu commun -->
        <li class="mt-4"><a href="../../auth/logout.php" style="color: #c94a4a;"><i class="bi bi-box-arrow-right"></i>
                <span>Déconnexion</span></a></li>
    </ul>
</div>

<div class="main-content">

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('appSidebar');
        sidebar.classList.toggle('active'); // Ou votre logique de classe pour fermer
    }
    </script>