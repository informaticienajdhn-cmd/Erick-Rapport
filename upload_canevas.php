<?php
// Désactiver l'affichage des erreurs pour ne pas polluer le JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'classes/Database.php';

$debug_log = [];

try {
    $debug_log[] = "POST: " . json_encode(array_keys($_POST));
    $debug_log[] = "FILES: " . json_encode(array_keys($_FILES));
    
    // Vérifier qu'un fichier a été uploadé
    if (!isset($_FILES['canevas_file']) || $_FILES['canevas_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucun fichier uploadé ou erreur lors de l\'upload: ' . (isset($_FILES['canevas_file']['error']) ? $_FILES['canevas_file']['error'] : 'fichier manquant'));
    }
    
    // Vérifier les paramètres
    if (empty($_POST['activite_id']) || empty($_POST['commune_id'])) {
        throw new Exception('Activité et commune obligatoires');
    }
    
    $activite_id = intval($_POST['activite_id']);
    $commune_id = intval($_POST['commune_id']);
    $file = $_FILES['canevas_file'];
    
    $debug_log[] = "Activité ID: $activite_id, Commune ID: $commune_id, Fichier: " . $file['name'];
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['xls', 'xlsx'])) {
        throw new Exception('Seuls les fichiers Excel (.xls, .xlsx) sont acceptés. Extension: ' . $extension);
    }
    
    // Lire le contenu du fichier
    $fichier_content = file_get_contents($file['tmp_name']);
    if ($fichier_content === false) {
        throw new Exception('Impossible de lire le fichier uploadé');
    }
    
    $debug_log[] = "Taille du fichier: " . strlen($fichier_content) . " bytes";
    
    // Connexion à la base de données
    $db = Database::getInstance()->getConnection();
    
    // Créer la table si elle n'existe pas
    $db->exec("
        CREATE TABLE IF NOT EXISTS canevas_suivi (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            activite_id INTEGER NOT NULL,
            commune_id INTEGER NOT NULL,
            nom_fichier TEXT NOT NULL,
            fichier BLOB NOT NULL,
            version INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $debug_log[] = "Table créée/vérifiée";
    
    // Trouver la version suivante pour cette combinaison activité-commune
    $stmt = $db->prepare("
        SELECT MAX(version) as max_version FROM canevas_suivi 
        WHERE activite_id = :activite_id AND commune_id = :commune_id
    ");
    $stmt->execute([
        ':activite_id' => $activite_id,
        ':commune_id' => $commune_id
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextVersion = ($result['max_version'] ?? 0) + 1;
    
    $debug_log[] = "Insertion d'un nouveau canevas (version $nextVersion)";
    
    // Insérer le nouveau canevas avec le numéro de version
    $stmt = $db->prepare("
        INSERT INTO canevas_suivi (activite_id, commune_id, nom_fichier, fichier, version)
        VALUES (:activite_id, :commune_id, :nom_fichier, :fichier, :version)
    ");
    $stmt->bindParam(':activite_id', $activite_id, PDO::PARAM_INT);
    $stmt->bindParam(':commune_id', $commune_id, PDO::PARAM_INT);
    $stmt->bindParam(':nom_fichier', $file['name'], PDO::PARAM_STR);
    $stmt->bindParam(':fichier', $fichier_content, PDO::PARAM_LOB);
    $stmt->bindParam(':version', $nextVersion, PDO::PARAM_INT);
    $stmt->bindParam(':fichier', $fichier_content, PDO::PARAM_LOB);
    $stmt->execute();
    
    $message = 'Canevas enregistré avec succès';
    
    $debug_log[] = "Succès";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'debug' => $debug_log
    ]);
    
} catch (Exception $e) {
    $debug_log[] = "ERREUR: " . $e->getMessage();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => $debug_log
    ]);
}
