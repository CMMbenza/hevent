<?php
session_start();

require_once '../config/database.php';

$error = '';
$success = '';
$email_verified = false;
$email_input = '';

// Étape 1 : Vérification de l'email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'check_email') {
    $email_input = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $email_verified = true; // L'email existe, on va afficher le formulaire de reset
    } else {
        $error = "Cette adresse email n'existe pas dans notre base de données.";
    }
}

// Étape 2 : Mise à jour du mot de passe
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset_password') {
    $email_input = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($password !== $password_confirm) {
        $error = "Les deux mots de passe ne correspondent pas.";
        $email_verified = true; // On reste sur le formulaire de reset
    } elseif (strlen($password) < 6) { // Sécurité minimale
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
        $email_verified = true;
    } else {
        // Hachage du nouveau mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Détection automatique du nom de la colonne (PASSWORD ou password)
        // On récupère une ligne pour inspecter les clés si nécessaire, ou on tente l'update standard.
        // Par sécurité avec votre structure adaptative :
        try {
            // On tente d'abord avec 'password' en minuscule
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $email_input]);
        } catch (PDOException $e) {
            // Si ça échoue, on tente avec 'PASSWORD' en majuscule
            $stmt = $pdo->prepare("UPDATE users SET PASSWORD = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $email_input]);
        }

        $success = "Votre mot de passe a bien été mis à jour ! Vous pouvez vous connecter.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié | H-Event</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap"
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
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(212, 163, 150, 0.15) 0%, rgba(26, 34, 50, 0.05) 100%);
    }

    .login-card {
        background-color: var(--bg-light);
        border-radius: 20px;
        box-shadow: 0 15px 45px rgba(26, 34, 50, 0.08);
        border: 1px solid rgba(212, 163, 150, 0.2);
        overflow: hidden;
    }

    .login-header {
        background-color: var(--dark-slate);
        color: white;
        padding: 35px 20px;
        text-align: center;
        position: relative;
    }

    .brand-title {
        font-family: 'Great Vibes', cursive;
        font-size: 3rem;
        color: var(--primary-rose);
        margin-bottom: 0;
        line-height: 1;
    }

    .brand-subtitle {
        font-family: 'Playfair Display', serif;
        font-size: 0.85rem;
        letter-spacing: 3px;
        text-transform: uppercase;
        opacity: 0.8;
        margin-top: 8px;
    }

    .login-body {
        padding: 40px 35px;
    }

    .form-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #555;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .input-group-text {
        background-color: #fff;
        color: #888;
        border-color: #e5dcd9;
    }

    .input-group-text.pre-icon {
        border-right: none;
        border-radius: 10px 0 0 10px;
    }

    .input-group-text.toggle-password {
        border-left: none;
        border-radius: 0 10px 10px 0;
        cursor: pointer;
        transition: color 0.2s;
    }

    .input-group-text.toggle-password:hover {
        color: var(--primary-rose-dark);
    }

    .form-control {
        border-left: none;
        border-radius: 0;
        padding: 12px;
        border-color: #e5dcd9;
        background-color: #fff;
        font-size: 0.95rem;
    }

    .form-control.input-email {
        border-radius: 0 10px 10px 0;
    }

    .form-control.input-password-confirm {
        border-radius: 0 10px 10px 0;
    }

    .form-control:focus {
        border-color: #e5dcd9;
        box-shadow: none;
        background-color: #fff;
    }

    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control {
        border-color: var(--primary-rose);
    }

    .btn-custom {
        background-color: var(--primary-rose);
        color: white;
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 1px;
        border: none;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(212, 163, 150, 0.3);
    }

    .btn-custom:hover {
        background-color: var(--primary-rose-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(212, 163, 150, 0.4);
    }

    .alert-danger {
        background-color: #fdf2f0;
        border-color: #f5c2b8;
        color: #922b16;
        font-size: 0.9rem;
        border-radius: 10px;
    }

    .alert-success {
        background-color: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
        font-size: 0.9rem;
        border-radius: 10px;
    }

    .link-register {
        color: var(--primary-rose-dark);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .link-register:hover {
        color: var(--dark-slate);
    }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row justify-content-center mt-2 mb-2">
            <div class="col-md-6 col-lg-6 col-sm-12">

                <div class="card login-card">

                    <div class="login-header">
                        <h1 class="brand-title">H-Event</h1>
                        <div class="brand-subtitle">Réinitialisation du Mot de Passe</div>
                    </div>

                    <div class="login-body">

                        <?php if($error): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if($success): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-check-circle-fill"></i>
                            <div><?= htmlspecialchars($success) ?></div>
                        </div>
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-custom w-100">Aller à la page de connexion</a>
                        </div>
                        <?php else: ?>

                        <?php if(!$email_verified): ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="check_email">

                            <div class="mb-4">
                                <label class="form-label">Entrez votre Adresse Email</label>
                                <div class="input-group">
                                    <span class="input-group-text pre-icon"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control input-email"
                                        placeholder="chrismbenza@hevent.com"
                                        value="<?= htmlspecialchars($email_input) ?>" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom w-100">
                                <i class="bi bi-search me-2"></i> Vérifier l'adresse email
                            </button>
                        </form>

                        <?php else: ?>
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email_input) ?>">

                            <div class="alert alert-info small py-2 mb-3">
                                Email validé : <strong><?= htmlspecialchars($email_input) ?></strong>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text pre-icon"><i
                                            class="bi bi-lock text-primary-rose"></i></span>
                                    <input type="password" name="password" id="passwordInput" class="form-control"
                                        placeholder="••••••••" required>
                                    <span class="input-group-text toggle-password" id="togglePasswordBtn">
                                        <i class="bi bi-eye" id="passwordIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Répéter le mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text pre-icon"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="password_confirm" id="passwordConfirmInput"
                                        class="form-control input-password-confirm" placeholder="••••••••" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom w-100">
                                <i class="bi bi-shield-check me-2"></i> Mettre à jour le mot de passe
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="login.php" class="link-register small"><i class="bi bi-arrow-left me-1"></i> Retour
                                à la connexion</a>
                        </div>

                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    // Script pour afficher/masquer le premier champ mot de passe
    const passwordInput = document.getElementById('passwordInput');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordIcon = document.getElementById('passwordIcon');

    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            passwordIcon.classList.toggle('bi-eye');
            passwordIcon.classList.toggle('bi-eye-slash');
        });
    }
    </script>

</body>

</html>