<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Récupérer les terroirs
    $stmt = $db->query("SELECT id, nom FROM terroirs ORDER BY nom");
    $terroirs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les communes
    $stmt = $db->query("SELECT id, nom FROM communes ORDER BY nom");
    $communes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les transferts (titres)
    $stmt = $db->query("SELECT id, nom as titre FROM titres_transfert ORDER BY nom");
    $transferts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les régions
    $stmt = $db->query("SELECT id, nom FROM regions ORDER BY nom");
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les districts
    $stmt = $db->query("SELECT id, nom FROM districts ORDER BY nom");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'terroirs' => $terroirs,
        'communes' => $communes,
        'transferts' => $transferts,
        'regions' => $regions,
        'districts' => $districts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
