<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $terroir_id = $_POST['terroir_id'] ?? null;
    $fichier = $_FILES['fichier'] ?? null;
    
    if (!$terroir_id) {
        throw new Exception('terroir_id requis');
    }
    
    if (!$fichier || $fichier['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fichier invalide');
    }
    
    $ext = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xls', 'xlsx'])) {
        throw new Exception('Format invalide. Accepté: XLS, XLSX');
    }
    
    $contenu = file_get_contents($fichier['tmp_name']);
    
    $db = Database::getInstance()->getConnection();
    
    $db->exec("CREATE TABLE IF NOT EXISTS conclusions_fusion (id INTEGER PRIMARY KEY AUTOINCREMENT, terroir_id INTEGER NOT NULL, nom_fichier TEXT NOT NULL, fichier BLOB NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(terroir_id), FOREIGN KEY(terroir_id) REFERENCES terroirs(id))");
    
    $stmt = $db->prepare("INSERT OR REPLACE INTO conclusions_fusion (terroir_id, nom_fichier, fichier) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $terroir_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $fichier['name'], PDO::PARAM_STR);
    $stmt->bindValue(3, $contenu, PDO::PARAM_LOB);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Conclusion enregistrée']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
