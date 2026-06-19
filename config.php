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

/**
 * Incrémente le compteur de fusions réalisées
 */
function incrementFusionCount() {
    try {
        require_once __DIR__ . '/classes/Database.php';
        $db = Database::getInstance()->getConnection();
        $db->exec("
            CREATE TABLE IF NOT EXISTS app_stats (
                key TEXT PRIMARY KEY,
                value INTEGER NOT NULL DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $db->exec("
            INSERT INTO app_stats (key, value, updated_at)
            VALUES ('total_fusions', 1, CURRENT_TIMESTAMP)
            ON CONFLICT(key) DO UPDATE SET
                value = value + 1,
                updated_at = CURRENT_TIMESTAMP
        ");
    } catch (Exception $e) {
        error_log('incrementFusionCount: ' . $e->getMessage());
    }
}

/**
 * Statistiques affichées sur la page d'accueil
 */
function getHomeStats() {
    $stats = [
        'uploadedFiles' => 0,
        'totalFusions' => 0,
        'savedReports' => 0,
        'version' => '2.1',
        'lastActivity' => 'Aucune activité récente'
    ];

    $files = [];
    if (is_dir(UPLOAD_DIR)) {
        $files = glob(UPLOAD_DIR . '*.{xls,xlsx}', GLOB_BRACE) ?: [];
        $stats['uploadedFiles'] = count($files);

        if (!empty($files)) {
            $latestUpload = max(array_map('filemtime', $files));
            $stats['lastActivity'] = 'Dernière activité: ' . date('d/m/Y H:i', $latestUpload);
        }
    }

    try {
        require_once __DIR__ . '/classes/Database.php';
        $db = Database::getInstance()->getConnection();

        $db->exec("
            CREATE TABLE IF NOT EXISTS app_stats (
                key TEXT PRIMARY KEY,
                value INTEGER NOT NULL DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $stmt = $db->query("SELECT value FROM app_stats WHERE key = 'total_fusions'");
        $fusionCount = (int) $stmt->fetchColumn();

        $tableExists = $db->query("
            SELECT name FROM sqlite_master
            WHERE type = 'table' AND name = 'rapports_enregistres'
        ")->fetchColumn();

        if ($tableExists) {
            $stats['savedReports'] = (int) $db->query("SELECT COUNT(*) FROM rapports_enregistres")->fetchColumn();

            if ($fusionCount === 0 && $stats['savedReports'] > 0) {
                $db->prepare("
                    INSERT INTO app_stats (key, value, updated_at)
                    VALUES ('total_fusions', :value, CURRENT_TIMESTAMP)
                    ON CONFLICT(key) DO UPDATE SET
                        value = excluded.value,
                        updated_at = CURRENT_TIMESTAMP
                ")->execute(['value' => $stats['savedReports']]);
                $fusionCount = $stats['savedReports'];
            }

            $stats['totalFusions'] = $fusionCount;

            $latestReport = $db->query("
                SELECT created_at FROM rapports_enregistres
                ORDER BY datetime(created_at) DESC
                LIMIT 1
            ")->fetchColumn();

            if ($latestReport) {
                $reportTime = strtotime($latestReport);
                $uploadTime = !empty($files) ? max(array_map('filemtime', $files)) : 0;
                $fusionTime = strtotime($db->query("
                    SELECT updated_at FROM app_stats WHERE key = 'total_fusions'
                ")->fetchColumn() ?: '');

                $latest = max(array_filter([$reportTime, $uploadTime, $fusionTime ?: 0]));
                if ($latest > 0) {
                    $stats['lastActivity'] = 'Dernière activité: ' . date('d/m/Y H:i', $latest);
                }
            }
        }
    } catch (Exception $e) {
        error_log('getHomeStats: ' . $e->getMessage());
    }

    return $stats;
}
?>
