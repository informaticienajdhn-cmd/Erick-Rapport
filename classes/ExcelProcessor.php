<?php
/**
 * Processeur Excel pour la fusion et le traitement des fichiers
 * @author SOMBINIAINA Erick
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ExcelProcessor
{
    private $progressCallback;
    private $totalFiles = 0;
    private $processedFiles = 0;
    private $reportParams = [];

    public function __construct($progressCallback = null)
    {
        $this->progressCallback = $progressCallback;
    }

    /**
     * Met à jour la progression
     */
    private function updateProgress($percentage, $message = '')
    {
        // Mise à jour de la session pour la barre de progression
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['progress'] = (int) $percentage;
        $_SESSION['progress_message'] = $message;
        session_write_close();

        if ($this->progressCallback && is_callable($this->progressCallback)) {
            call_user_func($this->progressCallback, $percentage, $message);
        }

        // Petit délai pour rendre la progression visible (50ms)
        usleep(50000);
    }

    /**
     * Fusionne les fichiers Excel du dossier uploads
     */
    public function mergeExcelFiles($reportParams = [])
    {
        try {
            $this->updateProgress(5, 'Initialisation de la fusion...');

            // Stockage des paramètres du rapport
            $this->reportParams = $reportParams;
            if (!is_dir(UPLOAD_DIR)) {
                throw new Exception("Le dossier d'upload n'existe pas.");
            }

            $fileList = array_diff(scandir(UPLOAD_DIR), ['.', '..']);
            $fileList = array_filter($fileList, function($file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                return in_array($extension, ALLOWED_EXTENSIONS);
            });

            if (empty($fileList)) {
                throw new Exception(ERROR_MESSAGES['NO_FILES_TO_MERGE']);
            }

            $this->totalFiles = count($fileList);
            $this->updateProgress(10, "Traitement de {$this->totalFiles} fichiers...");

            // Création du spreadsheet de fusion
            $fusionSpreadsheet = new Spreadsheet();
            $fusionSheet = $fusionSpreadsheet->getActiveSheet();
            $fusionSheet->setTitle('Fusion');

            $rowIndex = 1;
            $headerRows = null;

            // Fusion des fichiers avec mise à jour progressive
            foreach ($fileList as $index => $file) {
                try {
                    $this->processedFiles++;
                    $baseProgress = 10 + (($this->processedFiles - 1) / $this->totalFiles) * 60;
                    $fileProgress = 60 / $this->totalFiles;
                    
                    // Étape 1: Chargement du fichier
                    $currentProgress = $baseProgress + ($fileProgress * 0.2);
                    $this->updateProgress($currentProgress, "Chargement du fichier: $file");

                    $filePath = UPLOAD_DIR . $file;
                    
                    // Chargement du fichier Excel
                    $reader = IOFactory::createReaderForFile($filePath);
                    
                    // Étape 2: Lecture du fichier
                    $currentProgress = $baseProgress + ($fileProgress * 0.4);
                    $this->updateProgress($currentProgress, "Lecture du fichier: $file");
                    
                    $excelFile = $reader->load($filePath);
                    $dataSheet = $excelFile->getActiveSheet();
                    
                    // Étape 3: Conversion en tableau
                    $currentProgress = $baseProgress + ($fileProgress * 0.6);
                    $this->updateProgress($currentProgress, "Conversion des données: $file");
                    
                    $data = $dataSheet->toArray();

                    // Sauvegarde des en-têtes du premier fichier
                    if ($headerRows === null && count($data) >= 5) {
                        $headerRows = array_slice($data, 0, 5);
                    }

                    // Suppression des lignes d'en-tête sauf pour le premier fichier
                    if ($rowIndex > 1 && count($data) >= 5) {
                        $data = array_slice($data, 5);
                    }

                    // Étape 4: Fusion des données
                    $currentProgress = $baseProgress + ($fileProgress * 0.8);
                    $this->updateProgress($currentProgress, "Fusion des données: $file");

                    // Ajout des données au spreadsheet de fusion
                    $totalRows = count($data);
                    foreach ($data as $rowIdx => $row) {
                        if (!empty(array_filter($row))) {
                            // Ajout du nom du fichier source dans la dernière colonne
                            $row[] = $file;
                            $fusionSheet->fromArray($row, null, 'A' . $rowIndex);
                            $rowIndex++;
                            
                            // Mise à jour micro-progressive pour les gros fichiers (moins fréquente)
                            if ($totalRows > 200 && $rowIdx % 100 === 0) {
                                $microProgress = $baseProgress + ($fileProgress * (0.8 + (0.2 * $rowIdx / $totalRows)));
                                $this->updateProgress($microProgress, "Fusion: $file (ligne " . ($rowIdx + 1) . "/$totalRows)");
                            }
                        }
                    }
                    
                    // Étape 5: Fichier terminé
                    $finalProgress = $baseProgress + $fileProgress;
                    $this->updateProgress($finalProgress, "Fichier terminé: $file");

                } catch (Exception $e) {
                    ErrorHandler::logError("Erreur lors du traitement du fichier $file: " . $e->getMessage());
                    // Continuer avec les autres fichiers
                    continue;
                }
            }

            // Vérification que la fusion a bien été réalisée
            if ($rowIndex === 1) {
                throw new Exception("Aucun fichier n'a pu être fusionné.");
            }

            $this->updateProgress(75, 'Création des feuilles spécialisées...');
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Avant createSpecializedSheets\n", FILE_APPEND);

            // Création des feuilles spécialisées avec progression détaillée
            $result = $this->createSpecializedSheets($fusionSpreadsheet, $fusionSheet, $headerRows);
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Après createSpecializedSheets\n", FILE_APPEND);

            // Ajouter les pages de garde et conclusions si sélectionnées
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Avant insertSupplementaryDocuments\n", FILE_APPEND);
            $this->insertSupplementaryDocuments($fusionSpreadsheet);
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Après insertSupplementaryDocuments\n", FILE_APPEND);

            $this->updateProgress(90, 'Finalisation du fichier...');
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Progress = 90%\n", FILE_APPEND);

            // Suppression de la feuille temporaire
            $fusionSpreadsheet->removeSheetByIndex($fusionSpreadsheet->getIndex($fusionSheet));

            // Génération du fichier final
            $outputFileName = 'Liste_des_beneficiaires_' . date('Y-m-d_H-i-s') . '.xlsx';
            $tempFilePath = TEMP_DIR . DIRECTORY_SEPARATOR . $outputFileName;

            error_log("[ExcelProcessor] AVANT save - Chemin: $tempFilePath");
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] AVANT save - Chemin: $tempFilePath\n", FILE_APPEND);
            
            // Vérification du répertoire TEMP
            if (!is_dir(TEMP_DIR)) {
                throw new Exception("Le répertoire TEMP n'existe pas: " . TEMP_DIR);
            }
            
            if (!is_writable(TEMP_DIR)) {
                throw new Exception("Le répertoire TEMP n'est pas accessible en écriture: " . TEMP_DIR);
            }
            
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Répertoire OK, création du writer\n", FILE_APPEND);

            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Création du writer...\n", FILE_APPEND);
            $writer = IOFactory::createWriter($fusionSpreadsheet, 'Xlsx');
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Writer créé avec succès\n", FILE_APPEND);
            
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Writer créé, appel save()\n", FILE_APPEND);
            error_log("[ExcelProcessor] Writer créé, appel save()");
            
            // Vérification de la mémoire disponible
            $memUsage = memory_get_usage(true);
            $memLimit = (int)ini_get('memory_limit') * 1024 * 1024;
            file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Mémoire: " . round($memUsage/1024/1024, 2) . "MB / " . round($memLimit/1024/1024, 2) . "MB\n", FILE_APPEND);
            error_log("[ExcelProcessor] Mémoire: " . round($memUsage/1024/1024, 2) . "MB / " . round($memLimit/1024/1024, 2) . "MB");
            
            try {
                file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] Appel writer->save() direct...\n", FILE_APPEND);
                ob_start();
                $writer->save($tempFilePath);
                ob_end_clean();
                file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] APRÈS save - Succès\n", FILE_APPEND);
                error_log("[ExcelProcessor] APRÈS save - Succès");
            } catch (Exception $saveEx) {
                ob_end_clean();
                error_log("[ExcelProcessor] Erreur save: " . $saveEx->getMessage() . " at " . $saveEx->getFile() . ":" . $saveEx->getLine());
                file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] ERREUR save: " . $saveEx->getMessage() . " (" . $saveEx->getFile() . ":" . $saveEx->getLine() . ")\n", FILE_APPEND);
                throw $saveEx;
            } catch (Throwable $t) {
                ob_end_clean();
                error_log("[ExcelProcessor] Throwable save: " . $t->getMessage() . " at " . $t->getFile() . ":" . $t->getLine());
                file_put_contents(__DIR__ . '/../logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] [ExcelProcessor] THROWABLE save: " . $t->getMessage() . " (" . $t->getFile() . ":" . $t->getLine() . ")\n", FILE_APPEND);
                throw new Exception("Erreur lors de la sauvegarde du fichier Excel: " . $t->getMessage());
            }

            $this->updateProgress(100, 'Fusion terminée avec succès!');

            // Nettoyage des fichiers sources
            $this->cleanupSourceFiles($fileList);

            return [
                'success' => true,
                'file_path' => $tempFilePath,
                'file_name' => $outputFileName,
                'processed_files' => $this->processedFiles,
                'total_files' => $this->totalFiles
            ];

        } catch (Exception $e) {
            ErrorHandler::logError("Erreur lors de la fusion: " . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Crée les feuilles spécialisées (PAYER, NP, RECAP FIN)
     */
    private function createSpecializedSheets($spreadsheet, $fusionSheet, $headerRows)
    {
        $this->updateProgress(76, 'Analyse des données pour tri...');
        
        $payerData = [];
        $npData = [];

        // Parcours des données pour tri
        $data = $fusionSheet->toArray();
        $totalRows = count($data);
        
        foreach ($data as $index => $row) {
            if ($index < 5) continue; // Ignorer les en-têtes
            
            // Mise à jour progressive du tri (moins fréquente)
            if ($index % 200 === 0) {
                $progress = 76 + (($index / $totalRows) * 2);
                $this->updateProgress($progress, "Tri des données: ligne $index/$totalRows");
            }

            if (isset($row[11])) {
                if ($row[11] === '‌Payé en totalité') {
                    $payerData[] = $row;
                } elseif ($row[11] === '‌Non payé' || $row[11] === 'A Payer') {
                    $row[11] = '‌Non payé';
                    $npData[] = $row;
                }
            }
        }

        $this->updateProgress(78, 'Gestion des doublons de Fokontany...');
        
        // Gestion des doublons de Fokontany
        $this->handleDuplicateFokontany($payerData, $npData);

        // ✅ CRÉER RECAP FIN EN PREMIER (avec les logos)
        // Cela évite les opérations de réorganisation qui perdent les images
        $this->updateProgress(88, 'Création de la feuille récapitulative...');
        $this->createRecapSheet($spreadsheet, $payerData, $npData);

        // Ensuite créer les autres feuilles
        if (!empty($payerData)) {
            $this->updateProgress(80, 'Création de la feuille PAYER...');
            $this->createPayerSheet($spreadsheet, $payerData, $headerRows);
        }

        if (!empty($npData)) {
            $this->updateProgress(84, 'Création de la feuille NP (Non Payé)...');
            $this->createNpSheet($spreadsheet, $npData, $headerRows);
        }

        return [
            'payer_count' => count($payerData),
            'np_count' => count($npData)
        ];
    }

    /**
     * Gère les doublons de Fokontany
     */
    private function handleDuplicateFokontany(&$payerData, &$npData)
    {
        $allData = array_merge($payerData, $npData);
        $fokontanyFiles = [];

        // Identifier les fokontany présents dans plusieurs fichiers
        foreach ($allData as $row) {
            $fokontany = trim($row[8]);
            $file = isset($row[13]) ? $row[13] : 'inconnu';
            if (!empty($fokontany)) {
                $fokontanyFiles[$fokontany][$file] = true;
            }
        }

        $fokontanyToRename = array_filter($fokontanyFiles, function($files) {
            return count($files) > 1;
        });

        // Renommer les doublons
        $this->renameDuplicateFokontany($payerData, $fokontanyToRename);
        $this->renameDuplicateFokontany($npData, $fokontanyToRename);

        // Nettoyer la colonne fichier
        foreach ($payerData as &$row) {
            array_pop($row);
        }
        foreach ($npData as &$row) {
            array_pop($row);
        }
    }

    /**
     * Renomme les fokontany en doublon
     */
    private function renameDuplicateFokontany(&$dataArray, $fokontanyToRename)
    {
        $suffixMap = [];

        foreach ($dataArray as &$row) {
            $originalFokontany = trim($row[8]);
            $file = isset($row[13]) ? $row[13] : 'inconnu';

            if (isset($fokontanyToRename[$originalFokontany])) {
                if (!isset($suffixMap[$originalFokontany][$file])) {
                    $index = count($suffixMap[$originalFokontany] ?? []) + 1;
                    $suffixMap[$originalFokontany][$file] = $originalFokontany . ' ' . $index;
                }
                $row[8] = $suffixMap[$originalFokontany][$file];
            }
        }
    }

    /**
     * Corrige les fokontany minoritaires en les renommant selon le fokontany majoritaire du fichier source
     */
    private function correctMinorityFokontany($dataArray)
    {
        // Analyser chaque fichier source pour trouver le fokontany majoritaire
        $fileStats = [];
        
        foreach ($dataArray as $row) {
            if (!empty($row[8])) {
                // Enlever le suffixe numérique pour obtenir le nom de base du fokontany
                $fokontanyBase = preg_replace('/ \d+$/', '', trim($row[8]));
                $file = isset($row[13]) ? $row[13] : 'inconnu';
                
                if (!isset($fileStats[$file])) {
                    $fileStats[$file] = [];
                }
                
                if (!isset($fileStats[$file][$fokontanyBase])) {
                    $fileStats[$file][$fokontanyBase] = 0;
                }
                
                $fileStats[$file][$fokontanyBase]++;
            }
        }
        
        // Identifier le fokontany majoritaire pour chaque fichier
        $majorityFokontany = [];
        foreach ($fileStats as $file => $fokontanyCounts) {
            arsort($fokontanyCounts);
            $majorityFokontany[$file] = key($fokontanyCounts);
        }
        
        // Créer une copie des données pour RECAP FIN avec correction
        $correctedData = [];
        foreach ($dataArray as $row) {
            $correctedRow = $row;
            if (!empty($correctedRow[8])) {
                $fokontanyBase = preg_replace('/ \d+$/', '', trim($correctedRow[8]));
                $fokontanySuffix = preg_match('/ (\d+)$/', $correctedRow[8], $matches) ? ' ' . $matches[1] : '';
                $file = isset($correctedRow[13]) ? $correctedRow[13] : 'inconnu';
                
                // Si ce fokontany n'est pas le majoritaire du fichier, le corriger
                if (isset($majorityFokontany[$file]) && $fokontanyBase !== $majorityFokontany[$file]) {
                    // Remplacer par le fokontany majoritaire en gardant le suffixe
                    $correctedRow[8] = $majorityFokontany[$file] . $fokontanySuffix;
                }
            }
            $correctedData[] = $correctedRow;
        }
        
        return $correctedData;
    }

    /**
     * Crée la feuille PAYER
     */
    private function createPayerSheet($spreadsheet, $payerData, $headerRows)
    {
        $payerSheet = $spreadsheet->createSheet();
        $payerSheet->setTitle('PAYER');

        // Ajout des en-têtes et données
        $payerSheet->fromArray($headerRows, null, 'A1');
        $payerSheet->fromArray($payerData, null, 'A6');

        // Configuration de la feuille
        $this->configureSheet($payerSheet, 'LISTE DES PAYES EN TOTALITE', $payerData);
    }

    /**
     * Crée la feuille NP (Non Payé)
     */
    private function createNpSheet($spreadsheet, $npData, $headerRows)
    {
        $npSheet = $spreadsheet->createSheet();
        $npSheet->setTitle('NP');

        // Ajout des en-têtes et données
        $npSheet->fromArray($headerRows, null, 'A1');
        $npSheet->fromArray($npData, null, 'A6');

        // Configuration de la feuille
        $this->configureSheet($npSheet, 'LISTE DES NON PAYES', $npData);
    }

    /**
     * Configure une feuille (style, bordures, etc.)
     */
    private function configureSheet($sheet, $title, $data)
    {
        // Fusion et style du titre
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', $title);
        
        $sheet->getStyle('A1:M1')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFCC'],
            ],
        ]);

        // Configuration de la mise en page
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        // Style de la ligne d'en-tête
        $sheet->getStyle('A5:' . $sheet->getHighestColumn() . '5')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Numérotation
        $sheet->setCellValue('A5', 'N°');
        $rowCount = count($data);
        for ($i = 1; $i <= $rowCount; $i++) {
            $sheet->setCellValue('A' . ($i + 5), $i);
        }

        // Suppression des colonnes H, J et N
        $sheet->removeColumn('N');
        $sheet->removeColumn('J');
        $sheet->removeColumn('H');

        // Ajustement automatique des colonnes
        foreach (range('B', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Bordures
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $range = 'A5:' . $highestColumn . $highestRow;

        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Ajout de la somme et signature
        $this->addSummaryAndSignature($sheet, $data);
    }

    /**
     * Ajoute la somme totale et la signature
     */
    private function addSummaryAndSignature($sheet, $data)
    {
        $highestRow = $sheet->getHighestRow();
        
        // Calcul de la somme totale
        $totalSum = 0;
        foreach ($data as $row) {
            if (isset($row[10]) && is_numeric($row[10])) {
                $totalSum += $row[10];
            }
        }

        // Format de la somme
        $totalSumFormatted = number_format($totalSum, 0, ',', ' ');
        
        // Conversion en lettres (nécessite l'extension intl)
        if (class_exists('NumberFormatter')) {
            $formatter = new NumberFormatter("fr", NumberFormatter::SPELLOUT);
            $totalSumInLetters = mb_strtoupper($formatter->format($totalSum), 'UTF-8');
        } else {
            $totalSumInLetters = 'CONVERSION NON DISPONIBLE';
        }

        // Ajout de l'arrêté
        $cellArretage = 'A' . ($highestRow + 2);
        $sheet->setCellValue($cellArretage, "Arrêté le présent état à la somme de : $totalSumInLetters ARIARY ($totalSumFormatted Ar).");

        $sheet->getStyle($cellArretage)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Ajout de la somme dans la colonne I
        $firstEmptyRow = $highestRow + 1;
        $cellTotal = 'I' . $firstEmptyRow;
        $sheet->setCellValue($cellTotal, $totalSum);

        // Format monétaire
        $sheet->getStyle('I5:I' . $firstEmptyRow)->getNumberFormat()
            ->setFormatCode('#,##0 [$Ar]');

        // Bordure pour la cellule de total
        $sheet->getStyle($cellTotal)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Signature
        $sheet->setCellValue('K' . ($firstEmptyRow + 2), "L' AGENCE PAYEUR");
    }

    /**
     * Crée la feuille récapitulative
     */
    private function createRecapSheet($spreadsheet, $payerData, $npData)
    {
        $recapSheet = $spreadsheet->createSheet();
        $recapSheet->setTitle('RECAP FIN');

        // Insertion des logos
        $logoPath1 = __DIR__ . '/../logo/logo_1.jpg';
        $logoPath2 = __DIR__ . '/../logo/logo_2.png';
        
        if (file_exists($logoPath1)) {
            $drawing1 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing1->setName('Logo 1');
            $drawing1->setDescription('Logo gauche');
            $drawing1->setPath($logoPath1);
            $drawing1->setCoordinates('A1');
            $drawing1->setHeight(99); // 2.62 cm
            $drawing1->setWidth(140); // 3.7 cm
            $drawing1->setWorksheet($recapSheet);
        }
        
        if (file_exists($logoPath2)) {
            $drawing2 = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing2->setName('Logo 2');
            $drawing2->setDescription('Logo droite');
            $drawing2->setPath($logoPath2);
            $drawing2->setCoordinates('I1');
            $drawing2->setHeight(158); // 4.18 cm
            $drawing2->setWidth(110); // 2.91 cm
            $drawing2->setWorksheet($recapSheet);
        }

        // Ajuster la hauteur des lignes pour les logos (lignes 1-3 fusionnées pour logos)
        $recapSheet->getRowDimension(1)->setRowHeight(25);
        $recapSheet->getRowDimension(2)->setRowHeight(25);
        $recapSheet->getRowDimension(3)->setRowHeight(25);

        // Ligne 4: Direction (avec espace en dessous)
        $recapSheet->mergeCells('A4:I4')->setCellValue('A4', 'DIRECTION INTER REGIONALE DE MANAKARA');
        $recapSheet->getStyle('A4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $recapSheet->getRowDimension(4)->setRowHeight(20);
        $recapSheet->getRowDimension(5)->setRowHeight(10); // Ligne vide

        // Ligne 6: Transfert monétaire conditionnel
        $recapSheet->mergeCells('A6:I6')->setCellValue('A6', 'TRANSFERT MONETAIRE CONDITIONNEL');
        $recapSheet->getStyle('A6')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Ligne 7: Transfert monétaire FSP (utiliser le paramètre ou défaut)
        $transfertTitle = isset($this->reportParams['transfert_title']) && !empty($this->reportParams['transfert_title']) 
            ? $this->reportParams['transfert_title'] 
            : 'TRANSFERT MONETAIRE FSP';
        
        $recapSheet->mergeCells('A7:I7')->setCellValue('A7', $transfertTitle);
        $recapSheet->getStyle('A7')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Ligne 8: Région et district
        $region = isset($this->reportParams['region']) ? $this->reportParams['region'] : '';
        $district = isset($this->reportParams['district']) ? $this->reportParams['district'] : '';
        
        $recapSheet->setCellValue('A8', 'REGION : ' . $region);
        $recapSheet->setCellValue('D8', 'DISTRICT : ' . $district);
        $recapSheet->getStyle('A8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $recapSheet->getStyle('D8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Ligne 9: Terroir et en-têtes principales
        $terroir = isset($this->reportParams['terroir']) ? $this->reportParams['terroir'] : '';
        $terroirLabel = !empty($terroir) ? 'TERROIR ' . strtoupper($terroir) : 'TERROIR';
        
        $recapSheet->mergeCells('A9:B9')->setCellValue('A9', $terroirLabel);
        $recapSheet->mergeCells('C9:D9')->setCellValue('C9', 'PREVISIONS');
        $recapSheet->mergeCells('E9:F9')->setCellValue('E9', 'REALISATIONS');
        $recapSheet->mergeCells('G9:H9')->setCellValue('G9', 'ECART NON PAYE');
        $recapSheet->setCellValue('I9', 'OBSERVATION');

        // Ligne 10: Sous-en-têtes
        $recapSheet->setCellValue('A10', 'COMMUNE');
        $recapSheet->setCellValue('B10', 'FOKONTANY');
        $recapSheet->setCellValue('C10', 'NBRE');
        $recapSheet->setCellValue('D10', 'MONTANT' . "\n" . 'A TRANSFERER');
        $recapSheet->setCellValue('E10', 'NBRE');
        $recapSheet->setCellValue('F10', 'MONTANT' . "\n" . 'TRANSFERE');
        $recapSheet->setCellValue('G10', 'NBRE');
        $recapSheet->setCellValue('H10', 'MONTANT' . "\n" . 'NON TRANSFERE');
        $recapSheet->setCellValue('I10', '');

        // Style des en-têtes avec wrapping du texte
        $recapSheet->getStyle('A9:I10')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFF']],
        ]);

        // Correction des fokontany minoritaires par fichier source
        $correctedData = $this->correctMinorityFokontany(array_merge($payerData, $npData));

        // Regroupement des données par fokontany (avec suffixe si différent fichier source)
        $fokontanyData = [];
        foreach ($correctedData as $row) {
            if (!empty($row[8])) {
                $fokontany = $row[8]; // Garde le suffixe numérique si présent
                
                if (!isset($fokontanyData[$fokontany])) {
                    $fokontanyData[$fokontany] = [
                        'prevision_nbre' => 0, 'prevision_montant' => 0,
                        'realisation_nbre' => 0, 'realisation_montant' => 0,
                        'ecart_nbre' => 0, 'ecart_montant' => 0,
                    ];
                }

                if ($row[11] === '‌Payé en totalité') {
                    $fokontanyData[$fokontany]['prevision_nbre']++;
                    $fokontanyData[$fokontany]['prevision_montant'] += $row[10];
                    $fokontanyData[$fokontany]['realisation_nbre']++;
                    $fokontanyData[$fokontany]['realisation_montant'] += $row[10];
                } elseif ($row[11] === '‌Non payé') {
                    $fokontanyData[$fokontany]['prevision_nbre']++;
                    $fokontanyData[$fokontany]['prevision_montant'] += $row[10];
                    $fokontanyData[$fokontany]['ecart_nbre']++;
                    $fokontanyData[$fokontany]['ecart_montant'] += $row[10];
                }
            }
        }

        // Remplissage des données (à partir de la ligne 11)
        $rowStart = 11;
        $totaux = [
            'prevision_nbre' => 0, 'prevision_montant' => 0,
            'realisation_nbre' => 0, 'realisation_montant' => 0,
            'ecart_nbre' => 0, 'ecart_montant' => 0,
        ];
        foreach ($fokontanyData as $fokontany => $data) {
            $recapSheet->setCellValue('A' . $rowStart, ''); // Cellule vide
            $recapSheet->setCellValue('B' . $rowStart, $fokontany);
            $recapSheet->setCellValue('C' . $rowStart, $data['prevision_nbre']);
            $recapSheet->setCellValue('D' . $rowStart, $data['prevision_montant']);
            $recapSheet->setCellValue('E' . $rowStart, $data['realisation_nbre']);
            $recapSheet->setCellValue('F' . $rowStart, $data['realisation_montant']);
            $recapSheet->setCellValue('G' . $rowStart, $data['ecart_nbre']);
            $recapSheet->setCellValue('H' . $rowStart, $data['ecart_montant']);
            $recapSheet->setCellValue('I' . $rowStart, '');
            // Totaux
            $totaux['prevision_nbre'] += $data['prevision_nbre'];
            $totaux['prevision_montant'] += $data['prevision_montant'];
            $totaux['realisation_nbre'] += $data['realisation_nbre'];
            $totaux['realisation_montant'] += $data['realisation_montant'];
            $totaux['ecart_nbre'] += $data['ecart_nbre'];
            $totaux['ecart_montant'] += $data['ecart_montant'];
            $rowStart++;
        }

        // Fusion de toutes les cellules A en une grande cellule (jaune) avec la commune
        $lastDataRow = $rowStart - 1;
        if ($lastDataRow >= 11) {
            $recapSheet->mergeCells('A11:A' . $lastDataRow);
            
            // Récupérer la commune du paramètre
            $commune = isset($this->reportParams['commune']) ? $this->reportParams['commune'] : '';
            $recapSheet->setCellValue('A11', $commune);
            
            $recapSheet->getStyle('A11:A' . $lastDataRow)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'font' => ['bold' => true],
            ]);
        }

        // Coloration jaune pour la colonne FOKONTANY et les montants
        $recapSheet->getStyle('B11:B' . $lastDataRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $recapSheet->getStyle('D11:D' . $lastDataRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'numberFormat' => ['formatCode' => '#,##0 [$Ar]'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $recapSheet->getStyle('F11:F' . $lastDataRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'numberFormat' => ['formatCode' => '#,##0 [$Ar]'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $recapSheet->getStyle('H11:H' . $lastDataRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'numberFormat' => ['formatCode' => '#,##0 [$Ar]'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Bordures et alignement pour les autres cellules (nombres)
        $recapSheet->getStyle('C11:I' . $lastDataRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $recapSheet->getStyle('C11:C' . $lastDataRow)->getNumberFormat()->setFormatCode('0');
        $recapSheet->getStyle('E11:E' . $lastDataRow)->getNumberFormat()->setFormatCode('0');
        $recapSheet->getStyle('G11:G' . $lastDataRow)->getNumberFormat()->setFormatCode('0');

        // Ligne des totaux
        $rowTotal = $rowStart;
        $recapSheet->setCellValue('B' . $rowTotal, 'TOTAL');
        $recapSheet->setCellValue('C' . $rowTotal, $totaux['prevision_nbre']);
        $recapSheet->setCellValue('D' . $rowTotal, $totaux['prevision_montant']);
        $recapSheet->setCellValue('E' . $rowTotal, $totaux['realisation_nbre']);
        $recapSheet->setCellValue('F' . $rowTotal, $totaux['realisation_montant']);
        $recapSheet->setCellValue('G' . $rowTotal, $totaux['ecart_nbre']);
        $recapSheet->setCellValue('H' . $rowTotal, $totaux['ecart_montant']);

        $recapSheet->getStyle('B' . $rowTotal . ':I' . $rowTotal)->applyFromArray([
            'font' => ['bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $recapSheet->getStyle('D' . $rowTotal)->getNumberFormat()->setFormatCode('#,##0 [$Ar]');
        $recapSheet->getStyle('F' . $rowTotal)->getNumberFormat()->setFormatCode('#,##0 [$Ar]');
        $recapSheet->getStyle('H' . $rowTotal)->getNumberFormat()->setFormatCode('#,##0 [$Ar]');

        // Pourcentages
        $rowPourc = $rowTotal + 1;
        $pourcReal = $totaux['prevision_nbre'] > 0 ? round($totaux['realisation_nbre'] / $totaux['prevision_nbre'] * 100) : 0;
        $pourcEcart = $totaux['prevision_nbre'] > 0 ? round($totaux['ecart_nbre'] / $totaux['prevision_nbre'] * 100) : 0;
        $pourcMontantTransf = $totaux['prevision_montant'] > 0 ? round($totaux['realisation_montant'] / $totaux['prevision_montant'] * 100) : 0;
        $pourcMontantEcart = $totaux['prevision_montant'] > 0 ? round($totaux['ecart_montant'] / $totaux['prevision_montant'] * 100) : 0;
        
        $recapSheet->setCellValue('D' . $rowPourc, 'Pourcentage');
        $recapSheet->setCellValue('E' . $rowPourc, $pourcReal . '%');
        $recapSheet->setCellValue('F' . $rowPourc, $pourcMontantTransf . '%');
        $recapSheet->setCellValue('G' . $rowPourc, $pourcEcart . '%');
        $recapSheet->setCellValue('H' . $rowPourc, $pourcMontantEcart . '%');

        $recapSheet->getStyle('D' . $rowPourc . ':H' . $rowPourc)->applyFromArray([
            'font' => ['bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Supprimer les bordures de la cellule C (à droite du label Pourcentage)
        $recapSheet->getStyle('C' . $rowPourc)->applyFromArray([
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_NONE],
                'bottom' => ['borderStyle' => Border::BORDER_NONE],
                'left' => ['borderStyle' => Border::BORDER_NONE],
                'right' => ['borderStyle' => Border::BORDER_NONE],
            ],
        ]);

        // Effacer la bordure gauche de la cellule contenant Pourcentage
        $recapSheet->getStyle('D' . $rowPourc)->applyFromArray([
            'borders' => [
                'left' => ['borderStyle' => Border::BORDER_NONE],
            ],
        ]);

        // Ajouter des bordures complètes à la cellule en dessous de Pourcentage
        $recapSheet->getStyle('D' . ($rowPourc + 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Bloc synthèse
        $rowSynth = $rowPourc + 1;
        $recapSheet->setCellValue('A' . $rowSynth, 'Moins montant non payé du transfert précédent');
        
        $rowSynth2 = $rowSynth + 1;
        $recapSheet->setCellValue('A' . $rowSynth2, 'MONTANT RECU DU FID');
        $recapSheet->setCellValue('D' . $rowSynth2, $totaux['prevision_montant']);
        $recapSheet->getStyle('D' . $rowSynth2)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'numberFormat' => ['formatCode' => '#,##0 [$Ar]'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        
        $recapSheet->setCellValue('F' . $rowSynth2, 'Montant à reverser au compte FID');
        $recapSheet->setCellValue('H' . $rowSynth2, $totaux['ecart_montant']);
        $recapSheet->getStyle('H' . $rowSynth2)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'numberFormat' => ['formatCode' => '#,##0 [$Ar]'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Date et signature
        $rowDate = $rowSynth2 + 2;
        $recapSheet->setCellValue('D' . $rowDate, 'Date, ' . date('d/m/Y'));
        
        $rowSig = $rowDate + 1;
        $recapSheet->setCellValue('D' . $rowSig, "Signatures de l'Agence de paiement");

        // Fusion des cellules en dessous de Signatures de l'Agence de paiement (3 colonnes x 5 lignes)
        $recapSheet->mergeCells('D' . ($rowSig + 1) . ':F' . ($rowSig + 5));
        $recapSheet->getStyle('D' . ($rowSig + 1) . ':F' . ($rowSig + 5))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Ajustement des colonnes
        $recapSheet->getColumnDimension('A')->setWidth(20);  // COMMUNE
        $recapSheet->getColumnDimension('B')->setWidth(20);  // FOKONTANY
        $recapSheet->getColumnDimension('C')->setWidth(10);  // NBRE
        $recapSheet->getColumnDimension('D')->setWidth(20);  // MONTANT A TRANSFERER
        $recapSheet->getColumnDimension('E')->setWidth(10);  // NBRE
        $recapSheet->getColumnDimension('F')->setWidth(20);  // MONTANT TRANSFERE
        $recapSheet->getColumnDimension('G')->setWidth(10);  // NBRE
        $recapSheet->getColumnDimension('H')->setWidth(20);  // MONTANT NON TRANSFERE
        $recapSheet->getColumnDimension('I')->setWidth(15);  // OBSERVATION

        // Hauteur des lignes
        $recapSheet->getDefaultRowDimension()->setRowHeight(16);
        $recapSheet->getRowDimension(9)->setRowHeight(18);
        $recapSheet->getRowDimension(10)->setRowHeight(25);

        // Augmenter la hauteur des cellules de données jusqu'à MONTANT RECU DU FID
        for ($i = 11; $i <= $rowSynth2; $i++) {
            $recapSheet->getRowDimension($i)->setRowHeight(25);
        }

        // Taille de police
        $recapSheet->getStyle('A1:I' . ($rowSig))->getFont()->setSize(10);

        // Mise en page A4 portrait
        $recapSheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $recapSheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $recapSheet->getPageSetup()->setFitToWidth(1);
        $recapSheet->getPageSetup()->setFitToHeight(0);
        
        // Marges
        $recapSheet->getPageMargins()->setTop(0.5);
        $recapSheet->getPageMargins()->setRight(0.5);
        $recapSheet->getPageMargins()->setLeft(0.5);
        $recapSheet->getPageMargins()->setBottom(0.5);
        
        $recapSheet->getPageSetup()->setHorizontalCentered(true);
    }

    /**
     * Nettoie les fichiers sources après fusion
     */
    private function cleanupSourceFiles($fileList)
    {
        foreach ($fileList as $file) {
            $filePath = UPLOAD_DIR . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
                ErrorHandler::logError("Fichier source supprimé: $file");
            }
        }
    }

    /**
     * Insère les pages de garde et conclusions dans le spreadsheet fusionné
     */
    private function insertSupplementaryDocuments($fusionSpreadsheet)
    {
        try {
            require_once __DIR__ . '/../classes/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $terroir_id = $this->reportParams['terroir'] ?? null;
            
            if (!$terroir_id) {
                return; // Pas de document supplémentaire à ajouter
            }
            
            // Insérer la page de garde avant "recap fin" si sélectionnée
            if (!empty($this->reportParams['canevas_id'])) {
                $this->insertPageDeGarde($fusionSpreadsheet, $db);
            }
            
            // Insérer la conclusion après "NP" si sélectionnée
            if (!empty($this->reportParams['conclusion_id'])) {
                $this->insertConclusion($fusionSpreadsheet, $db);
            }
            
        } catch (Exception $e) {
            ErrorHandler::logError("Erreur lors de l'insertion des documents supplémentaires: " . $e->getMessage());
        }
    }
    
    /**
     * Insère les feuilles de la page de garde avant "recap fin"
     */
    private function insertPageDeGarde($fusionSpreadsheet, $db)
    {
        try {
            $canevas_id = $this->reportParams['canevas_id'];
            
            // Récupérer la page de garde depuis canevas_suivi
            $stmt = $db->prepare("SELECT nom_fichier, fichier FROM canevas_suivi WHERE id = ?");
            $stmt->execute([$canevas_id]);
            $canevas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$canevas) {
                return; // Pas de page de garde trouvée
            }
            
            // Créer un fichier temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'canevas_');
            file_put_contents($tempFile, $canevas['fichier']);
            
            try {
                // Charger le fichier de la page de garde
                $reader = IOFactory::createReaderForFile($tempFile);
                $canevasWorkbook = $reader->load($tempFile);
                
                // Trouver l'index de "recap fin"
                $recapFinIndex = $this->findSheetIndex($fusionSpreadsheet, 'recap fin');
                
                // Ajouter les feuilles de la page de garde avant "recap fin"
                $sheetCount = 0;
                foreach ($canevasWorkbook->getSheetNames() as $sheetName) {
                    $worksheet = $canevasWorkbook->getSheetByName($sheetName);
                    $clonedSheet = clone $worksheet;
                    
                    if ($recapFinIndex !== null) {
                        // Insérer avant "recap fin"
                        $this->insertSheetAtIndex($fusionSpreadsheet, $clonedSheet, $recapFinIndex + $sheetCount);
                    } else {
                        // Si "recap fin" n'existe pas, ajouter à la fin
                        $fusionSpreadsheet->addSheet($clonedSheet);
                    }
                    $sheetCount++;
                }
                
            } finally {
                unlink($tempFile);
            }
            
        } catch (Exception $e) {
            ErrorHandler::logError("Erreur insertion page de garde: " . $e->getMessage());
        }
    }
    
    /**
     * Insère les feuilles de la conclusion après "NP"
     */
    private function insertConclusion($fusionSpreadsheet, $db)
    {
        try {
            $conclusion_id = $this->reportParams['conclusion_id'];
            
            // Récupérer la conclusion depuis conclusions_suivi
            $stmt = $db->prepare("SELECT nom_fichier, fichier FROM conclusions_suivi WHERE id = ?");
            $stmt->execute([$conclusion_id]);
            $conclusion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$conclusion) {
                return; // Pas de conclusion trouvée
            }
            
            // Créer un fichier temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'conclusion_');
            file_put_contents($tempFile, $conclusion['fichier']);
            
            try {
                // Charger le fichier de la conclusion
                $reader = IOFactory::createReaderForFile($tempFile);
                $conclusionWorkbook = $reader->load($tempFile);
                
                // Trouver l'index de "NP"
                $npIndex = $this->findSheetIndex($fusionSpreadsheet, 'NP');
                
                // Ajouter les feuilles de la conclusion après "NP"
                $sheetCount = 0;
                foreach ($conclusionWorkbook->getSheetNames() as $sheetName) {
                    $worksheet = $conclusionWorkbook->getSheetByName($sheetName);
                    $clonedSheet = clone $worksheet;
                    
                    if ($npIndex !== null) {
                        // Insérer après "NP"
                        $this->insertSheetAtIndex($fusionSpreadsheet, $clonedSheet, $npIndex + 1 + $sheetCount);
                    } else {
                        // Si "NP" n'existe pas, ajouter à la fin
                        $fusionSpreadsheet->addSheet($clonedSheet);
                    }
                    $sheetCount++;
                }
                
            } finally {
                unlink($tempFile);
            }
            
        } catch (Exception $e) {
            ErrorHandler::logError("Erreur insertion conclusion: " . $e->getMessage());
        }
    }
    
    /**
     * Trouve l'index d'une feuille par nom (insensible à la casse)
     */
    private function findSheetIndex($spreadsheet, $sheetName)
    {
        $targetName = strtolower(trim(str_replace(' ', '', $sheetName)));
        
        foreach ($spreadsheet->getSheetNames() as $index => $name) {
            $normalizedName = strtolower(trim(str_replace(' ', '', $name)));
            if ($normalizedName === $targetName) {
                return $index;
            }
        }
        
        return null;
    }
    
    /**
     * Insère une feuille à un index spécifique
     */
    private function insertSheetAtIndex($spreadsheet, $sheet, $index)
    {
        $sheetCount = $spreadsheet->getSheetCount();
        
        if ($index >= $sheetCount) {
            // Ajouter à la fin
            $spreadsheet->addSheet($sheet);
        } else {
            // Récupérer toutes les feuilles actuelles
            $sheets = [];
            for ($i = 0; $i < $sheetCount; $i++) {
                $sheets[] = $spreadsheet->getSheet($i);
            }
            
            // Supprimer toutes les feuilles
            for ($i = $sheetCount - 1; $i >= 0; $i--) {
                $spreadsheet->removeSheetByIndex($i);
            }
            
            // Réinsérer dans le bon ordre
            $insertedIndex = 0;
            foreach ($sheets as $i => $s) {
                if ($i === $index) {
                    $spreadsheet->addSheet($sheet);
                    $insertedIndex++;
                }
                $spreadsheet->addSheet($s);
                $insertedIndex++;
            }
            
            // Si l'index était à la fin
            if ($index >= count($sheets)) {
                $spreadsheet->addSheet($sheet);
            }
        }
    }
}
?>
