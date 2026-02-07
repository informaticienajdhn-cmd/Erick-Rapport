<?php
/**
 * API pour sauvegarder une fusion dans la table temporaire
 */
session_start();
header('Content-Type: application/json');

require_once 'config.php';
require_once 'classes/Database.php';

try {
    $sessionId = session_id();
    
    // Récupérer les données de fusion
    $fusionDataFile = __DIR__ . '/temp/fusion_data_' . $sessionId . '.json';
    $fusionResultFile = __DIR__ . '/temp/result_' . $sessionId . '.xlsx';
    
    if (!file_exists($fusionDataFile)) {
        throw new Exception('Fichier de fusion introuvable');
    }
    
    $fusionData = json_decode(file_get_contents($fusionDataFile), true);
    $fusionFilePath = $fusionData['file_path'] ?? '';
    
    // Attendre un peu si le fichier n'est pas encore prêt
    $maxAttempts = 10;
    $attempt = 0;
    while ($attempt < $maxAttempts) {
        if ($fusionFilePath && file_exists($fusionFilePath)) {
            break;
        }
        if (file_exists($fusionResultFile)) {
            $fusionFilePath = $fusionResultFile;
            break;
        }
        usleep(200000); // 200ms
        $attempt++;
    }
    
    if (!$fusionFilePath || !file_exists($fusionFilePath)) {
        throw new Exception('Fichiers de fusion introuvables');
    }
    
    $fusionFileContent = file_get_contents($fusionFilePath);
    
    // Récupérer l'ordre suivant
    $db = Database::getInstance();
    $stmt = $db->getConnection()->prepare(
        "SELECT COALESCE(MAX(ordre), 0) + 1 as next_ordre FROM fusions_temporaires WHERE session_id = ?"
    );
    $stmt->execute([$sessionId]);
    $ordre = $stmt->fetchColumn();
    
    // Insérer la fusion temporaire
    $stmt = $db->getConnection()->prepare("
        INSERT INTO fusions_temporaires (session_id, ordre, fichier, nom_fichier, params)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $sessionId,
        $ordre,
        $fusionFileContent,
        $fusionData['file_name'] ?? basename($fusionFilePath),
        json_encode($fusionData['params'] ?? [])
    ]);
    
    // Nettoyer les fichiers temporaires actuels
    if (file_exists($fusionDataFile)) {
        unlink($fusionDataFile);
    }
    if (file_exists($fusionResultFile)) {
        unlink($fusionResultFile);
    }
    
    echo json_encode([
        'success' => true,
        'fusion_number' => $ordre,
        'message' => "Fusion #{$ordre} sauvegardée avec succès"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
