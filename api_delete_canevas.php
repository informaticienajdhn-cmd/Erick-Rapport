<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        throw new Exception('ID manquant');
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("DELETE FROM canevas_suivi WHERE id = :id");
    $stmt->execute([':id' => intval($input['id'])]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Canevas supprimé avec succès'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
