<?php
/**
 * API pour récupérer les données des paramètres
 */

require_once 'config.php';
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    $type = $_GET['type'] ?? '';

    $data = [];
    switch ($type) {
        case 'terroirs':
            $data = $db->getAll('terroirs');
            break;
        case 'communes':
            $data = $db->getAll('communes');
            break;
        case 'regions':
            $data = $db->getAll('regions');
            break;
        case 'districts':
            $data = $db->getAll('districts');
            break;
        case 'titres':
            $data = $db->getAll('titres_transfert');
            break;
        case 'all':
            $data = [
                'terroirs' => $db->getAll('terroirs'),
                'communes' => $db->getAll('communes'),
                'regions' => $db->getAll('regions'),
                'districts' => $db->getAll('districts'),
                'titres' => $db->getAll('titres_transfert')
            ];
            break;
        default:
            throw new Exception('Type non reconnu');
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    error_log("Erreur API Paramètres: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
