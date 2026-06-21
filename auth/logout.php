<?php
session_start();

// On vide toutes les variables de session
$_SESSION = array();

// On détruit la session
session_destroy();

// On redirige vers la page de connexion
header('Location: login.php');
exit;