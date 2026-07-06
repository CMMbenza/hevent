<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$user_id = $_SESSION['user_id'] ?? null;
/*
----------------------------------
FLASH MESSAGES
----------------------------------
*/
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

unset($_SESSION['error'], $_SESSION['success']);

/*
----------------------------------
GET ACTION
----------------------------------
*/
$action = $_GET['action'] ?? 'create';
$id = $_GET['id'] ?? null;

/*
----------------------------------
LOAD EVENT (EDIT) OR PRE-GENERATE CODE (CREATE)
----------------------------------
*/
$editEvent = null;
$generat_code = null;

if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("
        SELECT * FROM events
        WHERE generat = ? AND user_id = ?
    ");
    $stmt->execute([$id, $user_id]);
    $editEvent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editEvent) {
        $_SESSION['error'] = "❌ Événement introuvable";
        header("Location: ../events/");
        exit;
    }
    $generat_code = $editEvent['generat'];
} else {
    $generat_code = rand(100000, 999999);
}

/*
----------------------------------
CREATE / UPDATE EVENT
----------------------------------
*/
if (isset($_POST['save'])) {

    $title = $_POST['title'];
    $type = $_POST['event_type'];
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    $lieu = $_POST['lieu']; // Nouveau champ Lieu
    $location = $_POST['location']; // Devient Adresse / Coordonnées GPS
    $description = $_POST['description'];
    $pack_code_input = trim($_POST['pack_code']);
    
    $final_generat = $_POST['generat'];

    /*
    ----------------------------------
    PACK CHECK
    ----------------------------------
    */
    $stmt = $pdo->prepare("SELECT * FROM packs WHERE code = ?");
    $stmt->execute([$pack_code_input]);
    $pack = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pack) {
        $_SESSION['error'] = "❌ Code pack invalide";
        header("Location: ../events/form_event.php" . ($action === 'edit' ? "?action=edit&id=".$id : ""));
        exit;
    }

    if ($pack['is_used'] == 1 && ($action === 'create' || $pack_code_input !== $editEvent['pack_code'])) {
        $_SESSION['error'] = "❌ Ce pack a déjà été utilisé";
        header("Location: ../events/form_event.php" . ($action === 'edit' ? "?action=edit&id=".$id : ""));
        exit;
    }

    /*
    ----------------------------------
    GENERATE NEW PACK CODE
    ----------------------------------
    */
    if ($action === 'create' || $pack_code_input !== $editEvent['pack_code']) {
        do {
            $new_code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $check = $pdo->prepare("SELECT id FROM packs WHERE code = ?");
            $check->execute([$new_code]);
        } while ($check->rowCount() > 0);

        $stmt = $pdo->prepare("
            INSERT INTO packs (name, code, max_invites, price, is_used)
            VALUES (?,?,?,?,1)
        ");
        $stmt->execute([
            $pack['name'],
            $new_code,
            $pack['max_invites'],
            $pack['price']
        ]);
    } else {
        $new_code = $editEvent['pack_code'];
    }

    /*
    ----------------------------------
    COVER IMAGE
    ----------------------------------
    */
    $cover = $editEvent['cover_image'] ?? null;

    if (!empty($_FILES['cover_image']['name'])) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $uploadDir = __DIR__ . "/../../uploads/covers/";

        move_uploaded_file(
            $_FILES['cover_image']['tmp_name'],
            $uploadDir . $name
        );
        $cover = $name;
    }

    /*
    ----------------------------------
    UPDATE MODE
    ----------------------------------
    */
    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("
            UPDATE events
            SET title=?, event_type=?, event_date=?, event_time=?, lieu=?, location=?, description=?, cover_image=?, pack_code=?
            WHERE id=? AND user_id=?
        ");
        $stmt->execute([
            $title,
            $type,
            $date,
            $time,
            $lieu,
            $location,
            $description,
            $cover,
            $new_code,
            $_POST['id'],
            $_SESSION['user_id']
        ]);
        $_SESSION['success'] = "✔ Événement modifié avec succès";
    }
    /*
    ----------------------------------
    CREATE MODE
    ----------------------------------
    */
    else {
        $stmt = $pdo->prepare("
            INSERT INTO events
            (generat, user_id, title, event_type, event_date, event_time, lieu, location, description, cover_image, pack_code)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $final_generat,
            $_SESSION['user_id'],
            $title,
            $type,
            $date,
            $time,
            $lieu,
            $location,
            $description,
            $cover,
            $new_code
        ]);
        $_SESSION['success'] = "✔ Événement créé avec succès";
    }

    header("Location: ../events/");
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<div class="container-fluid">

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px;">
        <?= $error ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px;">
        <?= $success ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="mb-4 p-3">
        <h2 class="fw-bold" style="color: var(--dark-slate);">
            <i class="bi bi-calendar-plus me-2" style="color: var(--primary-rose);"></i>
            <?= $action === 'edit' ? "Modification" : "Création d'événement" ?>
        </h2>
        <small class="text-muted">Remplissez les détails ci-dessous pour configurer votre interface
            d'invitation.</small>
    </div>

    <div class="card p-4 border-0 shadow-sm" style="border-radius: 16px; background-color: #ffffff;">

        <form method="POST" enctype="multipart/form-data">

            <?php if ($editEvent): ?>
            <input type="hidden" name="id" value="<?= $editEvent['id'] ?>">
            <?php endif; ?>

            <input type="hidden" name="generat" value="<?= $generat_code ?>">

            <div class="row">
                <div class="d-none col-md-4 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Code Événement
                        (Generat)</label>
                    <input type="text" class="form-control border shadow-none bg-light fw-bold text-primary"
                        style="border-radius: 10px; padding: 10px; letter-spacing: 1px;" value="<?= $generat_code ?>"
                        readonly>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Type
                        d'événement</label>
                    <select name="event_type" id="event_type" class="form-select border shadow-none"
                        style="border-radius: 10px; padding: 10px;" required>
                        <option disabled selected hidden>Sélectionnez un type</option>
                        <?php
                        $types = ['Mariage','Anniversaire','Bapteme','Conference','Reunion','Personnalise'];
                        foreach ($types as $t):
                        ?>
                        <option value="<?= $t ?>" <?= ($editEvent['event_type'] ?? '') == $t ? 'selected' : '' ?>>
                            <?= $t ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Titre</label>
                    <input type="text" name="title" class="form-control border shadow-none"
                        style="border-radius: 10px; padding: 10px;" value="<?= $editEvent['title'] ?? '' ?>"
                        placeholder="Titre de votre événement" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Date</label>
                    <input type="date" name="event_date" class="form-control border shadow-none"
                        style="border-radius: 10px; padding: 10px;" value="<?= $editEvent['event_date'] ?? '' ?>"
                        required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Heure de début</label>
                    <input type="time" name="event_time" class="form-control border shadow-none"
                        style="border-radius: 10px; padding: 10px;" value="<?= $editEvent['event_time'] ?? '' ?>"
                        required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Nom du Lieu</label>
                    <input type="text" name="lieu" class="form-control border shadow-none"
                        style="border-radius: 10px; padding: 10px;" value="<?= $editEvent['lieu'] ?? '' ?>"
                        placeholder="Ex: Hôtel de Ville, Salle des Fêtes" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Adresse
                        (Location)</label>
                    <input type="text" name="location" class="form-control border shadow-none"
                        style="border-radius: 10px; padding: 10px;" value="<?= $editEvent['location'] ?? '' ?>"
                        placeholder="Ex: 15 Rue de la Paix, Paris ou Coordonnées" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Description &
                    Déroulement</label>
                <textarea name="description" class="form-control border shadow-none" rows="5"
                    style="border-radius: 10px; padding: 12px;"
                    placeholder="Décrivez votre événement à vos invités (ex : planning de la journée, dress code exigé, thématique optionnelle...)"><?= $editEvent['description'] ?? '' ?></textarea>
            </div>

            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Image de
                        couverture</label>
                    <input type="file" name="cover_image" class="form-control border shadow-none"
                        style="border-radius: 10px; padding: 8px;" id="coverInput">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-muted" style="font-size: 0.85rem;">Code d'activation du
                        Pack</label>
                    <input type="text" name="pack_code" class="form-control border shadow-none"
                        style="border-radius: 10px; padding: 10px;" placeholder="Entrez votre code d'activation"
                        value="<?= $editEvent['pack_code'] ?? '' ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <img id="preview"
                    style="max-width:320px; width:100%; display:<?= !empty($editEvent['cover_image']) ? 'block' : 'none' ?>; border-radius:12px; border: 1px solid rgba(212, 163, 150, 0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 10px;"
                    src="<?= !empty($editEvent['cover_image']) ? '../../uploads/covers/'.$editEvent['cover_image'] : '' ?>">
            </div>

            <button class="btn btn-primary shadow-sm" name="save"
                style="background-color: var(--dark-slate); border-color: var(--dark-slate); color: var(--primary-rose); font-weight: 600; border-radius: 12px; padding: 12px; width: 100%; transition: all 0.2s;">
                <i class="bi bi-check2-circle me-2"></i>
                <?= $action === 'edit' ? "Confirmer la modification" : "Enregistrer et créer l'événement" ?>
            </button>

        </form>

    </div>
</div>

<script>
const titles = {
    "Mariage": "Ex: Mariage de Jean & Marie",
    "Anniversaire": "Ex: Anniversaire de Paul",
    "Bapteme": "Ex: Baptême de Emma",
    "Conference": "Ex: Conférence Tech 2026",
    "Reunion": "Ex: Réunion équipe projet",
    "Personnalise": "Ex: Titre de votre événement"
};

document.getElementById("event_type").addEventListener("change", function() {
    document.querySelector("input[name='title']").placeholder = titles[this.value] ||
        "Ex: Titre de votre événement";
});

document.getElementById('coverInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const img = document.getElementById('preview');
            img.src = evt.target.result;
            img.style.width = '100%';
            img.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>