<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ? AND user_id = ? LIMIT 1");
$stmt->execute([$event_id, $_SESSION['user_id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Événement introuvable ou accès refusé.");
}

$stmt = $pdo->prepare("SELECT * FROM gallery WHERE generat_event = ? ORDER BY created_at DESC");
$stmt->execute([$event_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<style>
.image-container {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
}

.image-container img {
    transition: transform .5s cubic-bezier(0.16, 1, 0.3, 1);
}

.image-container:hover img {
    transform: scale(1.06);
}

.image-actions {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(.8);
    display: flex;
    gap: 12px;
    opacity: 0;
    transition: all .3s ease;
    z-index: 3;
}

.image-container:hover .image-actions {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.image-container::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, rgba(0, 0, 0, .2), rgba(0, 0, 0, .6));
    opacity: 0;
    transition: opacity .35s ease;
    z-index: 1;
}

.image-container:hover::before {
    opacity: 1;
}

.image-actions .btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    background: rgba(255, 255, 255, .2);
    border: 1px solid rgba(255, 255, 255, .3);
    color: #fff;
    transition: all .2s ease;
}

.image-actions .btn:hover {
    transform: translateY(-3px);
    background: #fff;
    color: #111;
}

.upload-dropzone {
    border: 2px dashed #dee2e6;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all .2s ease;
}

.upload-dropzone:hover {
    border-color: #0d6efd;
    background: #f1f6ff;
}

#preview-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
    max-height: 240px;
    overflow-y: auto;
    padding: 5px;
}

.preview-thumb {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}
</style>

<div class="container-fluid px-4">

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success mt-3"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger mt-3"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4" style="color: var(--primary-rose);">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-images"></i> Gestion de la Galerie</h1>
            <p class="mb-0 text-muted">Événement : <strong><?= htmlspecialchars($event['title']) ?></strong></p>
        </div>
        <a href="../events/index.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row g-4" style="color: var(--primary-rose);">
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0 fw-bold text-dark">
                    <i class="bi bi-cloud-upload-fill"></i> Ajouter des souvenirs
                </div>
                <div class="card-body">
                    <form id="uploadForm" method="POST" action="upload.php?event_id=<?= $event_id ?>"
                        enctype="multipart/form-data">
                        <div class="upload-dropzone" onclick="document.getElementById('photos').click();">
                            <i class="bi bi-camera fs-1 text-muted mb-2 d-block"></i>
                            <span class="text-muted d-block small">Cliquez pour sélectionner vos photos</span>
                            <span class="badge bg-light text-dark mt-2">Max recommandé: 25Mo par image</span>
                            <input type="file" name="photos[]" id="photos" class="d-none" accept=".jpg,.jpeg,.png,.webp"
                                multiple required>
                        </div>

                        <div id="preview-container" class="mt-3" style="display: none;"></div>

                        <button type="submit" class="btn btn-primary w-100 mt-3 py-2 fw-bold"
                            style="background-color: var(--dark-slate); border-color: var(--dark-slate); color: var(--primary-rose); font-weight: 500; border-radius: 12px; padding: 10px 20px;">
                            <i class="bi bi-cloud-arrow-up-fill"></i> Téléverser & Compresser
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7" style="color: var(--primary-rose);">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0 fw-bold text-dark">
                    <i class="bi bi-grid-3x3-gap"></i> Photos téléversées (<?= count($images) ?>)
                </div>
                <div class="card-body">
                    <?php if(empty($images)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-image fs-1 d-block mb-3 opacity-50"></i>
                        Aucune photo dans la galerie pour le moment.
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach($images as $index => $img): ?>
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="image-container">
                                <img src="../../uploads/gallery/<?= htmlspecialchars($img['photo']) ?>" class="w-100"
                                    style="height:160px; object-fit:cover;">
                                <div class="image-actions">
                                    <button class="btn" data-bs-toggle="modal" data-bs-target="#galleryModal"
                                        data-slide="<?= $index ?>">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <a href="upload.php?delete=<?= $img['id'] ?>&event_id=<?= $event_id ?>"
                                        class="btn btn-danger-custom"
                                        style="background: rgba(220, 53, 69, 0.25); border-color: rgba(220, 53, 69, 0.3);"
                                        onclick="return confirm('Supprimer définitivement cette photo ?')">
                                        <i class="bi bi-trash-fill text-danger"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($images)): ?>
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0 p-0 mb-2 justify-content-end">
                <button type="button" class="btn-close btn-close-white fs-4" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="galleryCarousel" class="carousel slide">
                    <div class="carousel-inner">
                        <?php foreach($images as $index => $img): ?>
                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                            <img src="../../uploads/gallery/<?= htmlspecialchars($img['photo']) ?>"
                                class="d-block w-100 rounded-3" style="max-height:80vh; object-fit:contain;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const galleryModal = document.getElementById('galleryModal');
    if (galleryModal) {
        galleryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const slideIndex = parseInt(button.getAttribute('data-slide'), 10);
            const carouselElement = document.getElementById('galleryCarousel');
            const carousel = bootstrap.Carousel.getOrCreateInstance(carouselElement);
            carousel.to(slideIndex);
        });
    }

    const photosInput = document.getElementById('photos');
    const previewContainer = document.getElementById('preview-container');
    const uploadForm = document.getElementById('uploadForm');

    if (photosInput) {
        photosInput.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            let files = this.files;
            if (files.length === 0) {
                previewContainer.style.display = 'none';
                return;
            }

            previewContainer.style.display = 'grid';

            Array.from(files).forEach(file => {
                if (!file.type.match('image.*')) return;

                // Alerte visuelle si un fichier est trop lourd pour le navigateur (Ex: > 35 Mo)
                if (file.size > 35 * 1024 * 1024) {
                    alert(
                        `Attention : Le fichier "${file.name}" est extrêmement lourd (${(file.size/1024/1024).toFixed(1)} Mo). S'il bloque à l'envoi, veuillez le réduire avant.`);
                }

                let reader = new FileReader();
                reader.onload = function(e) {
                    let img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-thumb');
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>