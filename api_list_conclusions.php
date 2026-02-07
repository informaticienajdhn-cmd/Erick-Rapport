<?php
require_once 'classes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    $commune_id = $_GET['commune_id'] ?? null;
    $activite_id = $_GET['activite_id'] ?? null;
    
    // Filtrage par commune et/ou activité
    if ($commune_id || $activite_id) {
        $conditions = [];
        $params = [];

        if ($commune_id) {
            $conditions[] = 'c.commune_id = ?';
            $params[] = $commune_id;
        }
        if ($activite_id) {
            $conditions[] = 'c.activite_id = ?';
            $params[] = $activite_id;
        }

        $whereClause = implode(' AND ', $conditions);

        $stmt = $db->prepare("
            SELECT 
                c.id,
                c.activite_id,
                c.commune_id,
                c.nom_fichier,
                c.created_at,
                a.nom as activite_nom,
                co.nom as commune_nom
            FROM conclusions_suivi c
            LEFT JOIN activites a ON c.activite_id = a.id
            LEFT JOIN communes co ON c.commune_id = co.id
            WHERE $whereClause
            ORDER BY c.created_at DESC
        ");
        $stmt->execute($params);
    } else {
        // Récupérer toutes les conclusions
        $stmt = $db->query("
            SELECT 
                c.id,
                c.activite_id,
                c.commune_id,
                c.nom_fichier,
                c.created_at,
                a.nom as activite_nom,
                co.nom as commune_nom
            FROM conclusions_suivi c
            LEFT JOIN activites a ON c.activite_id = a.id
            LEFT JOIN communes co ON c.commune_id = co.id
            ORDER BY c.created_at DESC
        ");
    }
    
    $conclusions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'conclusions' => $conclusions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

