<?php
session_start();

require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

/*
====================================
SECURITY CHECK EVENT OWNER
====================================
*/
$stmt = $pdo->prepare("SELECT * FROM events WHERE generat = ? LIMIT 1");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Événement introuvable ou accès refusé.");
}

/*
====================================
FONCTION DE COMPRESSION ET REDIMENSIONNEMENT
====================================
*/
function compressAndResizeImage($sourcePath, $destinationPath, $maxDimension = 1920, $quality = 75) {
    // Récupérer les informations de l'image d'origine
    list($width, $height, $type) = getimagesize($sourcePath);
    
    if (!$width || !$height) return false;

    // Calculer les proportions de redimensionnement si l'image est immense
    $newWidth = $width;
    $newHeight = $height;

    if ($width > $maxDimension || $height > $maxDimension) {
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = floor($height * ($maxDimension / $width));
        } else {
            $newHeight = $maxDimension;
            $newWidth = floor($width * ($maxDimension / $height));
        }
    }

    // Créer une ressource image PHP selon le type mime d'origine
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false; // Type non pris en charge pour la conversion
    }

    if (!$sourceImage) return false;

    // Créer la nouvelle image de destination vierge
    $destinationImage = imagecreatetruecolor($newWidth, $newHeight);

    // Préserver la transparence si c'est un PNG ou WEBP
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($destinationImage, false);
        imagesavealpha($destinationImage, true);
    }

    // Effectuer le redimensionnement fluide
    imagecopyresampled($destinationImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Sauvegarder au format JPEG compressé pour économiser un maximum d'espace disque
    // (Tu peux forcer en .jpg pour homogénéiser ta galerie)
    $success = imagejpeg($destinationImage, $destinationPath, $quality);

    // Libérer la mémoire RAM occupée par PHP
    imagedestroy($sourceImage);
    imagedestroy($destinationImage);

    return $success;
}

/*
====================================
DELETE IMAGE
====================================
*/
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("
        SELECT g.*
        FROM gallery g
        JOIN events e ON e.generat = g.generat_event
        WHERE g.id = ? AND e.user_id = ?
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $img = $stmt->fetch();

    if (!$img) {
        $_SESSION['error'] = "Image introuvable ou accès refusé";
        header("Location: ../gallery/?event_id=".$event_id);
        exit;
    }

    $filePath = "../../uploads/gallery/" . $img['photo'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "✔ Image supprimée avec succès";
    header("Location: ../gallery/?event_id=".$event_id);
    exit;
}

/*
====================================
UPLOAD MULTIPLE IMAGES WITH COMPRESSION
====================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
        $_SESSION['error'] = "Veuillez sélectionner au moins un fichier.";
        header("Location: ../gallery/?event_id=".$event_id);
        exit;
    }

    $files = $_FILES['photos'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $dir = "../../uploads/gallery/";

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $success_count = 0;
    $errors = [];

    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] != 0) {
            $errors[] = "Fichier ignoré ou trop lourd pour le serveur : " . htmlspecialchars($name);
            continue;
        }

        $tmp_name = $files['tmp_name'][$key];
        if (function_exists('mime_content_type')) {

    $mime = mime_content_type($tmp_name);

        } elseif (function_exists('finfo_open')) {

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);

        } else {

    // Dernier recours : extension du fichier

    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $mimeTypes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',
    ];

    $mime = $mimeTypes[$extension] ?? '';

}

        if (!in_array($mime, $allowed)) {
            $errors[] = "Format non autorisé pour : " . htmlspecialchars($name);
            continue;
        }

        // On sauvegarde toutes les images compressées en extension .jpg pour maximiser les performances
        $filename = uniqid("gallery_") . "_" . $key . ".jpg";
        $destinationPath = $dir . $filename;

        // Appel de notre fonction de compression à la volée
        if (compressAndResizeImage($tmp_name, $destinationPath, 1920, 75)) {
            $stmt = $pdo->prepare("
                INSERT INTO gallery(generat_event, photo)
                VALUES(?, ?)
            ");
            $stmt->execute([$event_id, $filename]);
            $success_count++;
        } else {
            $errors[] = "Échec de la compression du fichier : " . htmlspecialchars($name);
        }
    }

    if ($success_count > 0) {
        $_SESSION['success'] = "✔ $success_count image(s) téléversée(s) et optimisée(s) avec succès.";
    }
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }

    header("Location: ../gallery/?event_id=".$event_id);
    exit;
}

header("Location: ../gallery/?event_id=".$event_id);
exit;