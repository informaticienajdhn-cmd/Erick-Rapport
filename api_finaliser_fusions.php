<?php
/**
 * API pour finaliser toutes les fusions et rediriger vers l'enregistrement
 */
session_start();

require_once 'config.php';
require_once 'classes/Database.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $sessionId = session_id();
    $db = Database::getInstance();
    
    // Récupérer toutes les fusions temporaires
    $stmt = $db->getConnection()->prepare("
        SELECT * FROM fusions_temporaires 
        WHERE session_id = ? 
        ORDER BY ordre ASC
    ");
    $stmt->execute([$sessionId]);
    $fusionsTemp = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter la fusion actuelle si elle existe
    $fusionDataFile = __DIR__ . '/temp/fusion_data_' . $sessionId . '.json';
    $fusionResultFile = __DIR__ . '/temp/result_' . $sessionId . '.xlsx';
    $currentFusionPath = '';
    $fusionData = null;
    if (file_exists($fusionDataFile)) {
        $fusionData = json_decode(file_get_contents($fusionDataFile), true);
        $currentFusionPath = $fusionData['file_path'] ?? '';
    }
    
    $hasFusionsToMerge = count($fusionsTemp) > 0
        || ($currentFusionPath && file_exists($currentFusionPath))
        || file_exists($fusionResultFile);
    
    if (!$hasFusionsToMerge) {
        // Aucune fusion à traiter, rediriger vers fusion
        header('Location: acceuil_fusion.php');
        exit;
    }
    
    // Créer un répertoire temporaire pour les fichiers de fusion
    $tempDir = __DIR__ . '/temp/fusion_multiple_' . $sessionId;
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $fusionFiles = [];
    $allParams = [];
    
    // Extraire tous les fichiers fusionnés temporaires
    foreach ($fusionsTemp as $index => $fusion) {
        $tempFile = $tempDir . '/fusion_' . ($index + 1) . '.xlsx';
        file_put_contents($tempFile, $fusion['fichier']);
        $fusionFiles[] = $tempFile;
        $allParams[] = json_decode($fusion['params'], true);
    }
    
    // Ajouter la fusion actuelle si elle existe
    if ($currentFusionPath && file_exists($currentFusionPath)) {
        $currentFusionFile = $tempDir . '/fusion_current.xlsx';
        copy($currentFusionPath, $currentFusionFile);
        $fusionFiles[] = $currentFusionFile;
        $allParams[] = $fusionData['params'] ?? [];
    } elseif (file_exists($fusionResultFile)) {
        $currentFusionFile = $tempDir . '/fusion_current.xlsx';
        copy($fusionResultFile, $currentFusionFile);
        $fusionFiles[] = $currentFusionFile;
        if ($fusionData) {
            $allParams[] = $fusionData['params'] ?? [];
        }
    }
    
    if (count($fusionFiles) === 1) {
        // Une seule fusion, pas besoin de combiner
        $finalFile = __DIR__ . '/temp/result_' . $sessionId . '.xlsx';
        copy($fusionFiles[0], $finalFile);
        
        // Sauvegarder les données de fusion
        $finalData = [
            'file_name' => 'fusion_finale_' . date('Y-m-d_H-i-s') . '.xlsx',
            'file_path' => $finalFile,
            'processed_files' => 1,
            'total_files' => 1,
            'params' => $allParams[0] ?? []
        ];
        file_put_contents($fusionDataFile, json_encode($finalData));
        
    } else {
        // Fusionner tous les fichiers Excel en conservant toutes les feuilles
        $finalSpreadsheet = new Spreadsheet();
        // Supprimer la feuille par défaut
        $finalSpreadsheet->removeSheetByIndex(0);

        // Combiner toutes les fusions (chaque fichier conserve ses feuilles)
        foreach ($fusionFiles as $fileIndex => $file) {
            $source = IOFactory::load($file);
            $suffix = $fileIndex === 0 ? '' : ' ' . $fileIndex;

            foreach ($source->getAllSheets() as $sourceSheet) {
                $baseTitle = $sourceSheet->getTitle();
                $newTitle = $baseTitle . $suffix;

                // Limiter à 31 caractères (Excel) et garantir l'unicité
                $newTitle = mb_substr($newTitle, 0, 31);
                $uniqueTitle = $newTitle;
                $counter = 1;
                while ($finalSpreadsheet->sheetNameExists($uniqueTitle)) {
                    $suffixAlt = ' ' . $counter;
                    $uniqueTitle = mb_substr($newTitle, 0, 31 - mb_strlen($suffixAlt)) . $suffixAlt;
                    $counter++;
                }

                $sourceSheet->setTitle($uniqueTitle);
                $finalSpreadsheet->addExternalSheet($sourceSheet);
            }
        }

        // Sauvegarder le fichier final
        $finalFile = __DIR__ . '/temp/result_' . $sessionId . '.xlsx';
        $writer = new Xlsx($finalSpreadsheet);
        $writer->save($finalFile);
        
        // Préparer les paramètres pour la fusion finale
        $mergedParams = [
            'terroir' => $allParams[0]['terroir'] ?? '',
            'commune' => $allParams[0]['commune'] ?? '',
            'transfer_title' => 'Fusion Multiple (' . count($fusionFiles) . ' fusions)',
            'logo1' => $allParams[0]['logo1'] ?? '',
            'logo2' => $allParams[0]['logo2'] ?? ''
        ];
        
        // Sauvegarder les données de fusion
        $finalData = [
            'file_name' => 'fusion_multiple_' . date('Y-m-d_H-i-s') . '.xlsx',
            'file_path' => $finalFile,
            'processed_files' => count($fusionFiles),
            'total_files' => count($fusionFiles),
            'params' => $mergedParams
        ];
        file_put_contents($fusionDataFile, json_encode($finalData));
    }
    
    // Nettoyer les fichiers temporaires de fusion
    foreach ($fusionFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    if (is_dir($tempDir)) {
        rmdir($tempDir);
    }
    
    // Ne PAS nettoyer les fusions temporaires de la BDD ici
    // Elles seront nettoyées après l'enregistrement final
    
    // Rediriger vers l'application avec la page d'enregistrement
    header('Location: index.php?page=enregistrer-rapport');
    exit;
    
} catch (Exception $e) {
    error_log('Erreur finalisation fusions: ' . $e->getMessage());
    die('Erreur lors de la finalisation des fusions: ' . $e->getMessage());
}
