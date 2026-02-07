<?php
/**
 * Configuration centralisée de l'application ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

// Configuration des uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['xls', 'xlsx']);
define('ALLOWED_MIME_TYPES', [
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]);

// Configuration de l'application
define('APP_NAME', 'ERICKRAPPORT');
define('APP_VERSION', '2.0.0');
define('APP_AUTHOR', 'SOMBINIAINA Erick');
define('DEBUG', false); // Mode debug (true en développement, false en production)

// Configuration des chemins
define('TEMP_DIR', sys_get_temp_dir());
define('LOG_DIR', __DIR__ . '/logs/');

// Configuration de sécurité
define('SESSION_TIMEOUT', 3600); // 1 heure
define('MAX_UPLOAD_FILES', 10);

// Messages d'erreur
define('ERROR_MESSAGES', [
    'INVALID_FILE_TYPE' => '❌ Type de fichier non autorisé. Seuls les fichiers Excel (.xls, .xlsx) sont acceptés.',
    'FILE_TOO_LARGE' => '❌ Fichier trop volumineux. Taille maximale autorisée : ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.',
    'UPLOAD_FAILED' => '❌ Échec du téléchargement du fichier.',
    'NO_FILES_TO_MERGE' => '❌ Aucun fichier à fusionner.',
    'MERGE_FAILED' => '❌ Erreur lors de la fusion des fichiers.',
    'INVALID_MIME_TYPE' => '❌ Type MIME non autorisé.',
    'SECURITY_VIOLATION' => '❌ Violation de sécurité détectée.'
]);

// Messages de succès
define('SUCCESS_MESSAGES', [
    'FILE_UPLOADED' => '✅ Fichier téléchargé avec succès.',
    'MERGE_COMPLETED' => '✅ Fusion des fichiers terminée.',
    'PROCESSING_COMPLETED' => '✅ Traitement terminé avec succès.'
]);

// Créer les dossiers nécessaires s'ils n'existent pas
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

/**
 * Fonction de sanitisation des entrées utilisateur
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
