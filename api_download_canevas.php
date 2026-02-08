<?php
require_once 'classes/Database.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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
    
    // Créer un fichier temporaire
    $tempFile = tempnam(sys_get_temp_dir(), 'canevas_');
    file_put_contents($tempFile, $canevas['fichier']);
    
    // Charger le fichier avec PhpSpreadsheet
    $spreadsheet = IOFactory::load($tempFile);
    
    // Formater les dates
    $dateFormatShort = date('d/m/y');   // 05/02/26
    $dateFormatLong = date('d/m/Y');    // 05/02/2026
    $dateWithLabel = 'Date, ' . $dateFormatLong;
    
    // Ajouter la date à la première feuille (D51:I51)
    if ($spreadsheet->getSheetCount() >= 1) {
        $sheet1 = $spreadsheet->getSheet(0);
        
        // Fusionner les cellules D51:I51 si pas déjà fusionné
        if (!$sheet1->mergeCells('D51:I51')) {
            $sheet1->mergeCells('D51:I51');
        }
        
        // Insérer la date
        $sheet1->setCellValue('D51', $dateFormatShort);
        
        // Appliquer le style : Arial Black, taille 16, centré
        $sheet1->getStyle('D51:I51')->applyFromArray([
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
    
    // Sauvegarder le fichier modifié
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tempFile);
    
    // Télécharger le fichier
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $canevas['nom_fichier'] . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($tempFile);
    
    // Nettoyer
    unlink($tempFile);
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
