<?php
/**
 * Gestionnaire de fusion des fichiers Excel
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

// Chargement de la configuration et des classes
require_once 'config.php';
require_once 'classes/ErrorHandler.php';
require_once 'classes/ExcelProcessor.php';
require_once 'classes/Database.php';

// Initialisation du gestionnaire d'erreurs
ErrorHandler::init();

// Démarrage de la session sécurisée
session_start();

// LOG AVANT TRY POUR CAPTURER LES ERREURS DE PARSING
error_log("[fusionner.php] AVANT TRY - " . date('Y-m-d H:i:s'));
file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [AVANT-TRY] Fichier chargé\n", FILE_APPEND);

try {
    ErrorHandler::logError('DEBUG: Lancement de la fusion');
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Début fusionner.php\n", FILE_APPEND);
    
    // Vérification de la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode HTTP non autorisée.');
    }
    
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] POST reçu, extraction des IDs\n", FILE_APPEND);

    // Initialisation du processeur Excel (la progression est gérée en interne)
    $processor = new ExcelProcessor();
    
    // Récupération des paramètres du formulaire
    $terroir_id = isset($_POST['terroir']) ? sanitize_input($_POST['terroir']) : '';
    $commune_id = isset($_POST['commune']) ? sanitize_input($_POST['commune']) : '';
    $region_id = isset($_POST['region']) ? sanitize_input($_POST['region']) : '';
    $district_id = isset($_POST['district']) ? sanitize_input($_POST['district']) : '';
    
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] IDs: terroir=$terroir_id, commune=$commune_id, region=$region_id, district=$district_id\n", FILE_APPEND);
    
    // Convertir les IDs en noms pour l'affichage dans le rapport
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Début conversion ID->NOM\n", FILE_APPEND);
    $db = Database::getInstance()->getConnection();
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Database connection OK\n", FILE_APPEND);
    
    $terroir_nom = '';
    if ($terroir_id) {
        $stmt = $db->prepare("SELECT nom FROM terroirs WHERE id = ?");
        $stmt->execute([$terroir_id]);
        $terroir_nom = $stmt->fetchColumn() ?: $terroir_id;
    }
    
    $commune_nom = '';
    if ($commune_id) {
        $stmt = $db->prepare("SELECT nom FROM communes WHERE id = ?");
        $stmt->execute([$commune_id]);
        $commune_nom = $stmt->fetchColumn() ?: $commune_id;
    }
    
    $region_nom = '';
    if ($region_id) {
        $stmt = $db->prepare("SELECT nom FROM regions WHERE id = ?");
        $stmt->execute([$region_id]);
        $region_nom = $stmt->fetchColumn() ?: $region_id;
    }
    
    $district_nom = '';
    if ($district_id) {
        $stmt = $db->prepare("SELECT nom FROM districts WHERE id = ?");
        $stmt->execute([$district_id]);
        $district_nom = $stmt->fetchColumn() ?: $district_id;
    }
    
    // Convertir le titre du transfert en nom (si reçu comme ID, sinon le titre est déjà le nom)
    $transfert_title = isset($_POST['transfert_title']) ? sanitize_input($_POST['transfert_title']) : 'TRANSFERT MONETAIRE FSP';
    
    // Vérifier si c'est un ID numérique ou un titre texte
    if (is_numeric($transfert_title)) {
        // C'est un ID, le convertir en nom
        $stmt = $db->prepare("SELECT nom FROM titres_transfert WHERE id = ?");
        $stmt->execute([$transfert_title]);
        $transfert_title = $stmt->fetchColumn() ?: 'TRANSFERT MONETAIRE FSP';
    }
    // Sinon c'est déjà le titre (texte), on le garde tel quel
    
    $reportParams = [
        'terroir' => $terroir_nom,
        'commune' => $commune_nom,
        'transfert_title' => $transfert_title,
        'region' => $region_nom,
        'district' => $district_nom,
        'canevas_id' => isset($_POST['canevas_id']) ? sanitize_input($_POST['canevas_id']) : null,
        'conclusion_id' => isset($_POST['conclusion_id']) ? sanitize_input($_POST['conclusion_id']) : null,
    ];
    
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Avant mergeExcelFiles\n", FILE_APPEND);
    $result = $processor->mergeExcelFiles($reportParams);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Après mergeExcelFiles\n", FILE_APPEND);
    
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Result: " . json_encode($result) . "\n", FILE_APPEND);

    // Stocker les données dans des fichiers temporaires car getProgress.php écrase la session
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Récupération session_id\n", FILE_APPEND);
    $sessionId = session_id();
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Session ID: $sessionId\n", FILE_APPEND);
    
    $fusionDataFile = __DIR__ . '/temp/fusion_data_' . $sessionId . '.json';
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Préparation fusion_data: $fusionDataFile\n", FILE_APPEND);
    
    $fusionData = [
        'file_path' => $result['file_path'],
        'file_name' => $result['file_name'],
        'params' => $reportParams
    ];
    
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Fusion data préparée\n", FILE_APPEND);
    $json_content = json_encode($fusionData);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] JSON: " . substr($json_content, 0, 100) . "...\n", FILE_APPEND);
    
    file_put_contents($fusionDataFile, $json_content);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Fichier fusion_data écrit\n", FILE_APPEND);
    
    $_SESSION['progress'] = 100; // S'assurer que la progression est à 100%
    $_SESSION['progress_message'] = 'Fusion terminée. Redirection...';
    
    // Utiliser un fichier pour le redirect
    $redirectFile = __DIR__ . '/temp/redirect_' . $sessionId . '.txt';
    file_put_contents($redirectFile, 'acceuil_choix_fusion.php');
    
    // Debug: Log session ID et variables
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Session ID dans fusionner.php: ".session_id()."\n", FILE_APPEND);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] redirect_to défini via fichier: acceuil_choix_fusion.php\n", FILE_APPEND);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] progress défini: ".$_SESSION['progress']."\n", FILE_APPEND);
    
    // IMPORTANT: Forcer l'écriture de la session pour que getProgress.php puisse la lire
    session_write_close();
    
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Après session file et session_write_close\n", FILE_APPEND);

    // Réponse JSON pour le front-end JS - Rediriger vers la page de renommage
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Avant echo JSON\n", FILE_APPEND);
    echo json_encode([
        'success' => true,
        'redirect' => 'acceuil_choix_fusion.php',
        'processed_files' => $result['processed_files'],
        'total_files' => $result['total_files'],
        'message' => 'Fusion terminée. Choisissez votre action.'
    ]);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Après echo JSON\n", FILE_APPEND);
    // Log de succès
    ErrorHandler::logError("Fusion terminée avec succès. Fichiers traités: {$result['processed_files']}/{$result['total_files']}");
    exit;

} catch (Exception $e) {
    // Écrire l'erreur dans les logs
    error_log("[fusionner.php CATCH] Exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [CATCH] Exception: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n", FILE_APPEND);
    
    // Réinitialisation de la progression en cas d'erreur
    $_SESSION['progress'] = 0;
    // Log détaillé de l'erreur
    ErrorHandler::logError("ERREUR FUSION: " . $e->getMessage());
    // Retour d'une erreur JSON détaillée
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

exit;
?>