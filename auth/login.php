<?php
session_start();

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ajustement de la casse pour correspondre à votre base de données ('PASSWORD' ou 'password')
    $db_password = $user['PASSWORD'] ?? $user['password'] ?? '';

    if ($user && password_verify($password, $db_password)) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];

        header('Location: ../pages/dashboard/');
        exit();

    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | H-Event</title>

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
                        <div class="brand-subtitle">Gestionnaire d'Invitations</div>
                    </div>

                    <div class="login-body">

                        <?php if($error): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                        <?php endif; ?>

                        <form method="POST" class="mb-4">

                            <div class="mb-3">
                                <label class="form-label">Adresse Email</label>
                                <div class="input-group">
                                    <span class="input-group-text pre-icon"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control input-email"
                                        placeholder="chrismbenza@hevent.com" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text pre-icon"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="passwordInput" class="form-control"
                                        placeholder="••••••••" required>
                                    <span class="input-group-text toggle-password" id="togglePasswordBtn">
                                        <i class="bi bi-eye" id="passwordIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom w-100">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Se connecter
                            </button>

                        </form>

                        <div class="text-center">
                            <span class="text-muted small">Nouveau sur H-Event ?</span>
                            <a href="register.php" class="link-register small ms-1">Créer un compte</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    const passwordInput = document.getElementById('passwordInput');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordIcon = document.getElementById('passwordIcon');

    togglePasswordBtn.addEventListener('click', function() {
        // Basculer le type d'attribut
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Basculer l'icône
        passwordIcon.classList.toggle('bi-eye');
        passwordIcon.classList.toggle('bi-eye-slash');
    });
    </script>

</body>

</html>