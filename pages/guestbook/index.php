<?php
session_start();

require_once '../../config/database.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';

$event_id = $_GET['event_id'] ?? 0;

// Récupération de l'événement
$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Événement introuvable");
}

// Récupération des messages du livre d'or
$stmt = $pdo->prepare("
    SELECT guestbook.*, inv.fullname
    FROM guestbook
    JOIN invites AS inv
        ON inv.id = guestbook.invite_id
    WHERE guestbook.generat_event = ?
    ORDER BY guestbook.created_at DESC
");
$stmt->execute([$event_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Design moderne et épuré */
    .card { border-radius: 15px !important; }
    
    .message-card {
        background: #ffffff;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        animation: fadeIn 0.6s ease-out forwards;
    }
    
    .message-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .avatar-circle {
        background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
        color: #d4a396;
        font-size: 1.2rem;
    }

    .quote-icon {
        font-size: 1.5rem;
        color: #d4a396;
        opacity: 0.3;
        margin-bottom: -10px;
    }
</style>

<div class="container-fluid py-4">
    <!-- En-tête élégant -->
    <div class="card shadow-sm mb-4 border-0 bg-white">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-heart-fill me-2" style="color: #d4a396;"></i>Livre d'or</h4>
                <p class="text-muted mb-0 small mt-1">
                    <i class="bi bi-calendar-event me-1"></i> Événement : <strong><?= htmlspecialchars($event['title']) ?></strong>
                </p>
            </div>
            <div class="text-center">
                <h3 class="mb-0 fw-bold text-primary"><?= count($messages) ?></h3>
                <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Messages</small>
            </div>
        </div>
    </div>

    <!-- Grille des messages -->
    <div class="row">
        <?php if (empty($messages)): ?>
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-circle d-inline-block shadow-sm mb-3">
                    <i class="bi bi-chat-dots display-3 text-muted"></i>
                </div>
                <h5 class="text-muted">Pas encore de souvenirs...</h5>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card message-card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <i class="bi bi-quote quote-icon d-block"></i>
                        <p class="card-text text-dark py-2" style="font-size: 1.05rem; line-height: 1.6; font-family: 'Georgia', serif;">
                            <?= nl2br(htmlspecialchars($m['message'])) ?>
                        </p>
                        <div class="d-flex align-items-center mt-3 pt-3 border-top">
                            <div class="rounded-circle avatar-circle d-flex align-items-center justify-content-center me-3 shadow-sm" 
                                 style="width: 40px; height: 40px;">
                                <i class="bi bi-person-heart"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($m['fullname']) ?></h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock-history me-1"></i><?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>