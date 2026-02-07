<?php
if (isset($_POST['PHPSESSID'])) session_id($_POST['PHPSESSID']);
elseif (isset($_GET['PHPSESSID'])) session_id($_GET['PHPSESSID']);
elseif (isset($_COOKIE['PHPSESSID'])) session_id($_COOKIE['PHPSESSID']);

// Toujours fermer et rouvrir pour lire les dernières données écrites par fusionner.php
session_start();
session_write_close();  // Fermer immédiatement
session_start();        // Rouvrir pour lire les données fraîches du disque

// Lire toutes les données de session
$progress = isset($_SESSION['progress']) ? (int)$_SESSION['progress'] : 0;
$progressMessage = isset($_SESSION['progress_message']) ? $_SESSION['progress_message'] : '';

// Lire le redirect depuis le fichier temporaire au lieu de la session
$redirectFile = __DIR__ . '/temp/redirect_' . session_id() . '.txt';
$redirect = null;
if (file_exists($redirectFile)) {
    $redirect = trim(file_get_contents($redirectFile));
}

header('Content-Type: application/json');

// Debug log avec session ID
file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] getProgress - Session ID: ".session_id()."\n", FILE_APPEND);
file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] getProgress: progress=$progress, redirect=$redirect\n", FILE_APPEND);

// Retourner les données
$response = [
    "progress" => $progress,
    "progress_message" => $progressMessage,
    "redirect" => $redirect
];

file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] getProgress response: ".json_encode($response)."\n", FILE_APPEND);

echo json_encode($response);

// Nettoyer le fichier redirect uniquement après envoi
if ($progress >= 100 && $redirect !== null) {
    if (file_exists($redirectFile)) {
        unlink($redirectFile);
    }
}

exit;
?>
