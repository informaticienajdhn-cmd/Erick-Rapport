<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['id'])) {
        throw new Exception('id requis');
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM conclusions_fusion WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Conclusion supprimée']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
