<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT c.id, c.terroir_id, c.nom_fichier, c.created_at, t.nom as terroir_nom FROM conclusions_fusion c LEFT JOIN terroirs t ON c.terroir_id = t.id ORDER BY c.created_at DESC");
    $stmt->execute();
    $conclusions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'conclusions' => $conclusions, 'count' => count($conclusions)]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
