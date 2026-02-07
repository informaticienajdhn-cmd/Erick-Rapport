<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Créer la table conclusions pour la fusion
    $db->exec("
        CREATE TABLE IF NOT EXISTS conclusions_fusion (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            terroir_id INTEGER NOT NULL,
            nom_fichier TEXT NOT NULL,
            fichier BLOB NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(terroir_id),
            FOREIGN KEY(terroir_id) REFERENCES terroirs(id)
        )
    ");
    
    echo json_encode(['success' => true, 'message' => 'Table conclusions_fusion créée']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
