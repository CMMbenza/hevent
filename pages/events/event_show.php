<?php
session_start();

// ÉVITE LE RETOUR EN ARRIÈRE APRÈS DÉCONNEXION
include '../../includes/constant.php';

require_once '../../config/database.php';
require_once '../../includes/functions.php';

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['error'] = "❌ Événement introuvable";
    header("Location: ../events/");
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM events
    WHERE generat = ? AND user_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['error'] = "❌ Événement introuvable ou non autorisé";
    header("Location: ../events/");
    exit;
}

$pack = getPack($pdo, $event['pack_code']);
$current = countInvitesByEvent($pdo, $event['generat']);
$limit = $pack['max_invites'] ?? 0;
$percent = ($limit == -1 || $limit == 0) ? 0 : ($current / $limit) * 100;
?>

<style>
/* Ajustements responsives pour fignoler le rendu */
@media (max-width: 767px) {
    .style-mobile-image {
        max-width: 100% !important;
    }

    .border-top-mobile {
        border-top: 1px solid rgba(0, 0, 0, 0.08);
        /* Petite ligne de séparation discrète sur mobile */
        width: 100%;
    }
}
</style>

<div class="container-fluid">

    <div class="card shadow-sm mb-3 mt-3 p-3">
        <div class="d-flex gap-3 align-items-start flex-column flex-md-row">

            <div class="w-100 style-mobile-image" style="max-width:220px; flex-shrink:0;">
                <?php if (!empty($event['cover_image'])): ?>
                <img src="../../uploads/covers/<?= $event['cover_image'] ?>"
                    style="width:100%; height:140px; object-fit:cover; border-radius:10px;">
                <?php else: ?>
                <div style="width:100%; height:140px; background:#eee; border-radius:10px;"
                    class="d-flex align-items-center justify-content-center text-muted">
                    Aucune image
                </div>
                <?php endif; ?>
            </div>

            <div class="flex-grow-1 w-100 d-flex flex-column justify-content-between" style="min-height: 140px;">

                <div>
                    <h4 class="mb-2 fw-bold" style="color: var(--dark-slate);">
                        <?= htmlspecialchars($event['title']) ?>
                    </h4>

                    <div class="mb-2 mt-1">
                        <span class="badge"
                            style="background-color: var(--dark-slate); color: var(--primary-rose);"><?= $event['event_type'] ?></span>
                        <span class="badge bg-secondary"><?= $pack['name'] ?? 'Aucun Pack' ?></span>
                        <span class="badge bg-light text-dark border">#<?= $event['generat'] ?></span>
                    </div>

                    <div class="text-muted small mb-2">
                        📅 <?= $event['event_date'] ?> | ⏰ <?= $event['event_time'] ?> | 📍
                        <strong><?= htmlspecialchars($event['lieu'] ?? '') ?></strong>
                        (<?= htmlspecialchars($event['location']) ?>)
                    </div>

                    <p class="mt-2 mb-3 text-muted small">
                        <?= nl2br(htmlspecialchars($event['description'] ?? '')) ?>
                    </p>
                </div>

                <div
                    class="d-flex gap-2 flex-wrap mt-auto pt-2 border-top-mobile justify-content-start justify-content-md-end">
                    <a href="../invites/form_invite.php?event_id=<?= $event['generat'] ?>"
                        class="btn btn-primary btn-sm"
                        style="background-color: var(--dark-slate); border-color: var(--dark-slate); color: var(--primary-rose); font-weight: 600;">
                        <i class="bi bi-person-plus"></i> Ajouter invité
                    </a>
                    <a href="form_event.php?action=edit&id=<?= $event['generat'] ?>" class="btn btn-dark btn-sm"
                        style="background-color: var(--dark-slate); border-color: var(--dark-slate);">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <a href="event_delete.php?id=<?= $event['generat'] ?>" class="btn btn-danger btn-sm"
                        onclick="return confirm('Supprimer cet événement ?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm p-3 mb-3">
                <h6>📊 Progression des invitations</h6>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar <?= $percent >= 100 ? 'bg-danger' : 'bg-success' ?>"
                        style="width:<?= min($percent, 100) ?>%"></div>
                </div>
                <small class="text-muted mt-1"><?= $current ?> / <?= $limit == -1 ? "∞" : $limit ?> invités
                    enregistrés</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-header fw-bold"
                    style="background-color: var(--dark-slate); color: var(--primary-rose);">
                    <i class="bi bi-sliders me-1"></i> Gestion de l'événement
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <a href="../invites/?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3">
                                <i class="bi bi-people-fill fs-2" style="color: var(--dark-slate);"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Gérer les invités</div>
                                <small class="text-muted">Nouvelle invitation</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="../tables/?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3">
                                <i class="bi bi-grid-3x3-gap-fill text-success fs-2"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Tables</div>
                                <small class="text-muted">Organisation des places</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="../rsvp/?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3">
                                <i class="bi bi-envelope-check-fill text-warning fs-2"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">RSVP</div>
                                <small class="text-muted">Réponses invités</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="https://<?= $_SERVER['HTTP_HOST'] ?>/guest/invitation.php?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3" target="black">
                                <i class="bi bi-eye-fill text-dark fs-2"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Aperçu invitation</div>
                                <small class="text-muted">Voir comme invité</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="../gallery/?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3">
                                <i class="bi bi-images text-secondary fs-2"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Galerie</div>
                                <small class="text-muted">Photos & vidéos</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="../stats/?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3">
                                <i class="bi bi-speedometer2 fs-2" style="color: var(--dark-slate);"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Statistiques</div>
                                <small class="text-muted">Analyse événement</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="form_event.php?action=edit&id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3">
                                <i class="bi bi-gear-fill text-dark fs-2"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Paramètres</div>
                                <small class="text-muted">Modifier l'événement</small>
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="../drinks/?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3">
                                <i class="bi bi-cup-hot-fill fs-2" style="color: #6c757d;"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Boissons</div>
                                <small class="text-muted">Gérer les choix</small>
                            </a>
                        </div>
                        <!-- <div class="col-md-3 col-6">
                            <a href="../../controle_acces_invitation/?event_id=<?= $event['generat'] ?>"
                                class="btn btn-light border shadow-sm w-100 py-3 text-decoration-none" target="_blank"
                                rel="noopener noreferrer">
                                <i class="bi bi-qr-code-scan text-dark fs-2"></i>
                                <div class="fw-bold mt-2" style="color: var(--dark-slate);">Scan Accès</div>
                                <small class="text-muted">Scanner QR Code</small>
                            </a>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row d-none">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header text-white"
                    style="background-color: var(--dark-slate); color: var(--primary-rose) !important;">
                    <i class="bi bi-people-fill fs-2"></i> Liste des invités
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT i.*, t.table_name FROM invites i LEFT JOIN event_tables t ON i.table_id = t.id WHERE i.generat_event = ? ORDER BY i.created_at DESC");
                    $stmt->execute([$event['generat']]);
                    $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if ($invites): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Tél</th>
                                    <th>Code</th>
                                    <th>Table</th>
                                    <th>RSVP</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invites as $inv): ?>

                                <?php
                                    $phone = preg_replace('/[^0-9]/', '', $inv['phone'] ?? '');

                                    $eventTitle = $event['title'] ?? 'Invitation événement';
                                    $guestName = $inv['fullname'] ?? 'Invité';

                                    $eventId = urlencode($inv['generat_event']);
                                    $code = urlencode($inv['invite_code']);

                                    $inviteLink = "https://hevent.notechgroup.com/guest/invitation.php?event_id=$eventId&code=$code";

                                    $message = "🎉 *$eventTitle*\n"
                                        . "Bonjour $guestName 👋\n\n"
                                        . "Vous êtes invité(e) à notre événement.\n\n"
                                        . "📌 Voir votre invitation :\n"
                                        . "$inviteLink\n\n"
                                        . "Merci 🙏";

                                    $waLink = !empty($phone)
                                        ? "https://wa.me/$phone?text=" . rawurlencode($message)
                                        : null;
                                    ?>
                                <tr>
                                    <td class="fw-semibold" style="color: var(--dark-slate);">
                                        <?= htmlspecialchars($inv['fullname']) ?></td>
                                    <td><?= htmlspecialchars($inv['phone'] ?? '-') ?></td>
                                    <td><span
                                            class="badge bg-light text-dark border"><?= htmlspecialchars($inv['invite_code']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($inv['table_name'] ?? '-') ?></td>
                                    <td><span
                                            class="badge bg-success"><?= htmlspecialchars($inv['rsvp_status'] ?? 'En attente') ?></span>
                                    </td>

                                    <td class="d-flex gap-1">

                                        <?php if ($waLink): ?>
                                        <a href="<?= $waLink ?>" target="_blank" class="btn btn-sm btn-success">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                        <?php endif; ?>

                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="copyInvite('<?= htmlspecialchars($inviteLink, ENT_QUOTES) ?>')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-success"
                                            onclick="shareInvite('<?= htmlspecialchars($inviteLink, ENT_QUOTES) ?>')">
                                            <i class="bi bi-share"></i>
                                        </button>

                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">Aucun invité inscrit pour le moment.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyInvite(link) {
    navigator.clipboard.writeText(link).then(() => {
        showToast("Copié ✔");
    }).catch(() => {
        showToast("Erreur copie");
    });
}

function shareInvite(link) {
    if (navigator.share) {
        navigator.share({
            title: "Invitation Hevent",
            url: link
        });
    } else {
        navigator.clipboard.writeText(link).then(() => {
            showToast("Lien copié");
        });
    }
}

function showToast(msg) {
    let t = document.createElement("div");
    t.innerHTML = msg;
    t.style.position = "fixed";
    t.style.bottom = "20px";
    t.style.right = "20px";
    t.style.background = "var(--dark-slate, #212529)";
    t.style.color = "var(--primary-rose, #fff)";
    t.style.padding = "10px 15px";
    t.style.borderRadius = "8px";
    t.style.zIndex = "9999";
    t.style.fontWeight = "600";
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2000);
}
// Sécurité JS supplémentaire bouton précédent
window.addEventListener('pageshow', function(event) {
    if (event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type ===
            2)) {
        window.location.reload();
    }
});
</script>
<?php include '../../includes/footer.php'; ?>