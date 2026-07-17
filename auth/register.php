<?php
session_start();

require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($fullname) && !empty($email) && !empty($password)) {
        
        // 1. Vérifier si l'email existe déjà
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            $error = "Cette adresse email est déjà associée à un compte.";
        } else {
            // 2. Hachage sécurisé du mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 3. Insertion du nouvel utilisateur
            // Note : Ajustez la casse de 'PASSWORD' ou 'password' selon votre table SQL
            $stmt = $pdo->prepare("
                INSERT INTO users (fullname, email, PASSWORD, role) 
                VALUES (?, ?, ?, 'user')
            ");

            if ($stmt->execute([$fullname, $email, $hashedPassword])) {
                $success = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Une erreur est survenue lors de l'inscription.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription | H-Event</title>

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
        border-right: none;
        color: #888;
        border-radius: 10px 0 0 10px;
        border-color: #e5dcd9;
    }

    .form-control {
        border-left: none;
        border-radius: 0 10px 10px 0;
        padding: 12px;
        border-color: #e5dcd9;
        background-color: #fff;
        font-size: 0.95rem;
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
        background-color: #edf7ed;
        border-color: #c3e6cb;
        color: #1e4620;
        font-size: 0.9rem;
        border-radius: 10px;
    }

    .link-login {
        color: var(--primary-rose-dark);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .link-login:hover {
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
                        <div class="brand-subtitle">Créer un compte</div>
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
                            <div><?= htmlspecialchars($success) ?>
                                <a href="login.php">Cliquez-ici pour se connecter</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="form-label">Nom complet</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="fullname" class="form-control" placeholder="Chris Mbenza"
                                        required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Adresse Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="chrismbenza@hevent.com" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••"
                                        required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-custom w-100 mb-3">
                                <i class="bi bi-person-plus me-2"></i> S'inscrire
                            </button>

                            <div class="text-center mt-3">
                                <span class="text-muted small">Déjà un compte ?</span>
                                <a href="login.php" class="link-login small ms-1">Se connecter</a>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>