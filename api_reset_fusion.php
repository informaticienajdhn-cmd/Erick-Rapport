<?php
/**
 * Réinitialiser l'état de la fusion pour démarrer une nouvelle fusion propre
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config.php';

try {
    // Nettoyer les fichiers uploadés
    if (is_dir(UPLOAD_DIR)) {
        $files = array_diff(scandir(UPLOAD_DIR), ['.', '..']);
        foreach ($files as $file) {
            $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    $sessionId = session_id();

    // Nettoyer les fichiers temporaires de redirection/progression
    $redirectFile = __DIR__ . '/temp/redirect_' . $sessionId . '.txt';
    if (file_exists($redirectFile)) {
        @unlink($redirectFile);
    }

    // Réinitialiser la progression en session
    $_SESSION['progress'] = 0;
    $_SESSION['progress_message'] = '';
    session_write_close();

    echo json_encode([
        'success' => true,
        'message' => 'État de fusion réinitialisé'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
