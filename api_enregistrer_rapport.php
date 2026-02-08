<?php
/**
 * API pour enregistrer le rapport après fusion
 */
session_start();

require_once 'config.php';
require_once 'classes/Database.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/json; charset=utf-8');

/**
 * Injecte la date dans le canevas/conclusion
 * @param string $tempFilePath chemin du fichier temporaire
 * @param bool $isPageGarde true pour page de garde, false pour conclusion
 */
function injectDateInFile($tempFilePath, $isPageGarde = true) {
    try {
        $spreadsheet = IOFactory::load($tempFilePath);
        
        $dateFormatShort = date('d/m/y');   // 05/02/26
        $dateFormatLong = date('d/m/Y');    // 05/02/2026
        $dateWithLabel = 'Date, ' . $dateFormatLong;
        
        if ($isPageGarde) {
            // Page de garde: cellules D51:I51 de la 1ère feuille, Arial Black 16
            if ($spreadsheet->getSheetCount() >= 1) {
                $sheet = $spreadsheet->getSheet(0);
                
                // Fusionner si pas déjà fusionné
                try {
                    $sheet->mergeCells('D51:I51');
                } catch (Exception $e) {
                    // Déjà fusionné, continuer
                }
                
                $sheet->setCellValue('D51', $dateFormatShort);
                $sheet->getStyle('D51:I51')->applyFromArray([
                    'font' => [
                        'name' => 'Arial Black',
                        'size' => 16,
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }
            
            // Chercher et injecter la date dans la feuille "RECAP TECHN" à C38
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                if (strtoupper($sheet->getTitle()) === 'RECAP TECHN') {
                    $sheet->setCellValue('C38', $dateWithLabel);
                    $sheet->getStyle('C38')->applyFromArray([
                        'font' => [
                            'name' => 'Arial',
                            'size' => 11,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    
                    // Ajuster la largeur des colonnes C et D à 82 pixels
                    $sheet->getColumnDimension('C')->setWidth(82 / 7); // conversion pixels en unités Excel
                    $sheet->getColumnDimension('D')->setWidth(82 / 7); // conversion pixels en unités Excel
                    break;
                }
            }
        } else {
            // Conclusion: cellule C38, format RECAP FIN
            // Utiliser la 3ème feuille si elle existe, sinon utiliser la dernière feuille disponible
            $sheetCount = $spreadsheet->getSheetCount();
            if ($sheetCount >= 1) {
                $targetIndex = min(2, $sheetCount - 1); // index 2 = 3ème feuille, fallback sur la dernière
                $sheet = $spreadsheet->getSheet($targetIndex);

                $sheet->setCellValue('C38', $dateWithLabel);
                $sheet->getStyle('C38')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }
        }
        
        // Sauvegarder les modifications
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempFilePath);
        
    } catch (Exception $e) {
        // Log l'erreur mais continue (dates non injectées)
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Erreur injection date: ".$e->getMessage()."\n", FILE_APPEND);
    }
}

try {
    // Lire les données de fusion depuis le fichier temporaire
    $sessionId = session_id();
    $fusionDataFile = __DIR__ . '/temp/fusion_data_' . $sessionId . '.json';
    $fusionData = null;
    
    if (file_exists($fusionDataFile)) {
        $fusionData = json_decode(file_get_contents($fusionDataFile), true);
    }

    // Debug
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] api_enregistrer_rapport.php - Session ID: ".$sessionId."\n", FILE_APPEND);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] fusionDataFile exists: ".(file_exists($fusionDataFile) ? 'OUI' : 'NON')."\n", FILE_APPEND);
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] POST nom: ".($_POST['nom'] ?? 'VIDE')." | activite_id: ".($_POST['activite_id'] ?? 'VIDE')."\n", FILE_APPEND);

    // Vérifier que la fusion s'est bien déroulée
    if (!$fusionData || empty($fusionData['file_path']) || !file_exists($fusionData['file_path'])) {
        throw new Exception('Fichier de rapport introuvable');
    }

    $nom = $_POST['nom'] ?? '';
    $activite_id = $_POST['activite_id'] ?? '';
    $canevas_id = $_POST['canevas_id'] ?? '';
    $conclusion_id = $_POST['conclusion_id'] ?? '';
    $params = $fusionData['params'] ?? [];

    if (!$nom || !$activite_id) {
        throw new Exception('Paramètres manquants');
    }

    // Récupérer l'ID de la commune
    $db = Database::getInstance();
    $communes = $db->getAll('communes');
    $commune_id = null;

    foreach ($communes as $commune) {
        if ($commune['nom'] === $params['commune']) {
            $commune_id = $commune['id'];
            break;
        }
    }

    if (!$commune_id) {
        throw new Exception('Commune non trouvée');
    }

    // Charger le fichier fusionné et y insérer page de garde + conclusion (si sélectionnées)
    $fusionFilePath = $fusionData['file_path'];
    
    // Fichiers temporaires à garder jusqu'après la sauvegarde
    $tempFilesToCleanup = [];
    
    // Fichiers à fusionner dans l'ordre
    $filesToMerge = [];
    
    // 1. Ajouter la page de garde en premier (si sélectionnée)
    if (!empty($canevas_id)) {
        // Chercher le canevas par nom du fichier
        $stmt = $db->getConnection()->prepare("SELECT nom_fichier, fichier FROM canevas_suivi WHERE nom_fichier = ? LIMIT 1");
        $stmt->execute([$canevas_id]);
        $canevas = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($canevas) {
            $tempCanevas = TEMP_DIR . DIRECTORY_SEPARATOR . 'canevas_' . uniqid() . '.xlsx';
            file_put_contents($tempCanevas, $canevas['fichier']);
            
            // ✅ INJECTER LA DATE DANS LA PAGE DE GARDE
            injectDateInFile($tempCanevas, true);
            
            $tempFilesToCleanup[] = $tempCanevas;
            $filesToMerge[] = $tempCanevas;
            file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Page de garde ajoutée: ".$canevas['nom_fichier']."\n", FILE_APPEND);
        }
    }

    // 2. Ajouter le fichier fusionné
    $filesToMerge[] = $fusionFilePath;

    // 3. Ajouter la conclusion à la fin (si sélectionnée)
    if (!empty($conclusion_id)) {
        // Chercher la conclusion par nom du fichier
        $stmt = $db->getConnection()->prepare("SELECT nom_fichier, fichier FROM conclusions_suivi WHERE nom_fichier = ? LIMIT 1");
        $stmt->execute([$conclusion_id]);
        $conclusion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conclusion) {
            $tempConclusion = TEMP_DIR . DIRECTORY_SEPARATOR . 'conclusion_' . uniqid() . '.xlsx';
            file_put_contents($tempConclusion, $conclusion['fichier']);
            
            // Ne pas injecter la date dans la conclusion — seules les pages de garde reçoivent la date
            $tempFilesToCleanup[] = $tempConclusion;
            $filesToMerge[] = $tempConclusion;
            file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Conclusion ajoutée: ".$conclusion['nom_fichier']."\n", FILE_APPEND);
        }
    }

    // ✅ OPTIMISATION CLÉS: 
    // - Si AUCUN canevas/conclusion: COPIE DIRECTE (images préservées à 100%)
    // - Si canevas/conclusion présent: Utiliser PhpSpreadsheet (fallback fiable)
    
    $finalTempPath = TEMP_DIR . DIRECTORY_SEPARATOR . 'Rapport_final_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    if (count($filesToMerge) === 1 && $filesToMerge[0] === $fusionFilePath) {
        // ✅ CAS OPTIMISÉ: AUCUN canevas/conclusion - COPIE DIRECTE
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Optimisation: AUCUN canevas/conclusion, copie directe du fichier fusionné\n", FILE_APPEND);
        copy($fusionFilePath, $finalTempPath);
    } else {
        // CAS AVEC FUSION: Utiliser PhpSpreadsheet (Python est trop complexe pour préserver les images)
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Fusion nécessaire: canevas/conclusion présents - utilisation PhpSpreadsheet\n", FILE_APPEND);
        
        // Charger le premier fichier (fusion)
        $spreadsheet = IOFactory::load($filesToMerge[0]);
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Base chargée: ".basename($filesToMerge[0])." (".count($spreadsheet->getAllSheets())." feuilles)\n", FILE_APPEND);
        
        // Ajouter les autres fichiers
        for ($i = 1; $i < count($filesToMerge); $i++) {
            file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Ajout fichier ".($i+1)."/".count($filesToMerge).": ".basename($filesToMerge[$i])."\n", FILE_APPEND);
            
            try {
                $workbook = IOFactory::load($filesToMerge[$i]);
                
                foreach ($workbook->getAllSheets() as $sheet) {
                    // Utiliser addExternalSheet qui devrait préserver la structure
                    $spreadsheet->addExternalSheet($sheet);
                    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Feuille ajoutée: ".$sheet->getTitle()."\n", FILE_APPEND);
                }
            } catch (Exception $e) {
                file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Erreur traitement fichier: ".$e->getMessage()."\n", FILE_APPEND);
                throw $e;
            }
        }
        
        // Sauvegarder
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Avant sauvegarde fichier final...\n", FILE_APPEND);
        $writer->save($finalTempPath);
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Fichier final sauvegardé avec succès\n", FILE_APPEND);
    }
    
    // Vérifier que le fichier final existe
    if (!file_exists($finalTempPath)) {
        throw new Exception("Échec de la création du fichier fusionné");
    }

    // Lire le contenu du fichier final
    $fichier_content = file_get_contents($finalTempPath);
    $fichier_size = filesize($finalTempPath);

    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Taille du fichier à sauvegarder: ".$fichier_size." bytes\n", FILE_APPEND);

    // Insérer en base de données avec binding correct pour BLOB
    $stmt = $db->getConnection()->prepare("
        INSERT INTO rapports_enregistres (nom, commune_id, activite_id, fichier)
        VALUES (:nom, :commune_id, :activite_id, :fichier)
    ");

    // Utiliser bindParam avec PDO::PARAM_LOB pour les données binaires
    $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
    $stmt->bindParam(':commune_id', $commune_id, PDO::PARAM_INT);
    $stmt->bindParam(':activite_id', $activite_id, PDO::PARAM_INT);
    $stmt->bindParam(':fichier', $fichier_content, PDO::PARAM_LOB);
    $stmt->execute();

    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Rapport enregistré avec succès\n", FILE_APPEND);

    // Nettoyage: supprimer les fichiers temporaires et les données
    if (file_exists($finalTempPath)) {
        unlink($finalTempPath);
    }
    if (file_exists($fusionFilePath)) {
        unlink($fusionFilePath);
    }
    if (file_exists($fusionDataFile)) {
        unlink($fusionDataFile);
    }
    
    // Supprimer les fichiers temporaires de canevas/conclusion
    foreach ($tempFilesToCleanup as $tempFile) {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
    
    // Nettoyer les fusions temporaires de la base de données
    $sessionId = session_id();
    try {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare("DELETE FROM fusions_temporaires WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    } catch (Exception $cleanupError) {
        // Log mais ne pas échouer l'opération principale
        error_log('Erreur nettoyage fusions temporaires: ' . $cleanupError->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Rapport enregistré avec succès'
    ]);

} catch (Exception $e) {
    file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] api_enregistrer_rapport.php ERROR: ".$e->getMessage()."\n", FILE_APPEND);
    
    // Nettoyer les fichiers temporaires en cas d'erreur aussi
    if (isset($tempFilesToCleanup)) {
        foreach ($tempFilesToCleanup as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
