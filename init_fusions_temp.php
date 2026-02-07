<?php
/**
 * Création de la table pour les fusions temporaires
 */

require_once 'config.php';
require_once 'classes/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Créer la table fusions_temporaires
    $sql = "CREATE TABLE IF NOT EXISTS fusions_temporaires (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id TEXT NOT NULL,
        ordre INTEGER NOT NULL,
        fichier BLOB NOT NULL,
        nom_fichier TEXT NOT NULL,
        params TEXT,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    
    echo "✓ Table 'fusions_temporaires' créée avec succès!\n";
    
    // Créer un index sur session_id pour optimiser les requêtes
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_session_id ON fusions_temporaires(session_id)");
    echo "✓ Index créé sur session_id\n";
    
} catch (Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}
?>
