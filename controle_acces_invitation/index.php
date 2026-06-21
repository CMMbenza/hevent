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
    <title>Contrôle d'accès - Hevent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', system-ui, sans-serif;
    }

    .card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }

    .card-header {
        border-radius: 20px 20px 0 0 !important;
        background: #4e73df !important;
        padding: 20px;
    }

    #reader {
        border-radius: 15px;
        overflow: hidden;
        border: 2px solid #e9ecef;
    }

    .btn-success {
        background: #1cc88a;
        border: none;
        border-radius: 12px;
        padding: 10px 20px;
    }

    .form-control {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 12px;
    }

    .modal-content {
        border-radius: 20px;
        border: none;
    }
    </style>
</head>

<body class="py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header text-white text-center">
                        <h4 class="mb-0"><i class="bi bi-qr-code-scan me-2"></i>Contrôle Hevent</h4>
                    </div>
                    <div class="card-body p-4">
                        <div id="reader" class="mb-4"></div>

                        <div class="text-center text-muted mb-3"><small>OU SAISIE MANUELLE</small></div>

                        <div class="input-group">
                            <input type="text" id="manualCode" class="form-control" placeholder="Code invité...">
                            <button class="btn btn-success" onclick="validerCode()"
                                style="background-color: var(--dark-slate); color: var(--primary-rose); font-weight: 500; padding: 10px 20px;">
                                <i class="bi bi-arrow-right-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-body text-center p-4" id="modalBody"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function validerCode(code = null) {
        const targetCode = code || document.getElementById("manualCode").value;
        const eventId = <?= json_encode($event_id) ?>;

        if (!targetCode) return;

        let url = `verifier.php?code=${encodeURIComponent(targetCode)}`;

        if (eventId) {
            url += `&event_id=${eventId}`;
        }

        fetch(url)
            .then(res => res.text())
            .then(data => {
                document.getElementById("modalBody").innerHTML = data;
                new bootstrap.Modal(document.getElementById('resultModal')).show();

                document.getElementById("manualCode").value = "";

                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('resultModal'))?.hide();
                }, 2000);
            });
    }

    <?php if (!empty($code)): ?>
    window.onload = () => validerCode("<?= $code ?>");
    <?php endif; ?>

    let html5QrcodeScanner = new Html5QrcodeScanner("reader", {
        fps: 10,
        qrbox: 250
    }, false);
    html5QrcodeScanner.render((decodedText) => validerCode(decodedText));
    </script>
</body>

</html>