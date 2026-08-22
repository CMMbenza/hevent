<?php
session_start();
require_once '../config/database.php';
include '../includes/header.php';

$event_id = !empty($_GET['event_id']) ? (int) $_GET['event_id'] : null;
$code = $_GET['code'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrôle d'accès - Hevent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    .main-card {
        border: none;
        border-radius: 24px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .card-header-custom {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        padding: 25px;
    }

    #reader {
        border-radius: 16px;
        overflow: hidden;
        border: none !important;
    }

    #reader video {
        object-fit: cover;
        border-radius: 16px;
    }

    .modal-content {
        border-radius: 24px;
        border: none;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }

    .form-control-custom {
        border-radius: 14px;
        border: 2px solid #e2e8f0;
        padding: 12px 16px;
    }

    .form-control-custom:focus {
        border-color: #4e73df;
        box-shadow: none;
    }

    .btn-scan {
        border-radius: 14px;
        padding: 12px 24px;
        font-weight: 600;
    }
    </style>
</head>

<body class="py-4 py-md-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card main-card bg-white">
                    <div class="card-header-custom text-white text-center">
                        <h4 class="mb-1 fw-bold"><i class="bi bi-qr-code-scan me-2"></i>Contrôle d'Accès</h4>
                        <p class="mb-0 text-white-50 small">Scannez le Pass QR ou saisissez le code invité</p>
                    </div>

                    <div class="card-body p-4">
                        <!-- Scanner QR Code -->
                        <div id="reader" class="mb-4"></div>

                        <div class="position-relative text-center mb-4">
                            <hr class="text-muted">
                            <span
                                class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small fw-semibold">OU
                                SAISIE MANUELLE</span>
                        </div>

                        <!-- Saisie manuelle -->
                        <form onsubmit="event.preventDefault(); validerCode();">
                            <div class="input-group">
                                <input type="text" id="manualCode" class="form-control form-control-custom"
                                    placeholder="Ex: INV-89X2" autocomplete="off">
                                <button class="btn btn-primary btn-scan px-4" type="submit">
                                    <i class="bi bi-search me-1"></i> Vérifier
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour l'affichage du Pass Invité -->
    <div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-4" id="modalBody">
                    <!-- Contenu injecté dynamiquement via verifier.php -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let isProcessing = false;

    function validerCode(code = null) {
        if (isProcessing) return;

        const targetCode = code || document.getElementById("manualCode").value.trim();
        const eventId = <?= json_encode($event_id) ?>;

        if (!targetCode) return;

        isProcessing = true;

        let url = `verifier.php?code=${encodeURIComponent(targetCode)}`;
        if (eventId) {
            url += `&event_id=${eventId}`;
        }

        fetch(url)
            .then(res => res.text())
            .then(data => {
                document.getElementById("modalBody").innerHTML = data;

                const modalElement = document.getElementById('resultModal');
                const modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.show();

                document.getElementById("manualCode").value = "";

                // Réinitialiser le verrou une fois le modal fermé
                modalElement.addEventListener('hidden.bs.modal', function() {
                    isProcessing = false;
                }, {
                    once: true
                });
            })
            .catch(err => {
                console.error("Erreur:", err);
                isProcessing = false;
            });
    }

    <?php if (!empty($code)): ?>
    window.onload = () => validerCode("<?= htmlspecialchars($code) ?>");
    <?php endif; ?>

    // Initialisation du scanner
    let html5QrcodeScanner = new Html5QrcodeScanner("reader", {
        fps: 10,
        qrbox: {
            width: 220,
            height: 220
        },
        aspectRatio: 1.0
    }, false);

    html5QrcodeScanner.render((decodedText) => {
        if (!isProcessing) {
            validerCode(decodedText);
        }
    });
    </script>
</body>

</html>