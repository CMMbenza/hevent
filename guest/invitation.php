<?php
session_start();

require_once '../config/database.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$code     = $_GET['code'] ?? '';

// Règle 1 : Si pas d'event_id valide, redirection immédiate vers index.php
if ($event_id <= 0) {
    header('Location: ../');
    exit;
}

$preview_mode = false;
$invite = null;

// Règle 2 : Mode Aperçu (Seul event_id est fourni) ou Mode Invité (event_id ET code fournis)
if (empty($code)) {
    // Mode Aperçu : On charge uniquement les détails de l'événement
    $preview_mode = true;
    
    $stmt = $pdo->prepare("SELECT id, generat, title, event_type, event_date, event_time, location, description, cover_image FROM events WHERE generat = ? LIMIT 1");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: ../');
        exit;
    }
    
    // On simule un tableau $invite générique pour que le HTML existant fonctionne sans bug
    $invite = [
        'id'          => 0,
        'event_id'    => $event['generat'],
        'title'       => $event['title'],
        'event_type'  => $event['event_type'],
        'event_date'  => $event['event_date'],
        'event_time'  => $event['event_time'],
        'location'    => $event['location'],
        'description' => $event['description'],
        'cover_image' => $event['cover_image'],
        'fullname'    => 'Aperçu Invité',
        'invite_code' => 'DEMO123',
        'table_name'  => 'Table Démo',
        'rsvp_status' => 'En attente'
    ];
} else {
    // Mode Standard avec Code d'invitation
    $stmt = $pdo->prepare("
        SELECT
            i.*,
            e.generat,
            e.title,
            e.event_type,
            e.event_date,
            e.event_time,
            e.location,
            e.description,
            e.cover_image,
            t.table_name
        FROM invites i
        INNER JOIN events e ON e.generat = i.generat_event
        LEFT JOIN event_tables t ON t.id = i.table_id
        WHERE e.generat = ?
        AND i.invite_code = ?
        LIMIT 1
    ");

    $stmt->execute([$event_id, $code]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invite) {
        // Code invalide ou introuvable -> retour à l'index
        header('Location: ../');
        exit;
    }

    /*
    ====================================
    MARQUER COMME VUE (Seulement en vrai mode invité)
    ====================================
    */
    if (isset($invite['viewed']) && $invite['viewed'] == 0) {
        $pdo->prepare("UPDATE invites SET viewed = 1 WHERE id = ?")->execute([$invite['id']]);
    }
}

/*
====================================
TRAITEMENT DES FORMULAIRES (POST)
====================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$preview_mode) {

    // Traitement du RSVP
    if (isset($_POST['save_rsvp'])) {
        $status = $_POST['rsvp_status'] ?? 'En attente';

        $stmt = $pdo->prepare("UPDATE invites SET rsvp_status = ? WHERE id = ?");
        $stmt->execute([$status, $invite['id']]);

        $_SESSION['flash'] = '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> Votre réponse a été enregistrée avec succès.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        
        // Redirection pour éviter la double soumission au rafraîchissement
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Traitement du Livre d'or
    if (isset($_POST['save_message'])) {
        $guest_message = trim($_POST['message']);

        if (!empty($guest_message)) {
            $stmt = $pdo->prepare("INSERT INTO guestbook(generat_event, invite_id, message) VALUES(?,?,?)");
            $stmt->execute([
                $invite['generat_event'],
                $invite['id'],
                $guest_message
            ]);

            $_SESSION['flash'] = '
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-heart-fill"></i> Merci pour votre tendre message !
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
            
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Récupération du message flash s'il existe
$message = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

/*
====================================
CHARGEMENT DU LIVRE D'OR
====================================
*/
$stmt = $pdo->prepare("
    SELECT g.*, i.fullname 
    FROM guestbook g 
    LEFT JOIN invites i ON g.invite_id = i.id 
    WHERE g.generat_event = ? 
    ORDER BY g.created_at DESC
");
$stmt->execute([$event_id]);
$messages_livre_or = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
====================================
CHARGEMENT DE LA GALERIE
====================================
*/
$stmt = $pdo->prepare("SELECT * FROM gallery WHERE generat_event=? ORDER BY id DESC");
$stmt->execute([$event_id]);
$gallery_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
====================================
CONSTRUCTION DE L'URL DU QR CODE (CIBLE AR)
====================================
*/
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$random_string = bin2hex(random_bytes(4)); // Génère un paramètre aléatoire unique à chaque chargement

// Génère l'adresse pointant vers guestbook.php au lieu de l'URL actuelle
$current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/controle_acces_invitation/?event_id=" . $event_id . "&code=" . urlencode($code) . "&random=" . $random_string;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($invite['title'] ?? 'Événement') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Montserrat:wght@300;400;600&display=swap"
        rel="stylesheet">

    <style>
    :root {
        --primary-rose: #d4a396;
        --primary-rose-dark: #b88679;
        --dark-slate: #1a2232;
        --bg-light: #fdfbf9;
    }

    body {
        background-color: #fcf9f6;
        font-family: 'Montserrat', sans-serif;
        color: #333;
        scroll-behavior: smooth;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--dark-slate);
        text-align: center;
        margin-bottom: 5px;
    }

    .heart-separator {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-rose);
        margin-bottom: 40px;
    }

    .heart-separator::before,
    .heart-separator::after {
        content: "";
        flex: 1;
        max-width: 50px;
        height: 1px;
        background-color: var(--primary-rose);
        margin: 0 10px;
    }

    .hero-section {
        position: relative;
        height: 100vh;
        background: linear-gradient(to bottom, rgba(26, 34, 50, 0.5), rgba(26, 34, 50, 0.2), rgba(252, 249, 246, 1)),
            url('<?= !empty($invite['cover_image']) ? "../uploads/covers/".htmlspecialchars($invite['cover_image']) : "https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200" ?>') center center/cover;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        text-align: center;
        padding: 20px;
    }

    .hero-content-wrapper {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        padding: 40px 60px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .hero-names {
        font-family: 'Great Vibes', cursive;
        font-size: 5rem;
        color: #ffffff;
        text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.2);
        margin-bottom: 10px;
    }

    @media(min-width: 768px) {
        .hero-names {
            font-size: 6.5rem;
        }
    }

    .hero-sub {
        font-size: 1.1rem;
        letter-spacing: 6px;
        text-transform: uppercase;
        margin-bottom: 30px;
        color: #fcf9f6;
        font-weight: 300;
    }

    .countdown-container {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .countdown-box {
        background: rgba(26, 34, 50, 0.75);
        color: white;
        min-width: 80px;
        padding: 12px;
        border-radius: 12px;
        border: 1px solid var(--primary-rose);
    }

    .countdown-number {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        font-weight: 700;
        display: block;
        color: var(--primary-rose);
    }

    .countdown-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.8;
    }

    .navbar-custom {
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .navbar-custom .nav-link {
        color: var(--dark-slate) !important;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
    }

    .navbar-custom .nav-link:hover {
        color: var(--primary-rose) !important;
    }

    .invitation-card {
        background-color: var(--bg-light);
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        max-width: 900px;
        margin: 0 auto;
        border: none;
    }

    .card-header-img {
        position: relative;
        height: 380px;
        background: linear-gradient(rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0.25)),
            url('<?= !empty($invite['cover_image']) ? "../uploads/covers/".htmlspecialchars($invite['cover_image']) : "https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200" ?>') center center/cover;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
    }

    .card-header-img {
        position: relative;
        height: 380px;
        overflow: hidden;
    }

    .bottom-wave {
        position: absolute;
        left: 0;
        bottom: -1px;

        width: 100%;
        height: 70px;

        background: #fdfbf9;

        border-radius: 100% 100% 0 0/100% 100% 0 0;

        z-index: 5;
    }

    .card-body-content {
        padding: 40px;
    }

    .invitation-text {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        color: #555;
    }

    .detail-icon {
        color: var(--primary-rose);
        font-size: 1.5rem;
    }

    .detail-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #666;
        margin-top: 5px;
    }

    .detail-value {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 0.95rem;
        color: #111;
    }

    .qr-section {
        border-left: 1px solid #e5dcd9;
        padding-left: 20px;
        text-align: center;
    }

    .btn-download-img {
        background-color: var(--dark-slate);
        color: white;
        border-radius: 12px;
        padding: 12px 35px;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-download-img:hover {
        background-color: var(--primary-rose-dark);
        color: white;
        transform: translateY(-2px);
    }

    .gallery-img {
        height: 280px;
        object-fit: cover;
        width: 100%;
        border-radius: 10px;
        transition: transform 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .gallery-img:hover {
        transform: scale(1.03);
    }

    .rsvp-section {
        background-color: var(--dark-slate);
        color: white;
        border-radius: 20px;
        padding: 50px;
    }

    .rsvp-section .section-title {
        color: white;
    }

    .form-control,
    .form-select {
        background-color: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 12px;
    }

    .form-control:focus,
    .form-select:focus {
        background-color: rgba(255, 255, 255, 0.15);
        border-color: var(--primary-rose);
        box-shadow: none;
        color: white;
    }

    .btn-custom {
        background-color: var(--primary-rose);
        color: white;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
    }

    .btn-custom:hover {
        background-color: var(--primary-rose-dark);
        color: white;
    }

    .guestbook-msg {
        background-color: white;
        border-left: 4px solid var(--primary-rose);
        padding: 20px;
        border-radius: 0 10px 10px 0;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.02);
        margin-bottom: 15px;
    }

    .reveal {
        opacity: 0;
        transform: translateY(40px);
        transition: all 1.2s ease;
    }

    .reveal.active {
        opacity: 1;
        transform: translateY(0);
    }

    #invitationCardToDownload {
        margin: auto;

        overflow: visible !important;
        position: relative;

        transform: none !important;
        overflow: visible !important;
        transform: none !important;
    }

    #invitationCardToDownload * {
        transform: none !important;
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#"
                style="font-family: 'Great Vibes', cursive; font-size: 2rem; color: var(--primary-rose);">
                <?= htmlspecialchars(substr($invite['title'], 0, 15)) ?>...
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav gap-3">
                    <li class="nav-item"><a class="nav-link" href="#accueil">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="#invitation">Invitation</a></li>
                    <?php if(!empty($gallery_photos)): ?><li class="nav-item"><a class="nav-link"
                            href="#galerie">Galerie</a></li><?php endif; ?>
                    <?php if(!$preview_mode): ?><li class="nav-item"><a class="nav-link" href="#rsvp">RSVP</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="#livredor">Livre d\'or</a></li>
                    <li class="nav-item"><a class="nav-link" href="#adresse">Accès</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if(!empty($message)): ?>
    <div class="container mt-3 position-fixed top-0 start-50 translate-middle-x"
        style="z-index: 1090; max-width: 500px;">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <header id="accueil" class="hero-section">
        <div class="hero-content-wrapper animate__animated animate__fadeIn">
            <h1 class="hero-names animate__animated animate__zoomIn animate__delay-1s">
                <?= htmlspecialchars($invite['title']) ?></h1>
            <p class="hero-sub"><?= htmlspecialchars($invite['event_type']) ?></p>

            <div class="countdown-container justify-content-center" id="countdownContainer">
                <div class="countdown-box">
                    <span class="countdown-number" id="days">00</span>
                    <span class="countdown-label">Jours</span>
                </div>
                <div class="countdown-box">
                    <span class="countdown-number" id="hours">00</span>
                    <span class="countdown-label">Heures</span>
                </div>
                <div class="countdown-box">
                    <span class="countdown-number" id="minutes">00</span>
                    <span class="countdown-label">Min</span>
                </div>
                <div class="countdown-box">
                    <span class="countdown-number" id="seconds">00</span>
                    <span class="countdown-label">Sec</span>
                </div>
            </div>
            <div id="countdownEndMessage" class="text-white mt-3 fw-bold fs-5 d-none">L'événement a commencé !</div>
        </div>
    </header>

    <section class="container-fluid px-0 mt-5 pt-5 reveal">
        <div class="container text-center">
            <h2 class="section-title">Informations Complémentaires</h2>
            <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

            <?php if(!empty($invite['description'])): ?>
            <div class="max-w-md mx-auto mb-5 p-3 shadow-sm rounded-3">
                <h5 class="fw-bold"><i class="bi bi-info-circle-fill text-muted"></i> À savoir sur l'événement :</h5>
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($invite['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if(!empty($gallery_photos)): ?>
    <section id="galerie" class="container py-5 my-5 reveal">
        <h2 class="section-title">Galerie Souvenir</h2>
        <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

        <div class="row g-2 g-md-3">
            <?php foreach($gallery_photos as $index => $g): ?>
            <div class="col-6 col-md-2">
                <div class="gallery-item-wrapper" style="cursor: pointer;" data-bs-toggle="modal"
                    data-bs-target="#publicGalleryModal" data-slide="<?= $index ?>">
                    <img src="../uploads/gallery/<?= htmlspecialchars($g['photo']) ?>" class="gallery-img w-100"
                        alt="Photo Galerie" style="height: 140px; object-fit: cover; border-radius: 8px;">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="modal fade" id="publicGalleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 p-0 mb-2 justify-content-end">
                    <button type="button" class="btn-close btn-close-white fs-4" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="publicGalleryCarousel" class="carousel slide" data-bs-ride="false">
                        <div class="carousel-inner">
                            <?php foreach($gallery_photos as $index => $g): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="../uploads/gallery/<?= htmlspecialchars($g['photo']) ?>"
                                    class="d-block w-100 rounded-3" style="max-height: 80vh; object-fit: contain;">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#publicGalleryCarousel"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#publicGalleryCarousel"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!$preview_mode): ?>
    <section id="rsvp" class="container my-5 py-5 reveal">
        <div class="rsvp-section">
            <h2 class="section-title">Confirmer votre présence (RSVP)</h2>
            <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

            <div class="text-center mb-4">
                <?php
                    $badge='secondary'; $text='En attente de réponse';
                    if($invite['rsvp_status']=='Present'){ $badge='success'; $text='Présence confirmée ✔️'; }
                    if($invite['rsvp_status']=='Absent'){ $badge='danger'; $text='Absence confirmée ❌'; }
                ?>
                <span class="badge bg-<?= $badge ?> fs-5 px-4 py-2 rounded-pill"> Statut actuel : <?= $text ?></span>
            </div>

            <form method="POST" class="max-w-md mx-auto" style="max-width: 500px; margin: 0 auto;">
                <div class="bg-white text-dark p-4 rounded-3 shadow-sm mb-3">
                    <div class="text-center mb-3">
                        <strong class="fs-5"><?= htmlspecialchars($invite['fullname']) ?></strong>
                    </div>
                    <div class="d-grid gap-2">
                        <input type="radio" class="btn-check" name="rsvp_status" id="rsvpP" value="Present"
                            <?= $invite['rsvp_status']=='Present'?'checked':'' ?>>
                        <label class="btn btn-outline-success w-100" for="rsvpP">😊 Oui, je serai présent à
                            l’événement</label>

                        <input type="radio" class="btn-check" name="rsvp_status" id="rsvpA" value="Absent"
                            <?= $invite['rsvp_status']=='Absent'?'checked':'' ?>>
                        <label class="btn btn-outline-danger w-100" for="rsvpA">😔 Malheureusement, je serai
                            absent</label>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" name="save_rsvp" class="btn btn-custom w-100">
                        <i class="bi bi-check-circle-fill"></i> Enregistrer ma réponse
                    </button>
                </div>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <section id="invitation" class="container py-5 my-5 reveal">
        <h2 class="section-title">Votre Carte d'Invitation</h2>
        <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

        <div class="card invitation-card" id="invitationCardToDownload">
            <div class="card-header-img">

                <h1 class="hero-names" style="font-size:3.5rem;text-align:center;">
                    <?= htmlspecialchars($invite['fullname']) ?>
                </h1>

                <p class="wedding-text">Invitation Officielle</p>

                <div class="bottom-wave"></div>

            </div>
            <div class="card-body-content">
                <div class="row align-items-center">
                    <div class="col-lg-8 pe-lg-4 text-center">
                        <div class="mb-3 text-muted"><i class="bi bi-infinity fs-1"
                                style="color: var(--primary-rose);"></i></div>
                        <p class="invitation-text mb-4 text-muted">
                            Vous êtes chaleureusement convié à l'événement décrit ci-dessous. Votre présence à nos côtés
                            est précieuse.
                        </p>

                        <div class="row g-3 justify-content-center">
                            <div class="col-6 col-sm-3 text-center">
                                <i class="bi bi-calendar3 detail-icon"></i>
                                <div class="detail-label">Date</div>
                                <div class="detail-value">
                                    <?= !empty($invite['event_date']) ? date('d/m/Y', strtotime($invite['event_date'])) : '' ?>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3 text-center">
                                <i class="bi bi-clock detail-icon"></i>
                                <div class="detail-label">Heure</div>
                                <div class="detail-value">
                                    <?= !empty($invite['event_time']) ? substr($invite['event_time'], 0, 5) : '' ?>
                                </div>
                            </div>
                            <?php if(!empty($invite['table_name'])): ?>
                            <div class="col-6 col-sm-3 text-center">
                                <i class="bi bi-grid-3x3-gap detail-icon"></i>
                                <div class="detail-label">Votre Table</div>
                                <div class="detail-value"><?= htmlspecialchars($invite['table_name']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4 qr-section mt-4 mt-lg-0">
                        <div class="qr-title mb-2">VOTRE ACCÈS UNIQUE</div>
                        <div class="detail-value text-uppercase"><?= htmlspecialchars($invite['invite_code']) ?></div>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=<?= urlencode($current_url) ?>"
                            alt="QR Code Unique" class="img-fluid" style="max-width: 120px;">
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Présentez ce QR code à l'entrée</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button class="btn btn-download-img d-inline-flex align-items-center gap-2" id="downloadImageBtn">
                <i class="bi bi-file-earmark-image"></i> Télécharger ma carte en Image
            </button>
        </div>
    </section>

    <section id="livredor" class="container py-5 my-5 reveal">
        <h2 class="section-title">Livre d'or</h2>
        <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

        <div class="row g-5">
            <div class="col-md-5">
                <div class="p-4 rounded shadow-sm bg-white border">
                    <h5 class="mb-3 font-serif" style="font-family: 'Playfair Display', serif;">Laissez un mot aux
                        organisateurs</h5>

                    <?php if($preview_mode): ?>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle-fill"></i> Le formulaire est masqué en mode aperçu.
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="text" class="form-control bg-light text-dark fw-bold"
                                value="<?= htmlspecialchars($invite['fullname']) ?>" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <textarea name="message" class="form-control bg-light text-dark" rows="4"
                                placeholder="Écrivez vos vœux ou félicitations ici..." required></textarea>
                        </div>
                        <button type="submit" name="save_message" class="btn btn-custom w-100">
                            <i class="bi bi-send-fill"></i> Envoyer mon message
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-7">
                <h4 class="mb-3 font-serif" style="font-family: 'Playfair Display', serif;"><i
                        class="bi bi-chat-left-heart text-rose"></i> Messages des invités</h4>
                <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                    <?php if(empty($messages_livre_or)): ?>
                    <p class="text-muted">Aucun message n'a encore été laissé. Soyez le premier !</p>
                    <?php else: ?>
                    <?php foreach($messages_livre_or as $m): ?>
                    <div class="guestbook-msg">
                        <strong
                            class="text-secondary d-block mb-1"><?= htmlspecialchars($m['fullname'] ?? 'Invité Anonyme') ?>
                            :</strong>
                        <p class="mb-2 text-dark"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
                        <small class="text-muted"><i class="bi bi-clock"></i> Le
                            <?= date('d/m/Y à H:i', strtotime($m['created_at'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="adresse" class="container-fluid px-0 mt-5 pt-5 reveal">
        <div class="container text-center">
            <h2 class="section-title">Localisation</h2>
            <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

            <p class="fs-5 mb-4"><i class="bi bi-geo-alt-fill text-danger"></i>
                <?= htmlspecialchars($invite['location']) ?></p>
            <div class="mb-4">
                <a target="_blank" href="https://maps.google.com/?q=<?= urlencode($invite['location']) ?>"
                    class="btn btn-outline-dark px-4 py-2">
                    <i class="bi bi-map-fill"></i> Ouvrir sur l'application Google Maps
                </a>
            </div>
        </div>

        <div class="w-100" style="height: 400px;">
            <iframe
                src="https://maps.google.com/maps?q=<?= urlencode($invite['location']) ?>&t=&z=13&ie=UTF8&iwloc=&output=embed"
                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </section>

    <footer class="bg-dark text-white text-center py-4">
        <p class="small text-white-50 mb-0">© 2026 - Invitation connectée propulsée par votre plateforme.</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    const targetWeddingDate = new Date(
        "<?= htmlspecialchars($invite['event_date']) ?>T<?= htmlspecialchars($invite['event_time']) ?>").getTime();

    const countdownInterval = setInterval(function() {
        const now = new Date().getTime();
        const difference = targetWeddingDate - now;

        if (difference <= 0) {
            clearInterval(countdownInterval);
            document.getElementById('countdownContainer').classList.add('d-none');
            document.getElementById('countdownEndMessage').classList.remove('d-none');
            return;
        }

        const d = Math.floor(difference / (1000 * 60 * 60 * 24));
        const h = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((difference % (1000 * 60)) / 1000);

        document.getElementById("days").innerText = d < 10 ? "0" + d : d;
        document.getElementById("hours").innerText = h < 10 ? "0" + h : h;
        document.getElementById("minutes").innerText = m < 10 ? "0" + m : m;
        document.getElementById("seconds").innerText = s < 10 ? "0" + s : s;
    }, 1000);

    // Initialisation du comportement de la modal de la galerie public
    const publicGalleryModal = document.getElementById('publicGalleryModal');
    if (publicGalleryModal) {
        publicGalleryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const slideIndex = parseInt(button.getAttribute('data-slide'), 10);
            const carouselElement = document.getElementById('publicGalleryCarousel');
            const carousel = bootstrap.Carousel.getOrCreateInstance(carouselElement);
            carousel.to(slideIndex);
        });
    }

    // Effet d'apparition au scroll (Reveal)
    window.addEventListener('scroll', revealElements);

    function revealElements() {
        const reveals = document.querySelectorAll('.reveal');
        for (let i = 0; i < reveals.length; i++) {
            const windowHeight = window.innerHeight;
            const elementTop = reveals[i].getBoundingClientRect().top;
            const elementVisible = 150;
            if (elementTop < windowHeight - elementVisible) {
                reveals[i].classList.add('active');
            }
        }
    }

    document.getElementById("downloadImageBtn").addEventListener("click", async function() {

        const card = document.getElementById("invitationCardToDownload");

        if (!card) return;

        // Sauvegarde styles temporaires
        const originalOverflow = card.style.overflow;
        const originalTransform = card.style.transform;

        // FIX CUTTING ISSUES
        card.style.overflow = "visible";
        card.style.transform = "none";

        // attend rendu DOM
        await new Promise(r => setTimeout(r, 300));

        const rect = card.getBoundingClientRect();

        html2canvas(card, {
            scale: 3,
            useCORS: true,
            backgroundColor: "#fdfbf9",

            width: rect.width,
            height: rect.height,

            x: 0,
            y: 0,

            scrollX: -window.scrollX,
            scrollY: -window.scrollY,

            windowWidth: document.documentElement.clientWidth,
            windowHeight: document.documentElement.clientHeight
        }).then(canvas => {

            const img = canvas.toDataURL("image/png");

            const link = document.createElement("a");
            link.href = img;
            link.download = "invitation-hevent.png";

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // restore styles
            card.style.overflow = originalOverflow;
            card.style.transform = originalTransform;

        }).catch(err => {
            console.error(err);
            alert("Erreur capture carte");
        });

    });

    revealElements(); // Exécution initiale
    </script>
</body>

</html>