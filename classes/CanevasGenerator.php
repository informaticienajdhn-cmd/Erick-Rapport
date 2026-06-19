<?php
/**
 * Génération automatique des pages de garde (canevas Excel)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CanevasGenerator
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureSchema();
    }

    public function ensureSchema()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS canevas_config (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                activite_id INTEGER NOT NULL,
                commune_id INTEGER NOT NULL,
                terroir_id INTEGER,
                region_id INTEGER,
                district_id INTEGER,
                direction_regionale TEXT,
                financement TEXT,
                contrat_numero TEXT,
                objet TEXT,
                lot TEXT,
                type_rapport TEXT,
                libelle_activite TEXT,
                code_activite_1 TEXT,
                code_activite_2 TEXT,
                code_activite_3 TEXT,
                code_activite_4 TEXT,
                periode_label TEXT,
                date_os TEXT,
                date_notification TEXT,
                date_signature TEXT,
                delai_prestation TEXT,
                date_fin_contrat TEXT,
                transfert_label TEXT,
                en_tete_ong TEXT,
                intro_texte TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(activite_id, commune_id)
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS canevas_suivi (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                activite_id INTEGER NOT NULL,
                commune_id INTEGER NOT NULL,
                nom_fichier TEXT NOT NULL,
                fichier BLOB NOT NULL,
                version INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->ensureConfigColumns();
    }

    private function ensureConfigColumns()
    {
        $columns = [
            'recap_direction' => 'TEXT',
            'recap_titre' => 'TEXT',
            'recap_sous_titre' => 'TEXT',
            'recap_region' => 'TEXT',
            'recap_district' => 'TEXT',
            'recap_l1_prevue' => 'TEXT',
            'recap_l1_effective' => 'TEXT',
            'recap_l1_obs' => 'TEXT',
            'recap_l2_prevue' => 'TEXT',
            'recap_l2_effective' => 'TEXT',
            'recap_l2_obs' => 'TEXT',
            'recap_l3_prevue' => 'TEXT',
            'recap_l3_effective' => 'TEXT',
            'recap_l3_obs' => 'TEXT',
            'recap_l4_prevue' => 'TEXT',
            'recap_l4_effective' => 'TEXT',
            'recap_l4_obs' => 'TEXT',
            'recap_l5_prevue' => 'TEXT',
            'recap_l5_effective' => 'TEXT',
            'recap_l5_obs' => 'TEXT',
            'recap_l6_prevue' => 'TEXT',
            'recap_l6_effective' => 'TEXT',
            'recap_l6_obs' => 'TEXT',
            'recap_problemes' => 'TEXT',
            'recap_solutions' => 'TEXT',
            'dynamic_values' => 'TEXT',
            'template_id' => 'INTEGER',
        ];

        $existing = $this->db->query('PRAGMA table_info(canevas_config)')->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($existing, 'name');
        foreach ($columns as $column => $type) {
            if (!in_array($column, $names, true)) {
                $this->db->exec("ALTER TABLE canevas_config ADD COLUMN $column $type");
            }
        }
    }

    public function getConfigFieldNames()
    {
        return [
            'activite_id', 'commune_id', 'terroir_id', 'region_id', 'district_id',
            'direction_regionale', 'financement', 'contrat_numero', 'objet', 'lot',
            'type_rapport', 'libelle_activite', 'code_activite_1', 'code_activite_2',
            'code_activite_3', 'code_activite_4', 'periode_label', 'date_os',
            'date_notification', 'date_signature', 'delai_prestation', 'date_fin_contrat',
            'transfert_label', 'en_tete_ong', 'intro_texte',
            'recap_direction', 'recap_titre', 'recap_sous_titre', 'recap_region', 'recap_district',
            'recap_l1_prevue', 'recap_l1_effective', 'recap_l1_obs',
            'recap_l2_prevue', 'recap_l2_effective', 'recap_l2_obs',
            'recap_l3_prevue', 'recap_l3_effective', 'recap_l3_obs',
            'recap_l4_prevue', 'recap_l4_effective', 'recap_l4_obs',
            'recap_l5_prevue', 'recap_l5_effective', 'recap_l5_obs',
            'recap_l6_prevue', 'recap_l6_effective', 'recap_l6_obs',
            'recap_problemes', 'recap_solutions',
        ];
    }

    public function getTemplateDefaults()
    {
        $spreadsheet = $this->loadTemplateSpreadsheet();
        $defaults = [
            'intro_texte' => $this->extractIntroText($spreadsheet),
        ];

        $recap = $this->findSheet($spreadsheet, 'RECAP TECHN');
        if ($recap) {
            $defaults['recap_direction'] = $this->cellText($recap, 'B4');
            $defaults['recap_titre'] = $this->cellText($recap, 'B7');
            $defaults['recap_sous_titre'] = $this->cellText($recap, 'B8');
            $defaults['recap_region'] = $this->cellText($recap, 'B9');
            $defaults['recap_district'] = $this->cellText($recap, 'E9');
            $defaults['recap_problemes'] = $this->cellText($recap, 'B28');
            $defaults['recap_solutions'] = $this->cellText($recap, 'C28');

            $rows = [1 => 13, 2 => 15, 3 => 17, 4 => 19, 5 => 21, 6 => 23];
            foreach ($rows as $index => $row) {
                $defaults["recap_l{$index}_prevue"] = $this->cellDateText($recap, 'C' . $row);
                $defaults["recap_l{$index}_effective"] = $this->cellDateText($recap, 'D' . $row);
                $defaults["recap_l{$index}_obs"] = $this->cellText($recap, 'E' . $row);
            }
        }

        return $defaults;
    }

    public function listConfigs()
    {
        $stmt = $this->db->query("
            SELECT cfg.*,
                a.nom AS activite_nom,
                co.nom AS commune_nom,
                t.nom AS terroir_nom,
                r.nom AS region_nom,
                d.nom AS district_nom
            FROM canevas_config cfg
            LEFT JOIN activites a ON cfg.activite_id = a.id
            LEFT JOIN communes co ON cfg.commune_id = co.id
            LEFT JOIN terroirs t ON cfg.terroir_id = t.id
            LEFT JOIN regions r ON cfg.region_id = r.id
            LEFT JOIN districts d ON cfg.district_id = d.id
            ORDER BY cfg.updated_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getConfig($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM canevas_config WHERE id = ?');
        $stmt->execute([$id]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($config && !empty($config['dynamic_values'])) {
            $dynamic = json_decode($config['dynamic_values'], true);
            if (is_array($dynamic)) {
                $config = array_merge($config, $dynamic);
            }
        }
        return $config;
    }

    public function getConfigByPair($communeId, $activiteId)
    {
        $stmt = $this->db->prepare('
            SELECT * FROM canevas_config
            WHERE commune_id = ? AND activite_id = ?
        ');
        $stmt->execute([$communeId, $activiteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function saveConfig(array $data)
    {
        $fields = $this->getConfigFieldNames();
        $intFields = ['activite_id', 'commune_id', 'terroir_id', 'region_id', 'district_id'];
        $preserveNewlines = ['intro_texte', 'en_tete_ong'];

        $payload = [];
        foreach ($fields as $field) {
            $value = isset($data[$field]) ? (string) $data[$field] : '';
            if (in_array($field, $intFields, true)) {
                $value = trim($value);
                $payload[$field] = ($value === '') ? null : (int) $value;
            } elseif (in_array($field, $preserveNewlines, true)) {
                $payload[$field] = trim($value) === '' ? null : trim($value);
            } else {
                $payload[$field] = trim($value) === '' ? null : trim($value);
            }
        }

        if (empty($payload['activite_id']) || empty($payload['commune_id'])) {
            throw new Exception('Activité et commune sont obligatoires.');
        }

        $templateId = !empty($data['template_id']) ? (int) $data['template_id'] : null;
        $dynamicValues = [];
        if (!empty($data['dynamic_values']) && is_array($data['dynamic_values'])) {
            $dynamicValues = $data['dynamic_values'];
        } elseif (!empty($data['dynamic_payload']) && is_array($data['dynamic_payload'])) {
            $dynamicValues = $data['dynamic_payload'];
        }

        $existing = $this->getConfigByPair($payload['commune_id'], $payload['activite_id']);

        if ($existing) {
            $sets = [];
            $params = [];
            foreach ($fields as $field) {
                if ($field === 'activite_id' || $field === 'commune_id') {
                    continue;
                }
                $sets[] = "$field = :$field";
                $params[":$field"] = $payload[$field];
            }
            $sets[] = 'template_id = :template_id';
            $params[':template_id'] = $templateId;
            $sets[] = 'dynamic_values = :dynamic_values';
            $params[':dynamic_values'] = empty($dynamicValues) ? null : json_encode($dynamicValues, JSON_UNESCAPED_UNICODE);
            $sets[] = 'updated_at = CURRENT_TIMESTAMP';
            $params[':id'] = $existing['id'];
            $sql = 'UPDATE canevas_config SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $existing['id'];
        }

        $allFields = array_merge($fields, ['template_id', 'dynamic_values']);
        $payload['template_id'] = $templateId;
        $payload['dynamic_values'] = empty($dynamicValues) ? null : json_encode($dynamicValues, JSON_UNESCAPED_UNICODE);

        $columns = implode(', ', $allFields);
        $placeholders = implode(', ', array_map(fn($f) => ":$f", $allFields));
        $stmt = $this->db->prepare("INSERT INTO canevas_config ($columns) VALUES ($placeholders)");
        foreach ($allFields as $field) {
            $stmt->bindValue(":$field", $payload[$field] ?? null);
        }
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    public function generateAndStore($configId)
    {
        $config = $this->getConfig($configId);
        if (!$config) {
            throw new Exception('Configuration introuvable.');
        }

        require_once __DIR__ . '/CanevasTemplateManager.php';
        $templateManager = new CanevasTemplateManager();
        $templateManager->bootstrapDefaultTemplate();

        $template = null;
        if (!empty($config['template_id'])) {
            $template = $templateManager->getTemplate($config['template_id']);
        }
        if (!$template) {
            $template = $templateManager->getActiveTemplate();
        }

        $names = $this->resolveNames($config);

        if ($template && !empty($template['schema'])) {
            $tmp = tempnam(sys_get_temp_dir(), 'canevas_gen_') . '.xlsx';
            file_put_contents($tmp, $template['fichier']);
            try {
                $spreadsheet = IOFactory::load($tmp);
                $values = $templateManager->mergeConfigValues($config);
                $templateManager->applyValuesToSpreadsheet($spreadsheet, $template['schema'], $values, $names);
                $this->injectDates($spreadsheet);
                require_once __DIR__ . '/CanevasLogoManager.php';
                (new CanevasLogoManager())->applyToSpreadsheet($spreadsheet);
                $filename = $this->buildFilename($config, $names);
                $binary = $this->spreadsheetToBinary($spreadsheet);
                $this->storeCanevas($config['activite_id'], $config['commune_id'], $filename, $binary);
                return [
                    'config_id' => $configId,
                    'nom_fichier' => $filename,
                    'taille' => strlen($binary),
                    'template_id' => $template['id'],
                ];
            } finally {
                @unlink($tmp);
            }
        }

        $spreadsheet = $this->loadTemplateSpreadsheet();
        $this->applyCoverPage($spreadsheet, $config, $names);
        $this->applyIntroduction($spreadsheet, $config);
        $this->applyRecapTechn($spreadsheet, $config, $names);
        $this->injectDates($spreadsheet);

        require_once __DIR__ . '/CanevasLogoManager.php';
        (new CanevasLogoManager())->applyToSpreadsheet($spreadsheet);

        $filename = $this->buildFilename($config, $names);
        $binary = $this->spreadsheetToBinary($spreadsheet);
        $this->storeCanevas($config['activite_id'], $config['commune_id'], $filename, $binary);

        return [
            'config_id' => $configId,
            'nom_fichier' => $filename,
            'taille' => strlen($binary),
        ];
    }

    private function resolveNames(array $config)
    {
        return [
            'commune' => $this->lookupName('communes', $config['commune_id']),
            'activite' => $this->lookupName('activites', $config['activite_id']),
            'terroir' => $this->lookupName('terroirs', $config['terroir_id']),
            'region' => $this->lookupName('regions', $config['region_id']),
            'district' => $this->lookupName('districts', $config['district_id']),
        ];
    }

    private function lookupName($table, $id)
    {
        if (!$id) {
            return '';
        }
        $allowed = ['communes', 'activites', 'terroirs', 'regions', 'districts'];
        if (!in_array($table, $allowed, true)) {
            return '';
        }
        $stmt = $this->db->prepare("SELECT nom FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    private function getTemplateDir()
    {
        $dir = __DIR__ . '/../templates';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function loadTemplateSpreadsheet()
    {
        $templatePath = $this->getTemplateDir() . '/canevas_master.xlsx';
        if (!file_exists($templatePath)) {
            $this->bootstrapTemplateFile($templatePath);
        }
        if (file_exists($templatePath)) {
            return IOFactory::load($templatePath);
        }

        return $this->buildMinimalTemplate();
    }

    /**
     * Crée le modèle persistant à partir du dernier canevas en base.
     * PhpSpreadsheet a besoin que le fichier reste accessible (images embarquées).
     */
    private function bootstrapTemplateFile($templatePath)
    {
        $stmt = $this->db->query('SELECT fichier FROM canevas_suivi ORDER BY id DESC LIMIT 1');
        $blob = $stmt->fetchColumn();
        if ($blob) {
            file_put_contents($templatePath, $blob);
        }
    }

    private function buildMinimalTemplate()
    {
        $ss = new Spreadsheet();
        $cover = $ss->getActiveSheet();
        $cover->setTitle('page de garde');

        $merges = [
            'C1:I2', 'B24:H24', 'B27:H27', 'B28:H29', 'B30:H30', 'B31:H31',
            'B33:H33', 'B34:H34', 'B36:H36', 'A38:B41', 'D38:I41', 'A42:B45',
            'D42:I45', 'A46:B51', 'D46:I46', 'D47:I47', 'D48:I48', 'D49:I49',
            'D50:I50', 'D51:I51', 'F52:I52',
        ];
        foreach ($merges as $range) {
            try {
                $cover->mergeCells($range);
            } catch (Exception $e) {
                // ignore
            }
        }

        $intro = $ss->createSheet();
        $intro->setTitle('Introduction');
        $intro->setCellValue('A3', 'INTRODUCTION');

        $recap = $ss->createSheet();
        $recap->setTitle('RECAP TECHN');
        $recap->setCellValue('B7', 'RECAPITULATION DES REALISATIONS TECHNIQUES');
        $recap->setCellValue('B8', 'TRANSFERT MONETAIRE FSP');

        return $ss;
    }

    private function applyCoverPage(Spreadsheet $spreadsheet, array $config, array $names)
    {
        $cover = $spreadsheet->getSheet(0);

        $enTete = $config['en_tete_ong'] ?: $this->defaultEnTeteOng();
        $cover->setCellValue('C1', $enTete);
        $cover->setCellValue('B5', $config['direction_regionale'] ?: 'DIRECTION INTER REGIONALE DE MANAKARA');
        $cover->setCellValue('B24', $config['financement'] ?: 'FINANCEMENT: FILETS DE SECURITE ET DE RESILIENCE (FSR)');
        $cover->setCellValue('B27', 'CONTRAT N° ' . ($config['contrat_numero'] ?: ''));
        $cover->setCellValue('B28', $config['objet'] ?: '');
        $cover->setCellValue('B30', $config['lot'] ? 'LOT ' . $config['lot'] : '');
        $cover->setCellValue('B31', $config['type_rapport'] ?: 'RAPPORT INTERMEDIAIRE');

        if ($names['region']) {
            $cover->setCellValue('B33', 'REGION ' . mb_strtoupper($names['region']));
        }
        if ($names['district']) {
            $cover->setCellValue('B34', 'DISTRICT DE ' . mb_strtoupper($names['district']));
        }
        if ($names['terroir']) {
            $cover->setCellValue('B36', 'TERROIR ' . mb_strtoupper($names['terroir']));
        }

        $cover->setCellValue('A38', 'PERIODE');
        $cover->setCellValue('C38', $config['code_activite_1'] ?: '');
        $cover->setCellValue('C39', $config['code_activite_2'] ?: '');
        $cover->setCellValue('C40', $config['code_activite_3'] ?: '');
        $cover->setCellValue('C41', $config['code_activite_4'] ?: '');
        $cover->setCellValue('D38', $config['periode_label'] ?: '');

        $cover->setCellValue('A42', 'ACTIVITE :');
        $cover->setCellValue('D42', $config['libelle_activite'] ?: '');

        $cover->setCellValue('A46', 'DATE :');
        $cover->setCellValue('C46', 'Date OS');
        $cover->setCellValue('D46', $this->formatDateValue($config['date_os']));
        $cover->setCellValue('C47', 'Date de notification');
        $cover->setCellValue('D47', $this->formatDateValue($config['date_notification']));
        $cover->setCellValue('C48', 'Date de signature');
        $cover->setCellValue('D48', $this->formatDateValue($config['date_signature']));
        $cover->setCellValue('C49', 'Délai de prestation');
        $cover->setCellValue('D49', $this->formatDateValue($config['delai_prestation'], false));
        $cover->setCellValue('C50', 'Date prévisionel de fin contrat');
        $cover->setCellValue('D50', $this->formatDateValue($config['date_fin_contrat']));
        $cover->setCellValue('C51', "Date d'édition Rapport");

        $cover->setCellValue('F52', $config['transfert_label'] ?: 'TRANSFERT INT4');
    }

    private function applyIntroduction(Spreadsheet $spreadsheet, array $config)
    {
        if (empty($config['intro_texte'])) {
            return;
        }

        $sheet = $this->findSheet($spreadsheet, 'Introduction');
        if (!$sheet) {
            return;
        }

        $paragraphs = preg_split('/\R\R+/u', trim((string) $config['intro_texte'])) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs), fn($p) => $p !== ''));
        $cells = ['B6', 'B13', 'B14', 'B16'];

        foreach ($cells as $cell) {
            $sheet->setCellValue($cell, '');
        }

        foreach ($paragraphs as $index => $paragraph) {
            if (!isset($cells[$index])) {
                break;
            }
            $sheet->setCellValue($cells[$index], $paragraph);
        }
    }

    private function applyRecapTechn(Spreadsheet $spreadsheet, array $config, array $names)
    {
        $sheet = $this->findSheet($spreadsheet, 'RECAP TECHN');
        if (!$sheet) {
            return;
        }

        $direction = $config['recap_direction'] ?: ($config['direction_regionale'] ?: '');
        if ($direction !== '') {
            $sheet->setCellValue('B4', $direction);
        }

        if (!empty($config['recap_titre'])) {
            $sheet->setCellValue('B7', $config['recap_titre']);
        }
        if (!empty($config['recap_sous_titre'])) {
            $sheet->setCellValue('B8', $config['recap_sous_titre']);
        }

        $region = $config['recap_region'] ?: ($names['region'] ? 'REGION : ' . mb_strtoupper($names['region']) : '');
        $district = $config['recap_district'] ?: ($names['district'] ? 'DISTRICT : ' . mb_strtoupper($names['district']) : '');
        if ($region !== '') {
            $sheet->setCellValue('B9', $region);
        }
        if ($district !== '') {
            $sheet->setCellValue('E9', $district);
        }

        $rows = [1 => 13, 2 => 15, 3 => 17, 4 => 19, 5 => 21, 6 => 23];
        foreach ($rows as $index => $row) {
            $prevue = $config["recap_l{$index}_prevue"] ?? null;
            $effective = $config["recap_l{$index}_effective"] ?? null;
            $obs = $config["recap_l{$index}_obs"] ?? null;

            if ($prevue) {
                $sheet->setCellValue('C' . $row, $this->parseRecapDateValue($prevue));
            }
            if ($effective) {
                $sheet->setCellValue('D' . $row, $this->parseRecapDateValue($effective));
            }
            if ($obs) {
                $sheet->setCellValue('E' . $row, $obs);
            }
        }

        if (!empty($config['recap_problemes'])) {
            $sheet->setCellValue('B28', $config['recap_problemes']);
        }
        if (!empty($config['recap_solutions'])) {
            $sheet->setCellValue('C28', $config['recap_solutions']);
        }
    }

    private function findSheet(Spreadsheet $spreadsheet, $title)
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (strcasecmp($sheet->getTitle(), $title) === 0) {
                return $sheet;
            }
        }
        return null;
    }

    private function extractIntroText(Spreadsheet $spreadsheet)
    {
        $sheet = $this->findSheet($spreadsheet, 'Introduction');
        if (!$sheet) {
            return '';
        }

        $parts = [];
        foreach (['B6', 'B13', 'B14', 'B16'] as $cell) {
            $text = trim((string) $sheet->getCell($cell)->getValue());
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }

    private function cellText($sheet, $coordinate)
    {
        $value = $sheet->getCell($coordinate)->getCalculatedValue();
        return trim((string) ($value ?? ''));
    }

    private function cellDateText($sheet, $coordinate)
    {
        $value = $sheet->getCell($coordinate)->getCalculatedValue();
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject((float) $value)->format('d/m/Y');
            } catch (Exception $e) {
                return (string) $value;
            }
        }
        return trim((string) $value);
    }

    private function parseRecapDateValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        $formats = ['d/m/Y', 'd/m/y', 'Y-m-d'];
        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt instanceof \DateTime) {
                return Date::PHPToExcel($dt);
            }
        }

        return $value;
    }

    private function formatDateValue($value, $withColon = true)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (!$withColon) {
            return $value;
        }
        return strncmp($value, ':', 1) === 0 ? $value : ': ' . $value;
    }

    private function injectDates(Spreadsheet $spreadsheet)
    {
        $dateShort = date('d/m/y');
        $dateLong = 'Date, ' . date('d/m/Y');

        $cover = $spreadsheet->getSheet(0);
        try {
            $cover->mergeCells('D51:I51');
        } catch (Exception $e) {
        }
        $cover->setCellValue('D51', $this->formatDateValue($dateShort));
        $cover->getStyle('D51:I51')->applyFromArray([
            'font' => ['name' => 'Arial Black', 'size' => 16, 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (strtoupper($sheet->getTitle()) === 'RECAP TECHN') {
                $sheet->setCellValue('C38', $dateLong);
                break;
            }
        }
    }

    private function buildFilename(array $config, array $names)
    {
        $commune = preg_replace('/[^A-Za-z0-9_-]+/', '_', $names['commune'] ?: 'COMMUNE');
        $activite = preg_replace('/[^A-Za-z0-9_-]+/', '_', $names['activite'] ?: 'ACTIVITE');
        return 'CANEVAS_' . $commune . '_' . $activite . '.xlsx';
    }

    private function spreadsheetToBinary(Spreadsheet $spreadsheet)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'canevas_out_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmp);
        $binary = file_get_contents($tmp);
        @unlink($tmp);
        return $binary;
    }

    private function storeCanevas($activiteId, $communeId, $filename, $binary)
    {
        $stmt = $this->db->prepare('
            SELECT MAX(version) FROM canevas_suivi
            WHERE activite_id = ? AND commune_id = ?
        ');
        $stmt->execute([$activiteId, $communeId]);
        $version = ((int) $stmt->fetchColumn()) + 1;

        $stmt = $this->db->prepare('
            INSERT INTO canevas_suivi (activite_id, commune_id, nom_fichier, fichier, version)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->bindValue(1, (int) $activiteId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int) $communeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $filename, PDO::PARAM_STR);
        $stmt->bindValue(4, $binary, PDO::PARAM_LOB);
        $stmt->bindValue(5, $version, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function defaultEnTeteOng()
    {
        return "Action des Jeunes pour le Développement de l'Humanité et de la Nature, Organisation pour l'Amélioration et la Gestion de l'Environnement et le Développement durable de l'être Humain\n"
            . "Adresse : En face Est de Restaurent EZAKA AMPASY VANGAINDRANO\n"
            . "Contact : 034 27 566 66  et  032 41 798 19\n"
            . "Email : velonavyphilos@gmail.com";
    }

    public function deleteConfig($id)
    {
        $stmt = $this->db->prepare('DELETE FROM canevas_config WHERE id = ?');
        $stmt->execute([$id]);
    }
}
