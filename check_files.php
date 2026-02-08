<?php
/**
 * Vérifie si des fichiers sont présents dans le dossier d'upload
 * Retourne un JSON avec l'information
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    // Vérifier si le dossier upload existe
    if (!is_dir(UPLOAD_DIR)) {
        echo json_encode(['hasFiles' => false, 'count' => 0]);
        exit;
    }

    // Scanner le dossier upload
    $files = array_diff(scandir(UPLOAD_DIR), ['.', '..']);
    
    // Filtrer pour les fichiers Excel uniquement
    $excelFiles = array_filter($files, function($file) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($extension, ALLOWED_EXTENSIONS);
    });

    // Retourner le résultat
    echo json_encode([
        'hasFiles' => count($excelFiles) > 0,
        'count' => count($excelFiles),
        'files' => array_values($excelFiles)
    ]);

} catch (Exception $e) {
    // En cas d'erreur, retourner false
    echo json_encode([
        'hasFiles' => false,
        'count' => 0,
        'error' => $e->getMessage()
    ]);
}

exit;
?>
