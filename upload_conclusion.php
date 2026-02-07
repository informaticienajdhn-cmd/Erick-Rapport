<?php
// Désactiver l'affichage des erreurs pour ne pas polluer le JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
require_once 'classes/Database.php';

$debug_log = [];

try {
    $debug_log[] = "Début du traitement";
    $debug_log[] = "POST keys: " . implode(', ', array_keys($_POST));
    $debug_log[] = "FILE keys: " . implode(', ', array_keys($_FILES));
    
    // Valider les entrées
    if (empty($_POST['activite_id']) || empty($_POST['commune_id'])) {
        throw new Exception('Activité et commune requises');
    }
    
    if (empty($_FILES['conclusion_file']) || $_FILES['conclusion_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fichier conclusion non valide');
    }
    
    $file = $_FILES['conclusion_file'];
    $debug_log[] = "Fichier: " . $file['name'];
    $debug_log[] = "Taille: " . $file['size'];
    
    // Valider l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['xls', 'xlsx'])) {
        throw new Exception('Seuls les fichiers Excel sont autorisés (.xls, .xlsx)');
    }
    
    // Lire le contenu du fichier
    $fichier_content = file_get_contents($file['tmp_name']);
    if ($fichier_content === false) {
        throw new Exception('Impossible de lire le fichier');
    }
    
    $debug_log[] = "Contenu lu: " . strlen($fichier_content) . " bytes";
    
    // Connexion à la base de données
    $db = Database::getInstance()->getConnection();
    $debug_log[] = "Base de données connectée";
    
    // Vérifier si la table existe
    $table_check = $db->query("
        SELECT name FROM sqlite_master 
        WHERE type='table' AND name='conclusions_suivi'
    ");
    
    if (!$table_check->fetch()) {
        $debug_log[] = "Table conclusions_suivi inexistante, création...";
        $db->exec("
            CREATE TABLE IF NOT EXISTS conclusions_suivi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                activite_id INTEGER NOT NULL,
                commune_id INTEGER NOT NULL,
                nom_fichier TEXT NOT NULL,
                fichier BLOB NOT NULL,
                version INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(activite_id) REFERENCES activites(id),
                FOREIGN KEY(commune_id) REFERENCES communes(id)
            )
        ");
        $debug_log[] = "Table créée";
    }
    
    // Trouver la version suivante pour cette combinaison activité-commune
    $stmt = $db->prepare("
        SELECT MAX(version) as max_version FROM conclusions_suivi 
        WHERE activite_id = :activite_id AND commune_id = :commune_id
    ");
    $stmt->execute([
        ':activite_id' => $_POST['activite_id'],
        ':commune_id' => $_POST['commune_id']
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextVersion = ($result['max_version'] ?? 0) + 1;
    
    // Insérer la nouvelle conclusion avec le numéro de version
    $debug_log[] = "Insertion d'une nouvelle conclusion (version $nextVersion)";
    $stmt = $db->prepare("
        INSERT INTO conclusions_suivi (activite_id, commune_id, nom_fichier, fichier, version)
        VALUES (:activite_id, :commune_id, :nom_fichier, :fichier, :version)
    ");
    $stmt->bindParam(':activite_id', $_POST['activite_id'], PDO::PARAM_INT);
    $stmt->bindParam(':commune_id', $_POST['commune_id'], PDO::PARAM_INT);
    $stmt->bindParam(':nom_fichier', $file['name'], PDO::PARAM_STR);
    $stmt->bindParam(':fichier', $fichier_content, PDO::PARAM_LOB);
    $stmt->bindParam(':version', $nextVersion, PDO::PARAM_INT);
    $stmt->execute();
    $message = 'Conclusion enregistrée avec succès';
    
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
?>
