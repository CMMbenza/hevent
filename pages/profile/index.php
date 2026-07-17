<?php
session_start();

// 1. SÉCURITÉ ET REDIRECTION (Toujours en premier !)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

// 3. INCLUSIONS TECHNIQUES & CONFIGURATION
include '../../includes/constant.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// 4. LOGIQUE APPLICATIVE (TRAITEMENT DE LA BDD)
$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){ 
    die("Utilisateur introuvable"); 
}

// STATS CORRIGÉES
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events");
$stmt->execute();
$total_events = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM invites i JOIN events e ON e.id = i.generat_event");
$stmt->execute();
$total_invites = $stmt->fetchColumn();


// 5. INCLUSIONS VISUELLES (Seulement maintenant qu'on est sûr que l'user est connecté)
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<div class="container-fluid">

    <div class="card shadow-sm mb-4 mt-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <?php if(!empty($user['avatar'])): ?>
                    <img src="../../uploads/photos/<?= $user['avatar'] ?>" class="rounded-circle shadow"
                        style="width:150px;height:150px;object-fit:cover;">
                    <?php else: ?>
                    <div class="bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center shadow"
                        style="width:150px;height:150px;"><i class="bi bi-person-fill fs-1 text-secondary"></i></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-9">
                    <h3 class="mb-1" style="color: var(--dark-slate);"><?= htmlspecialchars($user['fullname']) ?></h3>
                    <div class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></div>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="card" style="border-color: var(--dark-slate);">
                                <div class="card-body text-center">
                                    <i class="bi bi-calendar-event fs-3" style="color: var(--dark-slate);"></i>
                                    <h4><?= $total_events ?></h4>
                                    <small class="text-muted fw-bold">Événements</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="bi bi-people-fill text-success fs-3"></i>
                                    <h4><?= $total_invites ?></h4>
                                    <small class="text-muted fw-bold">Invités</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="card" style="border-color: var(--dark-slate);">
                                <div class="card-body text-center">
                                    <i class="bi bi-award-fill fs-3" style="color: var(--dark-slate);"></i>
                                    <h4 style="color: var(--dark-slate);"><?= strtoupper($user['role'] ?? 'USER') ?></h4>
                                    <small class="text-muted fw-bold">Compte</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header fw-bold" style="background-color: var(--dark-slate); color: var(--primary-rose);">
            <i class="bi bi-person-gear me-1"></i> Modifier mon profil
        </div>
        <div class="card-body">
            <form action="profile_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold small">Nom complet</label><input
                            type="text" name="name" class="form-control"
                            value="<?= htmlspecialchars($user['fullname']) ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold small">Téléphone</label><input
                            type="text" name="phone" class="form-control"
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold small">Email</label><input type="email"
                            name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-bold small">Photo de profil</label><input
                            type="file" name="avatar" class="form-control"></div>
                </div>
                <button class="btn"
                    style="background-color: var(--dark-slate); color: var(--primary-rose); font-weight: 600;"><i
                        class="bi bi-save me-1"></i> Enregistrer</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header fw-bold text-white" style="background-color: #212529;">
            <i class="bi bi-shield-lock me-1"></i> Changer le mot de passe
        </div>
        <div class="card-body">
            <form action="profile_action.php" method="POST">
                <input type="hidden" name="change_password" value="1">
                <div class="row g-3">
                    <div class="col-md-4"><input type="password" name="current_password" class="form-control"
                            placeholder="Mot de passe actuel" required></div>
                    <div class="col-md-4"><input type="password" name="new_password" class="form-control"
                            placeholder="Nouveau mot de passe" required></div>
                    <div class="col-md-4"><input type="password" name="confirm_password" class="form-control"
                            placeholder="Confirmation" required></div>
                </div>
                <button class="btn btn-dark mt-3"><i class="bi bi-key me-1"></i> Modifier le mot de passe</button>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('pageshow', function(event) {
    if (event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type === 2)) {
        window.location.reload();
    }
});
</script>
<?php include '../../includes/footer.php'; ?>