<?php
session_start();

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

// Fonction pour mettre à jour la progression
function updateProgress($percentage, $message = '') {
    $_SESSION['progress'] = (int)$percentage;
    $_SESSION['progress_message'] = $message;
    session_write_close();
    session_start();
    usleep(50000); // 50ms pause pour rendre visible
}
// Récupérer les paramètres du formulaire
$titre_suivi = isset($_POST['titre_suivi']) && !empty($_POST['titre_suivi']) ? $_POST['titre_suivi'] : 'SUIVI DES PAIEMENTS';
$commune_suivi = isset($_POST['commune_suivi']) && !empty($_POST['commune_suivi']) ? $_POST['commune_suivi'] : '';
$terroir_suivi = isset($_POST['terroir_suivi']) && !empty($_POST['terroir_suivi']) ? $_POST['terroir_suivi'] : '';

// Log de débogage
file_put_contents(__DIR__.'/logs/debug_suivi.txt', "[".date('Y-m-d H:i:s')."] POST reçus:\n", FILE_APPEND);
file_put_contents(__DIR__.'/logs/debug_suivi.txt', "  - titre_suivi: ".$titre_suivi."\n", FILE_APPEND);
file_put_contents(__DIR__.'/logs/debug_suivi.txt', "  - commune_suivi: ".$commune_suivi."\n", FILE_APPEND);
file_put_contents(__DIR__.'/logs/debug_suivi.txt', "  - terroir_suivi: ".$terroir_suivi."\n", FILE_APPEND);

$uploadDir = 'uploads/';
$fusionFile = 'Suvi des paiements.xlsx';

updateProgress(5, 'Initialisation...');

// Vérifier la présence de fichiers
if (!is_dir($uploadDir) || empty(scandir($uploadDir))) {
    die("Aucun fichier à fusionner.");
}

$fileList = array_diff(scandir($uploadDir), ['.', '..']);

updateProgress(10, 'Création du fichier de fusion...');

$fusionSpreadsheet = new Spreadsheet();
$fusionSheet = $fusionSpreadsheet->getActiveSheet();
$fusionSheet->setTitle('Fichier Fusionné');

$rowIndex = 1;
$dataCollection = [];

$totalFiles = count($fileList);
$processedFiles = 0;

updateProgress(15, "Traitement de $totalFiles fichiers...");

// Charger et fusionner les fichiers
foreach ($fileList as $file) {
    $processedFiles++;
    $baseProgress = 15 + (($processedFiles - 1) / $totalFiles) * 50;
    
    updateProgress($baseProgress, "Chargement: $file");
    
    $filePath = $uploadDir . $file;
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    if (in_array($extension, ['xls', 'xlsx'])) {
        updateProgress($baseProgress + 5, "Lecture: $file");
        
        $reader = IOFactory::createReaderForFile($filePath);
        $excelFile = $reader->load($filePath);
        $dataSheet = $excelFile->getActiveSheet();
        $data = $dataSheet->toArray();

        updateProgress($baseProgress + 10, "Fusion des données: $file");

        foreach ($data as $index => $row) {
            if ($index < 5) continue; // Ignorer les en-têtes

            if (!empty(array_filter($row))) {
                $fusionSheet->fromArray($row, null, 'A' . $rowIndex);

                $fokontany = $row[8] ?? '';
                $utilisateur = $row[13] ?? '';
                $montant = isset($row[10]) ? floatval($row[10]) : 0;

                $key = $utilisateur . '-' . $fokontany;
                if (!isset($dataCollection[$key])) {
                    $dataCollection[$key] = [
                        'caisses' => $utilisateur,
                        'fokontany' => $fokontany,
                        'nb_beneficiaires' => 0,
                        'montant_recu' => 0,
                    ];
                }
                $dataCollection[$key]['nb_beneficiaires']++;
                $dataCollection[$key]['montant_recu'] += $montant;

                $rowIndex++;
            }
        }

        // Supprimer le fichier après traitement
        unlink($filePath);
    }
}

if ($rowIndex === 1) {
    die("Erreur : Aucun fichier n'a été fusionné.");
}

updateProgress(70, 'Tri des données...');

// Trier les caisses pour que les utilisateurs similaires se suivent
ksort($dataCollection);

updateProgress(75, 'Création de la feuille SUIVI PAIEMENT...');

// Création de la feuille "SUIVI PAIEMENT"
$suiviSheet = $fusionSpreadsheet->createSheet();
$suiviSheet->setTitle('SUIVI PAIEMENT');

