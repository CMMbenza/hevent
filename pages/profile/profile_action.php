<?php
session_start();

require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/*
==================================================
MODIFICATION PROFIL
==================================================
*/
if (isset($_POST['update_profile'])) {

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {

        $_SESSION['error'] = "Veuillez remplir les champs obligatoires.";
        header("Location: index.php");
        exit;
    }

    /*
    --------------------------------------
    VERIFICATION EMAIL UNIQUE
    --------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        AND id <> ?
    ");
    $stmt->execute([$email, $user_id]);

    if ($stmt->fetch()) {

        $_SESSION['error'] = "Cette adresse email est déjà utilisée.";
        header("Location: index.php");
        exit;
    }

    /*
    --------------------------------------
    AVATAR
    --------------------------------------
    */
    $avatarName = null;

    if (
        isset($_FILES['avatar']) &&
        $_FILES['avatar']['error'] == 0
    ) {

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        $ext = strtolower(
            pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION)
        );

        if (in_array($ext, $allowed)) {

            $avatarName =
                time() .
                '_' .
                uniqid() .
                '.' .
                $ext;

            $destination =
                '../../uploads/photos/' .
                $avatarName;

            move_uploaded_file(
                $_FILES['avatar']['tmp_name'],
                $destination
            );
        }
    }

    /*
    --------------------------------------
    UPDATE
    --------------------------------------
    */

    if ($avatarName) {

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                fullname = ?,
                email = ?,
                phone = ?,
                avatar = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $email,
            $phone,
            $avatarName,
            $user_id
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                fullname = ?,
                email = ?,
                phone = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $email,
            $phone,
            $user_id
        ]);
    }

    $_SESSION['success'] = "Profil mis à jour avec succès.";

    header("Location: index.php");
    exit;
}

/*
==================================================
CHANGER MOT DE PASSE
==================================================
*/
if (isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        empty($current_password) ||
        empty($new_password) ||
        empty($confirm_password)
    ) {

        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: index.php");
        exit;
    }

    if ($new_password !== $confirm_password) {

        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        header("Location: index.php");
        exit;
    }

    if (strlen($new_password) < 6) {

        $_SESSION['error'] = "Le mot de passe doit contenir au moins 6 caractères.";
        header("Location: index.php");
        exit;
    }

    /*
    --------------------------------------
    USER
    --------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT password
        FROM users
        WHERE id = ?
    ");

    $stmt->execute([$user_id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $_SESSION['error'] = "Utilisateur introuvable.";
        header("Location: index.php");
        exit;
    }

    /*
    --------------------------------------
    VERIFICATION MOT DE PASSE ACTUEL
    --------------------------------------
    */
    if (!password_verify($current_password, $user['password'])) {

        $_SESSION['error'] = "Mot de passe actuel incorrect.";
        header("Location: index.php");
        exit;
    }

    /*
    --------------------------------------
    UPDATE PASSWORD
    --------------------------------------
    */
    $newHash = password_hash(
        $new_password,
        PASSWORD_DEFAULT
    );

    $stmt = $pdo->prepare("
        UPDATE users
        SET password = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $newHash,
        $user_id
    ]);

    $_SESSION['success'] = "Mot de passe modifié avec succès.";

    header("Location: index.php");
    exit;
}

/*
==================================================
FALLBACK
==================================================
*/
$_SESSION['error'] = "Action non autorisée.";
header("Location: index.php");
exit;