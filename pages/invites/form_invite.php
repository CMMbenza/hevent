<?php
session_start();

// 1. SÉCURITÉ ET REDIRECTION
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../");
    exit;
}

// 2. ÉVITE LE RETOUR EN ARRIÈRE APRÈS DÉCONNEXION
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 3. INCLUSIONS TECHNIQUES & CONFIGURATION
include '../../includes/constant.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    $_SESSION['error'] = "❌ Événement introuvable";
    header("Location: ../events/");
    exit;
}

/*
----------------------------------
GET EVENT
----------------------------------
*/
$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ? ");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['error'] = "❌ Accès refusé";
    header("Location: ../events/");
    exit;
}

/*
----------------------------------
TABLES (CORRIGÉ : generat_event)
----------------------------------
*/
$stmt = $pdo->prepare("SELECT * FROM event_tables WHERE generat_event = ?");
$stmt->execute([$event_id]);
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
----------------------------------
SAVE INVITE
----------------------------------
*/
if (isset($_POST['save_invite'])) {

    // COUNT INVITES (LIMIT CHECK - CORRIGÉ : generat_event)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invites WHERE generat_event = ?");
    $stmt->execute([$event_id]);
    $totalInvites = $stmt->fetchColumn();

    // GET PACK LIMIT
    $pack = getPack($pdo, $event['pack_code']);
    $limit = $pack['max_invites'] ?? 0;

    // BLOCK IF LIMIT REACHED
    if ($limit != -1 && $totalInvites >= $limit) {
        $_SESSION['error'] = "❌ Limite d'invités atteinte pour ce pack";
        header("Location: form_invite.php?event_id=$event_id");
        exit;
    }

    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $table_name = trim($_POST['table_name']);

    if (empty($fullname)) {
        $_SESSION['error'] = "❌ Nom obligatoire";
        header("Location: form_invite.php?event_id=$event_id");
        exit;
    }

    // TABLE AUTO CREATE / FIND (CORRIGÉ : generat_event)
    $table_id = null;

    if (!empty($table_name)) {
        $stmt = $pdo->prepare("SELECT id FROM event_tables WHERE generat_event = ? AND table_name = ?");
        $stmt->execute([$event_id, $table_name]);
        $table = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($table) {
            $table_id = $table['id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO event_tables (generat_event, table_name)
                VALUES (?, ?)
            ");
            $stmt->execute([$event_id, $table_name]);
            $table_id = $pdo->lastInsertId();
        }
    }

    // GENERATE UNIQUE CODE
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $check = $pdo->prepare("SELECT id FROM invites WHERE invite_code = ?");
        $check->execute([$code]);
    } while ($check->rowCount() > 0);

    // INSERT INVITE (CORRIGÉ : generat_event)
    $stmt = $pdo->prepare("
        INSERT INTO invites (generat_event, fullname, phone, table_id, invite_code, rsvp_status, checked_in)
        VALUES (?, ?, ?, ?, ?, 'Pending', 0)
    ");

    $stmt->execute([
        $event_id,
        $fullname,
        $phone,
        $table_id,
        $code
    ]);

    $_SESSION['success'] = "✔ Invité ajouté avec succès";
    header("Location: form_invite.php?event_id=$event_id");
    exit;
}

/*
----------------------------------
IMPORT CSV
----------------------------------
*/
if (isset($_POST['import_csv']) && !empty($_FILES['csv']['name'])) {

    // LIMIT CHECK POUR L'IMPORT COMPLET
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invites WHERE generat_event = ?");
    $stmt->execute([$event_id]);
    $totalInvites = $stmt->fetchColumn();

    $pack = getPack($pdo, $event['pack_code']);
    $limit = $pack['max_invites'] ?? 0;

    $file = fopen($_FILES['csv']['tmp_name'], "r");
    $imported = 0;
    $skipped = 0;

    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        // Validation limite pack en cours d'importation
        if ($limit != -1 && ($totalInvites + $imported) >= $limit) {
            $skipped++;
            continue;
        }

        $fullname = trim($data[0] ?? '');
        $phone = trim($data[1] ?? '');

        if (empty($fullname)) continue;

        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $check = $pdo->prepare("SELECT id FROM invites WHERE invite_code = ?");
            $check->execute([$code]);
        } while ($check->rowCount() > 0);

        // CORRIGÉ : generat_event
        $stmt = $pdo->prepare("
            INSERT INTO invites (generat_event, fullname, phone, invite_code, rsvp_status, checked_in)
            VALUES (?, ?, ?, ?, 'Pending', 0)
        ");
        $stmt->execute([$event_id, $fullname, $phone, $code]);
        $imported++;
    }

    fclose($file);

    if ($skipped > 0) {
        $_SESSION['success'] = "✔ Import partiel : $imported invités ajoutés. ($skipped ignorés par limite de pack)";
    } else {
        $_SESSION['success'] = "✔ Import CSV réussi ($imported invités ajoutés)";
    }

    header("Location: ../events/event_show.php?id=$event_id");
    exit;
}

