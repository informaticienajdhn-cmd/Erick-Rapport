<?php
require_once 'classes/Database.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID du canevas manquant');
    }
    
    $id = intval($_GET['id']);
    
    // Récupérer le canevas
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT nom_fichier, fichier FROM canevas_suivi WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $canevas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$canevas) {
        throw new Exception('Canevas non trouvé');
    }
    
    // Télécharger le fichier
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $canevas['nom_fichier'] . '"');
    header('Content-Length: ' . strlen($canevas['fichier']));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $canevas['fichier'];
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
