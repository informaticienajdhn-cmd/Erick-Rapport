<?php
require_once 'classes/Database.php';

echo "<h2>🔧 Création Table Canevas</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Créer la table canevas_suivi
    $db->exec("
        CREATE TABLE IF NOT EXISTS canevas_suivi (
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
    
    echo "<p style='color: green; font-size: 16px;'><strong>✅ Table canevas_suivi créée avec succès!</strong></p>";
    
    // Vérifier
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='canevas_suivi'")->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<p style='color: green;'>✅ Vérification: La table existe maintenant</p>";
        
        // Afficher la structure
        echo "<h3>Structure de la table:</h3>";
        $columns = $db->query("PRAGMA table_info(canevas_suivi)")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Colonne</th><th>Type</th><th>Not Null</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['name'] . "</td>";
            echo "<td>" . $col['type'] . "</td>";
            echo "<td>" . ($col['notnull'] ? 'Oui' : 'Non') . "</td>";
            echo "<td>" . ($col['dflt_value'] ? $col['dflt_value'] : '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><br><p style='font-size: 14px; color: #666;'>Vous pouvez maintenant retourner à <strong>Paramètres → Canevas Excel</strong> et enregistrer vos fichiers.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ ERREUR:</strong> " . $e->getMessage() . "</p>";
}
?>
