<?php
require_once 'classes/Database.php';

header('Content-Type: application/octet-stream');

try {
    if (empty($_GET['id'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'ID requis'
        ]);
        return;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT nom_fichier, fichier FROM conclusions_suivi WHERE id = :id");
    $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $conclusion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conclusion) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Conclusion non trouvée'
        ]);
        return;
    }
    
    // Envoyer le fichier
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $conclusion['nom_fichier'] . '"');
    header('Content-Length: ' . strlen($conclusion['fichier']));
    
    echo $conclusion['fichier'];
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