// 4. INCLUSIONS VISUELLES
include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<div class="container-fluid">

    <div class="card p-3 shadow-sm mb-4 border-0">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-1 fw-bold" style="color: var(--dark-slate);">
                    <i class="bi bi-person-plus-fill me-1"></i> Gestion des invitations
                </h4>
                <p class="text-muted mb-0">
                    Événement lié : <span class="fw-bold"
                        style="color: var(--dark-slate);"><?= htmlspecialchars($event['title']) ?></span>
                </p>
            </div>
            <a href="../events/event_show.php?id=<?= $event_id ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger shadow-sm border-0 align-items-center d-flex">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success shadow-sm border-0 align-items-center d-flex">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-bold text-white d-flex align-items-center"
                    style="background-color: var(--dark-slate); color: var(--primary-rose);">
                    <i class="bi bi-person-fill-add me-2"></i> Ajout Manuel Unique
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><i class="bi bi-person text-muted me-1"></i>Nom de
                                l'invité</label>
                            <input type="text" name="fullname" class="form-control" required autofocus
                                placeholder="Ex: Chris Mbenza">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold"><i
                                    class="bi bi-telephone text-muted me-1"></i>Téléphone</label>
                            <input type="text" name="phone" class="form-control" placeholder="Ex: +243 845 757 799">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold"><i
                                    class="bi bi-grid-3x3-gap text-muted me-1"></i>Assignation Table (Optionnel)</label>
                            <input list="tableList" name="table_name" class="form-control"
                                placeholder="Saisissez ou sélectionnez une table">
                            <datalist id="tableList">
                                <?php foreach ($tables as $t): ?>
                                <option value="<?= htmlspecialchars($t['table_name']) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                            <div class="form-text text-muted mt-1 small">
                                <i class="bi bi-info-circle"></i> Si le nom saisi n'existe pas encore, la table sera
                                créée automatiquement.
                            </div>
                        </div>

                        <button class="btn w-100 fw-bold shadow-sm py-2 mt-2" name="save_invite"
                            style="background-color: var(--dark-slate); color: var(--primary-rose);">
                            <i class="bi bi-plus-circle me-1"></i> Valider et inscrire l'invité
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-none col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-bold text-white d-flex align-items-center"
                    style="background-color: #212529;">
                    <i class="bi bi-filetype-csv me-2 text-warning"></i> Import Collectif par CSV
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label small fw-bold"><i class="bi bi-upload text-muted me-1"></i>Fichier
                                source (.csv)</label>
                            <input type="file" name="csv" class="form-control" accept=".csv" required>
                        </div>

                        <button class="btn btn-dark w-100 fw-bold shadow-sm py-2" name="import_csv">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Lancer le téléversement
                        </button>
                    </form>

                    <div class="bg-light p-3 rounded border mt-3">
                        <h6 class="small fw-bold text-dark mb-2"><i class="bi bi-info-square me-1"></i> Consignes de
                            structure :</h6>
                        <small class="text-muted d-block">Le fichier doit comporter 2 colonnes sans en-tête structurées
                            comme ceci :</small>
                        <code class="d-block bg-white p-2 border rounded my-2 text-dark small font-monospace">
                            Nom Complet, Téléphone<br>
                            Alice Martin, 0612345678<br>
                            Bob Dupont, 0799887766
                        </code>
                        <small class="text-danger small"><i class="bi bi-exclamation-circle"></i> Les quotas et limites
                            de votre abonnement s'appliqueront lors de la lecture.</small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>