// Paramétrage de la mise en page pour l'impression
$suiviSheet->getPageSetup()
    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE) // Mettre en paysage
    ->setFitToWidth(1) // Ajuster la largeur sur une seule page
    ->setFitToHeight(0); // Hauteur automatique

// Ajout de l'en-tête "SUIVI DES PAIEMENTS" fusionné
$suiviSheet->mergeCells('A1:I1');
$suiviSheet->setCellValue('A1', $titre_suivi);

// Log de débogage
file_put_contents(__DIR__.'/logs/debug_suivi.txt', "[".date('Y-m-d H:i:s')."] Cellule A1 définie: ".$titre_suivi."\n", FILE_APPEND);

$suiviSheet->getStyle('A1:I1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE699']],
]);

// Ajout de la ligne "Commune / Terroir"
$communeTerroir = 'Commune / Terroir : ';
if (!empty($commune_suivi)) {
    $communeTerroir .= $commune_suivi;
}
if (!empty($terroir_suivi)) {
    if (!empty($commune_suivi)) {
        $communeTerroir .= ' / ';
    }
    $communeTerroir .= $terroir_suivi;
}

$suiviSheet->mergeCells('A2:I2');
$suiviSheet->setCellValue('A2', $communeTerroir);

// Log de débogage
file_put_contents(__DIR__.'/logs/debug_suivi.txt', "[".date('Y-m-d H:i:s')."] Cellule A2 définie: ".$communeTerroir."\n", FILE_APPEND);

$suiviSheet->getStyle('A2:I2')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
]);

// Ajout des en-têtes du tableau
$headers = ['N°', 'CAISSE', 'FOKONTANY', 'NB BENEFICIAIRE', 'MONTANT RECU', 'MONTANT PAYES', 'NB ABS', 'MONTANT A RENDRE', 'SIGNATURE'];
$suiviSheet->fromArray([$headers], null, 'A3');

// Style des en-têtes
$suiviSheet->getStyle('A3:I3')->applyFromArray([
    'font' => ['bold' => true, 'size' => 12],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFCC']],
]);

// Ajout des données correctement structurées
$rowIndex = 4;
$totalBeneficiaires = 0;
$totalMontantRecu = 0;

foreach ($dataCollection as $key => $values) {
    $suiviSheet->fromArray([
        [$rowIndex - 3, $values['caisses'], $values['fokontany'], $values['nb_beneficiaires'], $values['montant_recu'], '', '', '', '']
    ], null, 'A' . $rowIndex);

    $totalBeneficiaires += $values['nb_beneficiaires'];
    $totalMontantRecu += $values['montant_recu'];
    $rowIndex++;
}

// Ajout de la ligne des totaux avec fusion des trois premières cellules
$suiviSheet->mergeCells("A$rowIndex:C$rowIndex");
$suiviSheet->setCellValue("A$rowIndex", "TOTAL");
$suiviSheet->setCellValue("D$rowIndex", $totalBeneficiaires);
$suiviSheet->setCellValue("E$rowIndex", $totalMontantRecu);

updateProgress(85, 'Application des styles...');

// Appliquer le format monétaire Ariary
$suiviSheet->getStyle("E4:E$rowIndex")->getNumberFormat()->setFormatCode('#,##0 [$Ar]');

// Appliquer des bordures aux données
$suiviSheet->getStyle("A4:I$rowIndex")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Ajustement automatique de la largeur des colonnes
foreach (range('A', 'I') as $col) {
    $suiviSheet->getColumnDimension($col)->setAutoSize(true);
}

updateProgress(90, 'Finalisation du fichier...');

// Enregistrer et télécharger le fichier Excel
$tempFilePath = tempnam(sys_get_temp_dir(), 'Liste_des_beneficiaires') . '.xlsx';
$writer = IOFactory::createWriter($fusionSpreadsheet, 'Xlsx');
$writer->save($tempFilePath);

updateProgress(100, 'Fusion terminée avec succès!');

// Marquer comme prêt pour téléchargement
$_SESSION['download_ready'] = true;
$_SESSION['download_file'] = $tempFilePath;
$_SESSION['download_name'] = 'Liste_des_beneficiaires.xlsx';
session_write_close();

// Télécharger le fichier
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Liste_des_beneficiaires.xlsx"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');
header('Content-Length: ' . filesize($tempFilePath));

readfile($tempFilePath);
unlink($tempFilePath);
exit;
?>
