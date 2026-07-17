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

    if (isset($invite['viewed']) && $invite['viewed'] == 0) {
        $pdo->prepare("UPDATE invites SET viewed = 1 WHERE id = ?")->execute([$invite['id']]);
    }
}

// Récupération des boissons disponibles
$stmt = $pdo->prepare("SELECT * FROM event_drinks WHERE generat_event = ?");
$stmt->execute([$event_id]);
$drinks_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération du choix actuel
// $stmt = $pdo->prepare("SELECT drink_id FROM guest_drink_choices WHERE invite_id = ? LIMIT 1");
// $stmt->execute([$invite['id']]);
// $current_choice = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT drink_id, custom_drink_name FROM guest_drink_choices WHERE invite_id = ?");
$stmt->execute([$invite['id']]);
$choices_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_drink_ids = [];
$current_custom_drink = '';

foreach ($choices_data as $row) {
    if (!empty($row['drink_id'])) {
        $current_drink_ids[] = $row['drink_id']; // Stocke les IDs des boissons cochées
    }
    if (!empty($row['custom_drink_name'])) {
        $current_custom_drink = $row['custom_drink_name']; // Stocke le texte de la boisson personnalisée
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$preview_mode) {

    // Traitement du RSVP
if (isset($_POST['save_rsvp'])) {
    $status = $_POST['rsvp_status'] ?? 'En attente';
    $selected_drink_ids = $_POST['drink_ids'] ?? []; // Tableau des IDs choisis
    $custom_drink = trim($_POST['custom_drink'] ?? '');

    $stmt = $pdo->prepare("UPDATE invites SET rsvp_status = ? WHERE id = ?");
    $stmt->execute([$status, $invite['id']]);

    // Nettoyage des anciens choix
    $pdo->prepare("DELETE FROM guest_drink_choices WHERE invite_id = ?")->execute([$invite['id']]);

    // Insertion des choix multiples
    if (!empty($selected_drink_ids)) {
        $stmt = $pdo->prepare("INSERT INTO guest_drink_choices (invite_id, drink_id) VALUES (?, ?)");
        foreach ($selected_drink_ids as $d_id) {
            $stmt->execute([$invite['id'], (int)$d_id]);
        }
    }

    // Insertion si suggestion personnalisée
    if (!empty($custom_drink)) {
        $pdo->prepare("INSERT INTO guest_drink_choices (invite_id, custom_drink_name) VALUES (?, ?)")
            ->execute([$invite['id'], $custom_drink]);
    }

    $_SESSION['flash'] = '<div class="alert alert-success">Choix enregistrés !</div>';
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

    // Traitement du Livre d'or
    if (isset($_POST['save_message'])) {
        $guest_message = trim($_POST['message']);

        if (!empty($guest_message)) {
            $stmt = $pdo->prepare("INSERT INTO guestbook(generat_event, invite_id, message) VALUES(?,?,?)");
            $stmt->execute([$invite['generat_event'], $invite['id'], $guest_message]);

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

$message = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Chargement du Livre d'or
$stmt = $pdo->prepare("SELECT g.*, i.fullname FROM guestbook g LEFT JOIN invites i ON g.invite_id = i.id WHERE g.generat_event = ? ORDER BY g.created_at DESC");
$stmt->execute([$event_id]);
$messages_livre_or = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chargement de la galerie
$stmt = $pdo->prepare("SELECT * FROM gallery WHERE generat_event=? ORDER BY id DESC");
$stmt->execute([$event_id]);
$gallery_photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$random_string = bin2hex(random_bytes(4));
$current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/controle_acces_invitation/?event_id=" . $event_id . "&code=" . urlencode($code) . "&random=" . $random_string;

// --- CONFIGURATION DU LIEN GOOGLE CALENDAR ---
$google_cal_url = "";
if (!empty($invite['event_date'])) {
    $evt_date = $invite['event_date'];
    $evt_time = !empty($invite['event_time']) ? $invite['event_time'] : "00:00:00";
    
    // Création des dates de début et de fin (on rajoute 4 heures par défaut pour la fin)
    $start_datetime = new DateTime($evt_date . ' ' . $evt_time);
    $end_datetime = clone $start_datetime;
    $end_datetime->modify('+4 hours');
    
    // Format requis par Google : AAAAMMJJTHHMMSS
    $google_start = $start_datetime->format('Ymd\THms');
    $google_end = $end_datetime->format('Ymd\THms');
    
    $google_cal_url = "https://calendar.google.com/calendar/render?" . http_build_query([
        'action'   => 'TEMPLATE',
        'text'     => $invite['title'] . ' - ' . $invite['event_type'],
        'dates'    => $google_start . '/' . $google_end,
        'details'  => "Bonjour " . $invite['fullname'] . ",\n\nVous êtes cordialement invité à cet événement.\nVotre Code d'accès unique : " . $invite['invite_code'] . "\nDescription : " . $invite['description'],
        'location' => $invite['location'],
        'sf'       => 'true',
        'output'   => 'xml'
    ]);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hevent - <?= htmlspecialchars($invite['title'] ?? 'Événement') ?></title>
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
        padding-bottom: 60px;
    }

    .section-title {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--dark-slate);
        text-align: center;
        margin-bottom: 5px;
        font-size: 1.8rem;
    }

    @media (min-width: 768px) {
        .section-title {
            font-size: 2.5rem;
        }
    }

    .heart-separator {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-rose);
        margin-bottom: 30px;
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
        height: 90vh;
        background: linear-gradient(to bottom, rgba(26, 34, 50, 0.5), rgba(26, 34, 50, 0.2), rgba(252, 249, 246, 1)),
            url('<?= !empty($invite['cover_image']) ? "../uploads/covers/".htmlspecialchars($invite['cover_image']) : "https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200" ?>') center center/cover;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        text-align: center;
        padding: 15px;
    }

    .hero-content-wrapper {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        padding: 25px 20px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 950px;
    }

    @media (min-width: 768px) {
        .hero-content-wrapper {
            padding: 40px 60px;
        }
    }

    .hero-names {
        font-family: 'Great Vibes', cursive;
        font-size: 3rem;
        color: #ffffff;
        text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.2);
        margin-bottom: 10px;
        word-break: break-word;
    }

    @media(min-width: 768px) {
        .hero-names {
            font-size: 5.5rem;
        }
    }

    .hero-sub {
        font-size: 0.9rem;
        letter-spacing: 3px;
        text-transform: uppercase;
        margin-bottom: 20px;
        color: #fcf9f6;
        font-weight: 300;
    }

    @media (min-width: 768px) {
        .hero-sub {
            font-size: 1.1rem;
            letter-spacing: 6px;
        }
    }

    .countdown-container {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
    }

    .countdown-box {
        background: rgba(26, 34, 50, 0.75);
        color: white;
        min-width: 65px;
        padding: 8px;
        border-radius: 10px;
        border: 1px solid var(--primary-rose);
    }

    @media (min-width: 768px) {
        .countdown-box {
            min-width: 85px;
            padding: 12px;
        }
    }

    .countdown-number {
        font-family: 'Playfair Display', serif;
        font-size: 1.3rem;
        font-weight: 700;
        display: block;
        color: var(--primary-rose);
    }

    @media (min-width: 768px) {
        .countdown-number {
            font-size: 1.8rem;
        }
    }

    .countdown-label {
        font-size: 0.55rem;
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

    .invitation-card {
        background-color: var(--bg-light);
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        max-width: 850px;
        margin: 0 auto;
        border: none;
    }

    .card-header-img {
        position: relative;
        height: 250px;
        background: linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.3)),
            url('<?= !empty($invite['cover_image']) ? "../uploads/covers/".htmlspecialchars($invite['cover_image']) : "https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200" ?>') center center/cover;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        padding: 15px;
    }

    @media (min-width: 768px) {
        .card-header-img {
            height: 350px;
        }
    }

    .bottom-wave {
        position: absolute;
        left: 0;
        bottom: -1px;
        width: 100%;
        height: 40px;
        background: #fdfbf9;
        border-radius: 100% 100% 0 0/100% 100% 0 0;
        z-index: 5;
    }

    .card-body-content {
        padding: 20px 15px;
    }

    @media (min-width: 768px) {
        .card-body-content {
            padding: 40px;
        }
    }

    .invitation-text {
        font-family: 'Playfair Display', serif;
        font-size: 1rem;
        color: #555;
    }

    .detail-icon {
        color: var(--primary-rose);
        font-size: 1.3rem;
    }

    .detail-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #666;
    }

    .detail-value {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 0.9rem;
        color: #111;
    }

    .qr-section {
        border-top: 1px solid #e5dcd9;
        padding-top: 20px;
        text-align: center;
    }

    @media (min-width: 992px) {
        .qr-section {
            border-top: none;
            border-left: 1px solid #e5dcd9;
            padding-top: 0;
            padding-left: 20px;
        }
    }

    .btn-download-img {
        background-color: var(--dark-slate);
        border: 1px solid var(--dark-slate);
        color: #ffffff;
        border-radius: 12px;
        padding: 12px 25px;
        font-size: 0.9rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        width: 100%;
        max-width: 320px;
    }

    .btn-download-img:hover {
        background-color: var(--primary-rose);
        color: white;
    }

    .btn-calendar {
        background-color: transparent;
        border: 1px solid var(--primary-rose);
        color: var(--primary-rose-dark);
        border-radius: 30px;
        padding: 6px 16px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-calendar:hover {
        background-color: var(--primary-rose);
        color: white;
    }

    .gallery-img {
        height: 120px;
        object-fit: cover;
        width: 100%;
        border-radius: 8px;
    }

    @media (min-width: 768px) {
        .gallery-img {
            height: 180px;
        }
    }

    .rsvp-section {
        background-color: var(--dark-slate);
        color: white;
        border-radius: 20px;
        padding: 25px 15px;
    }

    @media (min-width: 768px) {
        .rsvp-section {
            padding: 50px;
        }
    }

    .form-control,
    .form-select {
        color: #333 !important;
        background-color: #ffffff !important;
        padding: 12px;
        font-size: 0.95rem;
    }

    .btn-custom {
        background-color: var(--primary-rose);
        color: white;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        width: 100%;
    }

    .btn-custom:hover {
        background-color: var(--primary-rose-dark);
        color: white;
    }

    .guestbook-msg {
        background-color: white;
        border-left: 4px solid var(--primary-rose);
        padding: 15px;
        border-radius: 0 10px 10px 0;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.02);
        margin-bottom: 15px;
    }

    .reveal {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.8s ease;
    }

    .reveal.active {
        opacity: 1;
        transform: translateY(0);
    }

    /* Bouton Musique Flottant */
    .music-control {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 2000;
        background: var(--primary-rose);
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    /* AJOUT : Bouton Contact WhatsApp Flottant (Vert) */
    .contact-control {
        position: fixed;
        bottom: 75px;
        /* Placé juste au-dessus du bouton musique */
        right: 20px;
        z-index: 2000;
        background: #25D366;
        /* Vert officiel WhatsApp */
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: transform 0.2s ease;
    }

    .contact-control:hover {
        color: white;
        transform: scale(1.05);
    }
    </style>
</head>

<body>

    <!-- Lecteur Audio avec une musique de fond acoustique très douce et calme -->
    <audio id="bgMusic" loop src="../assets/audio/the_mountain-calm-romantic-444038.mp3"></audio>
    <button class="music-control" id="musicBtn" onclick="toggleMusic()"><i class="bi bi-music-note-beamed"></i></button>

    <!-- AJOUT : Bouton flottant de contact WhatsApp -->
    <a href="https://wa.me/243980287578" target="_blank" class="contact-control" title="Nous contacter sur WhatsApp">
        <i class="bi bi-whatsapp fs-5"></i>
    </a>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#"
                style="font-family: 'Great Vibes', cursive; font-size: 1.8rem; color: var(--primary-rose);">
                <?= htmlspecialchars(substr($invite['title'], 0, 15)) ?>...
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav gap-2 pt-2 pt-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#accueil">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="#invitation">Invitation</a></li>
                    <?php if(!empty($gallery_photos)): ?><li class="nav-item"><a class="nav-link"
                            href="#galerie">Galerie</a></li><?php endif; ?>
                    <?php if(!$preview_mode): ?><li class="nav-item"><a class="nav-link" href="#rsvp">RSVP</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="#livredor">Livre d'or</a></li>
                    <li class="nav-item"><a class="nav-link" href="#adresse">Accès</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if(!empty($message)): ?>
    <div class="container mt-3 position-fixed top-0 start-50 translate-middle-x"
        style="z-index: 1090; width: 90%; max-width: 500px;">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <header id="accueil" class="hero-section">
        <div class="hero-content-wrapper animate__animated animate__fadeIn">
            <h1 class="hero-names animate__animated animate__zoomIn animate__delay-1s">
                <?= htmlspecialchars($invite['title']) ?></h1>
            <p class="hero-sub"><?= htmlspecialchars($invite['event_type']) ?></p>

            <div class="countdown-container" id="countdownContainer">
                <div class="countdown-box"><span class="countdown-number" id="days">00</span><span
                        class="countdown-label">Jours</span></div>
                <div class="countdown-box"><span class="countdown-number" id="hours">00</span><span
                        class="countdown-label">Heures</span></div>
                <div class="countdown-box"><span class="countdown-number" id="minutes">00</span><span
                        class="countdown-label">Min</span></div>
                <div class="countdown-box"><span class="countdown-number" id="seconds">00</span><span
                        class="countdown-label">Sec</span></div>
            </div>
            <div id="countdownEndMessage" class="text-white mt-3 fw-bold fs-5 d-none">L'événement a commencé !</div>
        </div>
    </header>

    <section class="container my-5 reveal">
        <div class="text-center mx-auto" style="max-width: 100%;">
            <h2 class="section-title">Informations Complémentaires</h2>
            <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

            <?php if(!empty($invite['description'])): ?>
            <div class="p-3 shadow-sm rounded-3 bg-white border">
                <h6 class="fw-bold text-muted mb-2"><i class="bi bi-info-circle-fill"></i> À savoir sur l'événement :
                </h6>
                <p class="mb-0 text-muted small"><?= nl2br(htmlspecialchars($invite['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if(!empty($gallery_photos)): ?>
    <section id="galerie" class="container py-4 reveal">
        <h2 class="section-title">Galerie Souvenir</h2>
        <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

        <div class="row g-2 g-md-3">
            <?php foreach($gallery_photos as $index => $g): ?>
            <div class="col-4 col-md-3 col-lg-2">
                <div class="gallery-item-wrapper" style="cursor: pointer;" data-bs-toggle="modal"
                    data-bs-target="#publicGalleryModal" data-slide="<?= $index ?>">
                    <img src="../uploads/gallery/<?= htmlspecialchars($g['photo']) ?>" class="gallery-img"
                        alt="Photo Galerie">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Modal Galerie -->
    <div class="modal fade" id="publicGalleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 p-0 mb-2 justify-content-end">
                    <button type="button" class="btn-close btn-close-white fs-4" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="publicGalleryCarousel" class="carousel slide">
                        <div class="carousel-inner">
                            <?php foreach($gallery_photos as $index => $g): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="../uploads/gallery/<?= htmlspecialchars($g['photo']) ?>"
                                    class="d-block w-100 rounded-3" style="max-height: 75vh; object-fit: contain;">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#publicGalleryCarousel"
                            data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#publicGalleryCarousel"
                            data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!$preview_mode): ?>
    <section id="rsvp" class="container my-5 reveal">
        <div class="rsvp-section mx-auto" style="max-width: 100%;">
            <h2 class="section-title text-white">Confirmation (RSVP)</h2>
            <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

            <div class="text-center mb-4">
                <?php
                    $badge='secondary'; $text='En attente';
                    if($invite['rsvp_status']=='Present'){ $badge='success'; $text='Présent ✔️'; }
                    if($invite['rsvp_status']=='Absent'){ $badge='danger'; $text='Absent ❌'; }
                ?>
                <span class="badge bg-<?= $badge ?> fs-6 px-3 py-2 rounded-pill">Statut actuel : <?= $text ?></span>
            </div>

            <form method="POST">
                <div class="bg-white text-dark p-3 p-md-4 rounded-3 shadow-sm mb-3">
                    <div class="text-center mb-3">
                        <strong class="fs-5"><?= htmlspecialchars($invite['fullname']) ?></strong>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <input type="radio" class="btn-check" name="rsvp_status" id="rsvpP" value="Present"
                            <?= $invite['rsvp_status']=='Present'?'checked':'' ?>>
                        <label class="btn btn-outline-success w-100" for="rsvpP">😊 Je serai présent</label>

                        <input type="radio" class="btn-check" name="rsvp_status" id="rsvpA" value="Absent"
                            <?= $invite['rsvp_status']=='Absent'?'checked':'' ?>>
                        <label class="btn btn-outline-danger w-100" for="rsvpA">😔 Malheureusement, absent</label>
                    </div>

                    <?php if (!empty($drinks_disponibles)): ?>
                    <div class="mt-3">
                        <label class="form-label fw-bold small text-secondary">Choisissez vos boissons (Si vous êtes
                            invité(e) en couple, vous pouvez sélectionner plusieurs boissons afin que chacun choisisse
                            selon ses préférences) :</label>

                        <div class="row">
                            <?php foreach ($drinks_disponibles as $d): ?>
                            <div class="col-2 me-2">
                                <input class="form-check-input mb-2" type="checkbox" name="drink_ids[]"
                                    value="<?= $d['id'] ?>" id="drink_<?= $d['id'] ?>"
                                    <?= in_array($d['id'], $current_drink_ids) ? 'checked' : '' ?>>

                                <label class="mb-0" for="drink_<?= $d['id'] ?>">
                                    <?= htmlspecialchars($d['drink_name']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>


                        <div class="mt-3">
                            <label class="form-label small text-muted"><b>Votre boisson préférée ne figure pas dans la
                                    liste ?</b> Écrivez-la ici. Nous ferons notre possible pour répondre à votre
                                demande. En cas d'indisponibilité, <b>une autre boisson vous sera
                                    proposée.</b>😊</label>
                            <input type="text" name="custom_drink" class="form-control"
                                placeholder="Ex: Jus de fruits pressés..."
                                value="<?= htmlspecialchars($current_custom_drink) ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="save_rsvp" class="btn btn-custom"><i class="bi bi-check-circle-fill"></i>
                    Enregistrer ma réponse</button>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <section id="invitation" class="container py-4 reveal">
        <h2 class="section-title">Votre Carte d'Invitation</h2>
        <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

        <div class="card invitation-card" id="invitationCardToDownload">
            <div class="card-header-img">
                <h1 class="hero-names text-center" style="font-size:2.2rem;">
                    <?= htmlspecialchars($invite['fullname']) ?></h1>
                <p class="small text-uppercase tracking-wider">Invitation Officielle</p>
                <div class="bottom-wave"></div>
            </div>
            <div class="card-body-content">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8 text-center">
                        <div class="mb-2"><i class="bi bi-infinity fs-2" style="color: var(--primary-rose);"></i></div>
                        <p class="invitation-text mb-4 text-muted px-2">Vous êtes chaleureusement convié. Votre présence
                            à nos côtés est précieuse.</p>

                        <div class="row g-2 justify-content-center mb-4">
                            <div class="col-4 text-center">
                                <i class="bi bi-calendar3 detail-icon"></i>
                                <div class="detail-label">Date</div>
                                <div class="detail-value text-nowrap">
                                    <?= !empty($invite['event_date']) ? date('d/m/Y', strtotime($invite['event_date'])) : '' ?>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <i class="bi bi-clock detail-icon"></i>
                                <div class="detail-label">Heure</div>
                                <div class="detail-value">
                                    <?= !empty($invite['event_time']) ? substr($invite['event_time'], 0, 5) : '' ?>
                                </div>
                            </div>
                            <?php if(!empty($invite['table_name'])): ?>
                            <div class="col-4 text-center">
                                <i class="bi bi-grid-3x3-gap detail-icon"></i>
                                <div class="detail-label">Table</div>
                                <div class="detail-value text-truncate"><?= htmlspecialchars($invite['table_name']) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- AJOUT : Bouton Google Calendar chic et discret -->
                        <?php if (!empty($google_cal_url)): ?>
                        <div class="mb-2 data-html2canvas-ignore">
                            <a href="<?= $google_cal_url ?>" target="_blank" class="btn-calendar">
                                <i class="bi bi-calendar-plus"></i> Ajouter à mon Google Agenda
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-4 qr-section">
                        <div class="small fw-bold text-muted mb-1">CODE D'ACCÈS</div>
                        <div class="detail-value text-uppercase mb-2"><?= htmlspecialchars($invite['invite_code']) ?>
                        </div>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=<?= urlencode($current_url) ?>"
                            alt="QR Code" class="img-fluid" style="max-width: 110px;">
                        <p class="text-muted mt-2 mb-0" style="font-size: 0.7rem;">Présentez ce QR à l'entrée</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button class="btn btn-download-img d-inline-flex align-items-center justify-content-center gap-2"
                id="downloadImageBtn">
                <i class="bi bi-file-earmark-image"></i> Télécharger ma carte
            </button>
        </div>
    </section>

    <section id="livredor" class="container py-4 reveal">
        <h2 class="section-title">Livre d'or</h2>
        <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="p-3 p-md-4 rounded shadow-sm bg-white border">
                    <h5 class="mb-3" style="font-family: 'Playfair Display', serif;">Laissez un mot</h5>

                    <?php if($preview_mode): ?>
                    <div class="alert alert-warning text-center small"><i class="bi bi-exclamation-triangle-fill"></i>
                        Masqué en mode aperçu.</div>
                    <?php else: ?>
                    <form method="POST">
                        <div class="mb-2"><input type="text" class="form-control bg-light fw-bold"
                                value="<?= htmlspecialchars($invite['fullname']) ?>" readonly disabled></div>
                        <div class="mb-3"><textarea name="message" class="form-control bg-light text-dark" rows="3"
                                placeholder="Vos vœux ici..." required></textarea></div>
                        <button type="submit" name="save_message" class="btn btn-custom btn-sm"><i
                                class="bi bi-send-fill"></i> Envoyer</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-7">
                <h5 class="mb-3" style="font-family: 'Playfair Display', serif;"><i
                        class="bi bi-chat-left-heart text-rose"></i> Mots des invités</h5>
                <div style="max-height: 320px; overflow-y: auto; padding-right: 5px;">
                    <?php if(empty($messages_livre_or)): ?>
                    <p class="text-muted small">Aucun message pour l'instant.</p>
                    <?php else: ?>
                    <?php foreach($messages_livre_or as $m): ?>
                    <div class="guestbook-msg">
                        <strong
                            class="text-secondary d-block small mb-1"><?= htmlspecialchars($m['fullname'] ?? 'Anonyme') ?>
                            :</strong>
                        <p class="mb-1 small text-dark"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
                        <small class="text-muted" style="font-size:0.65rem;"><i class="bi bi-clock"></i>
                            <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="adresse" class="container-fluid px-0 mt-5 reveal">
        <div class="container text-center mb-3">
            <h2 class="section-title">Localisation</h2>
            <div class="heart-separator"><i class="bi bi-heart-fill"></i></div>
            <p class="small mb-3"><i class="bi bi-geo-alt-fill text-danger"></i>
                <?= htmlspecialchars($invite['location']) ?></p>
            <a target="_blank" href="https://maps.google.com/?q=<?= urlencode($invite['location']) ?>"
                class="btn btn-outline-dark btn-sm px-3 mb-2"><i class="bi bi-map-fill"></i> Ouvrir sur Google Maps</a>
        </div>
        <div class="w-100" style="height: 300px;">
            <iframe
                src="https://maps.google.com/maps?q=<?= urlencode($invite['location']) ?>&t=&z=13&ie=UTF8&iwloc=&output=embed"
                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </section>

    <footer class="bg-dark text-white text-center py-3">
        <p class="small text-white-50 mb-0">© 2026 - H-Event | Tous droits réservés.</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Gestion de la Musique en arrière plan
    function toggleMusic() {
        const music = document.getElementById('bgMusic');
        const btn = document.getElementById('musicBtn');

        music.volume = 0.2;
        music.play();

        if (music.paused) {
            music.play().catch(e => console.log("Lecture bloquée par le navigateur, interaction requise."));
            btn.innerHTML = '<i class="bi bi-pause-fill"></i>';
        } else {
            music.pause();
            btn.innerHTML = '<i class="bi bi-music-note-beamed"></i>';
        }
    }

    // Lancer automatiquement si possible
    window.addEventListener('click', () => {
        const music = document.getElementById('bgMusic');
        if (music.paused && !music.src.includes('paused_manually')) {
            music.play().catch(() => {});
            document.getElementById('musicBtn').innerHTML = '<i class="bi bi-pause-fill"></i>';
        }
    }, {
        once: true
    });

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

    window.addEventListener('scroll', revealElements);

    function revealElements() {
        const reveals = document.querySelectorAll('.reveal');
        for (let i = 0; i < reveals.length; i++) {
            const windowHeight = window.innerHeight;
            const elementTop = reveals[i].getBoundingClientRect().top;
            if (elementTop < windowHeight - 100) {
                reveals[i].classList.add('active');
            }
        }
    }

    document.getElementById("downloadImageBtn").addEventListener("click", async function() {
        const card = document.getElementById("invitationCardToDownload");
        if (!card) return;
        const originalOverflow = card.style.overflow;
        const originalTransform = card.style.transform;
        card.style.overflow = "visible";
        card.style.transform = "none";
        await new Promise(r => setTimeout(r, 300));
        const rect = card.getBoundingClientRect();
        html2canvas(card, {
            scale: 2,
            useCORS: true,
            backgroundColor: "#fdfbf9",
            width: rect.width,
            height: rect.height,
            x: 0,
            y: 0,
            scrollX: -window.scrollX,
            scrollY: -window.scrollY
        }).then(canvas => {
            const img = canvas.toDataURL("image/png");
            const link = document.createElement("a");
            link.href = img;
            link.download = "invitation-hevent.png";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            card.style.overflow = originalOverflow;
            card.style.transform = originalTransform;
        }).catch(err => {
            alert("Erreur capture carte");
        });
    });
    revealElements();
    </script>
</body>

</html>