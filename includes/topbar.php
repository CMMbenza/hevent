<style>
.topbar {
    background-color: #ffffff;
    padding: 15px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 20px rgba(26, 34, 50, 0.04);
    border-bottom: 1px solid rgba(212, 163, 150, 0.15);
}

#sidebarToggle {
    background-color: var(--bg-light);
    color: var(--dark-slate);
    border: 1px solid rgba(212, 163, 150, 0.2) !important;
    border-radius: 10px;
    padding: 6px 12px;
    transition: all 0.2s ease;
}

#sidebarToggle:hover {
    background-color: var(--primary-rose);
    color: white;
    border-color: var(--primary-rose) !important;
}

.topbar .profile-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification-bell {
    color: var(--dark-slate);
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
    padding: 8px;
    border-radius: 5px;
}

.notification-bell:hover {
    color: var(--primary-rose);
    background-color: var(--bg-light);
}

.user-info-block {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-rose);
    box-shadow: 0 3px 10px rgba(212, 163, 150, 0.2);
}

.avatar-placeholder {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--dark-slate), #2c374e);
    color: var(--primary-rose);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    text-transform: uppercase;
    border: 2px solid var(--primary-rose);
    box-shadow: 0 3px 10px rgba(212, 163, 150, 0.2);
}

.user-text strong {
    color: var(--dark-slate);
    font-weight: 600;
    font-size: 0.95rem;
    display: block;
    line-height: 1.2;
}

.user-text small {
    font-size: 0.75rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-weight: 500;
    color: var(--primary-rose-dark) !important;
}
@media (max-width: 768px) {
    /* Cache la sidebar par défaut sur mobile */
    .sidebar {
        position: fixed;
        left: -250px; /* Elle est cachée à gauche */
        transition: 0.3s ease; /* Animation fluide */
        z-index: 1050;
    }

    /* Quand on ajoute la classe .active via JS */
    .sidebar.active {
        left: 0;
    }
}
</style>

<div class="topbar">
    <button class="btn shadow-sm" id="sidebarToggle">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="profile-section">
        <div class="notification-bell">
            <i class="bi bi-bell fs-5"></i>
        </div>

        <?php
        $fullname = $_SESSION['fullname'] ?? 'Admin';
        $initial  = strtoupper(substr($fullname, 0, 1));
        $avatar   = $_SESSION['avatar'] ?? '';
        ?>

        <div class="user-info-block">
            <?php if (!empty($avatar) && file_exists("../../uploads/avatars/" . $avatar)): ?>
            <img src="../../uploads/avatars/<?= htmlspecialchars($avatar) ?>" class="avatar" alt="Avatar">
            <?php else: ?>
            <div class="avatar-placeholder">
                <?= $initial ?>
            </div>
            <?php endif; ?>

            <div class="user-text d-none d-sm-block">
                <strong><?= htmlspecialchars($fullname) ?></strong>
                <small class="text-muted">Administrateur</small>
            </div>
        </div>
    </div>
</div>