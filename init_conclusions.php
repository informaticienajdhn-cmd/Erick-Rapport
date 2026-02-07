<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Créer la table conclusions_suivi si elle n'existe pas
    $db->exec("
        CREATE TABLE IF NOT EXISTS conclusions_suivi (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            activite_id INTEGER NOT NULL,
            commune_id INTEGER NOT NULL,
            nom_fichier TEXT NOT NULL,
            fichier BLOB NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(activite_id, commune_id),
            FOREIGN KEY(activite_id) REFERENCES activites(id),
            FOREIGN KEY(commune_id) REFERENCES communes(id)
        )
    ");
    
    echo json_encode([
        'success' => true,
        'message' => 'Table conclusions_suivi créée avec succès'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
