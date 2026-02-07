<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Récupérer toutes les pages avec les noms des terroirs
    $stmt = $db->prepare("SELECT p.id, p.terroir_id, p.nom_fichier, p.created_at, t.nom as terroir_nom FROM pages_de_garde_fusion p LEFT JOIN terroirs t ON p.terroir_id = t.id ORDER BY p.created_at DESC");
    $stmt->execute();
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'pages' => $pages, 'count' => count($pages)]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
