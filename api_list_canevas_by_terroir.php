<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    if (empty($_GET['terroir_id'])) {
        throw new Exception('terroir_id requis');
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, terroir_id, nom_fichier, created_at FROM pages_de_garde_fusion WHERE terroir_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_GET['terroir_id']]);
    $canevas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'canevas' => $canevas, 'count' => count($canevas)]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
