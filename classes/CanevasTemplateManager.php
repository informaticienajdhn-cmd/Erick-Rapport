<?php
/**
 * Gestion des modèles de canevas personnalisés (fichier + schéma adaptatif).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/CanevasStructureDetector.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CanevasTemplateManager
{
    private $db;
    private $detector;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->detector = new CanevasStructureDetector();
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS canevas_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL,
                nom_fichier TEXT,
                fichier BLOB NOT NULL,
                schema_json TEXT NOT NULL,
                structure_hash TEXT,
                is_active INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $cols = $this->db->query('PRAGMA table_info(canevas_config)')->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($cols, 'name');
        if (!in_array('template_id', $names, true)) {
            $this->db->exec('ALTER TABLE canevas_config ADD COLUMN template_id INTEGER');
        }
        if (!in_array('dynamic_values', $names, true)) {
            $this->db->exec('ALTER TABLE canevas_config ADD COLUMN dynamic_values TEXT');
        }
    }

    public function listTemplates()
    {
        $stmt = $this->db->query('
            SELECT id, nom, nom_fichier, structure_hash, is_active, created_at, updated_at
            FROM canevas_templates
            ORDER BY is_active DESC, updated_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveTemplate()
    {
        $stmt = $this->db->query('SELECT * FROM canevas_templates WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['schema'] = json_decode($row['schema_json'], true);
        }
        return $row ?: null;
    }

    public function getTemplate($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM canevas_templates WHERE id = ?');
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['schema'] = json_decode($row['schema_json'], true);
        }
        return $row ?: null;
    }

    public function registerFromUpload(array $file, $setActive = true)
    {
        $this->validateUpload($file);

        $tmp = tempnam(sys_get_temp_dir(), 'canevas_tpl_') . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!move_uploaded_file($file['tmp_name'], $tmp)) {
            throw new Exception('Impossible de lire le fichier.');
        }

        try {
            $spreadsheet = IOFactory::load($tmp);
            $binary = file_get_contents($tmp);
            $schema = $this->detector->detectSchema($spreadsheet);
            $values = $this->detector->extractValues($spreadsheet, $schema);

            if (empty($schema['sheets'])) {
                throw new Exception('Aucun champ détecté dans ce fichier. Vérifiez qu\'il s\'agit d\'un canevas Excel.');
            }

            $hash = $schema['structure_hash'];
            $existing = $this->findByHash($hash);

            if ($existing) {
                $templateId = (int) $existing['id'];
                $this->updateTemplateFile($templateId, $file['name'], $binary, $schema);
            } else {
                $templateId = $this->insertTemplate($file['name'], $binary, $schema);
            }

            if ($setActive) {
                $this->setActive($templateId);
            }

            return [
                'template_id' => $templateId,
                'schema' => $schema,
                'values' => $values,
                'field_count' => $this->countFields($schema),
                'is_new' => !$existing,
            ];
        } finally {
            @unlink($tmp);
        }
    }

    public function setActive($id)
    {
        $this->db->exec('UPDATE canevas_templates SET is_active = 0');
        $stmt = $this->db->prepare('UPDATE canevas_templates SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([(int) $id]);
    }

    public function deleteTemplate($id)
    {
        $stmt = $this->db->prepare('DELETE FROM canevas_templates WHERE id = ?');
        $stmt->execute([(int) $id]);
    }

    public function applyValuesToSpreadsheet($spreadsheet, array $schema, array $values, array $names = [])
    {
        foreach ($schema['sheets'] ?? [] as $sheetDef) {
            $sheet = $spreadsheet->getSheet($sheetDef['index']);
            foreach ($sheetDef['fields'] as $field) {
                $key = $field['key'];
                if (!array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
                    continue;
                }
                $this->writeField($sheet, $field, $values[$key], $names);
            }
        }
    }

    public function mergeConfigValues(array $config)
    {
        $values = [];
        foreach ($config as $k => $v) {
            if ($v !== null && $v !== '' && !in_array($k, ['id', 'dynamic_values', 'schema_json', 'created_at', 'updated_at'], true)) {
                $values[$k] = $v;
            }
        }
        if (!empty($config['dynamic_values'])) {
            $dynamic = json_decode($config['dynamic_values'], true);
            if (is_array($dynamic)) {
                $values = array_merge($values, $dynamic);
            }
        }
        return $values;
    }

    public function splitValuesForSave(array $payload, array $schema)
    {
        $known = [];
        $dynamic = [];
        $schemaKeys = $this->flattenSchemaKeys($schema);

        foreach ($payload as $key => $value) {
            if (in_array($key, ['activite_id', 'commune_id', 'terroir_id', 'region_id', 'district_id', 'template_id', 'config_id'], true)) {
                continue;
            }
            if (in_array($key, $schemaKeys, true) && strpos($key, 'custom_') !== 0) {
                $dynamic[$key] = $value;
            } else {
                $known[$key] = $value;
            }
        }

        return [
            'known' => $known,
            'dynamic' => $dynamic,
        ];
    }

    private function flattenSchemaKeys(array $schema)
    {
        $keys = [];
        foreach ($schema['sheets'] ?? [] as $sheet) {
            foreach ($sheet['fields'] ?? [] as $field) {
                if (($field['type'] ?? '') === 'recap_table') {
                    $keys[] = $field['key'];
                    foreach ($field['rows'] ?? [] as $i => $row) {
                        $keys[] = 'recap_row_' . $i . '_prevue';
                        $keys[] = 'recap_row_' . $i . '_effective';
                        $keys[] = 'recap_row_' . $i . '_obs';
                    }
                } else {
                    $keys[] = $field['key'];
                }
            }
        }
        return $keys;
    }

    private function writeField($sheet, array $field, $value, array $names)
    {
        $type = $field['type'] ?? 'text';

        if ($type === 'textarea_multicell') {
            $paragraphs = preg_split('/\R\R+/u', trim((string) $value)) ?: [];
            $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));
            $cells = $field['cells'] ?? [];
            foreach ($cells as $cell) {
                $sheet->setCellValue($cell, '');
            }
            foreach ($paragraphs as $i => $paragraph) {
                if (isset($cells[$i])) {
                    $sheet->setCellValue($cells[$i], $paragraph);
                }
            }
            return;
        }

        if ($type === 'recap_table' && is_array($value)) {
            foreach ($field['rows'] ?? [] as $i => $rowDef) {
                $rowNum = $rowDef['row'];
                $rowVal = $value[$i] ?? [];
                if (!empty($rowVal['prevue'])) {
                    $sheet->setCellValue(($rowDef['prevue_col'] ?? 'C') . $rowNum, $this->parseDate($rowVal['prevue']));
                }
                if (!empty($rowVal['effective'])) {
                    $sheet->setCellValue(($rowDef['effective_col'] ?? 'D') . $rowNum, $this->parseDate($rowVal['effective']));
                }
                if (isset($rowVal['obs'])) {
                    $sheet->setCellValue(($rowDef['obs_col'] ?? 'E') . $rowNum, $rowVal['obs']);
                }
            }
            return;
        }

        $cell = $field['cell'] ?? null;
        if (!$cell) {
            return;
        }

        $text = (string) $value;

        if (!empty($field['entity'])) {
            $entityKey = $field['entity'];
            if (!empty($names[$entityKey])) {
                $prefix = $field['entity'] === 'region' ? 'REGION ' : ($field['entity'] === 'district' ? 'DISTRICT DE ' : 'TERROIR ');
                $text = $prefix . mb_strtoupper($names[$entityKey]);
            }
        }

        if (!empty($field['strip_prefixes'])) {
            // writing: rebuild with prefix for contrat/lot
            if ($field['key'] === 'contrat_numero') {
                $text = 'CONTRAT N° ' . $text;
            } elseif ($field['key'] === 'lot') {
                $text = 'LOT ' . $text;
            }
        }

        if ($type === 'date') {
            $text = $field['key'] === 'delai_prestation' ? $text : (strncmp($text, ':', 1) === 0 ? $text : ': ' . $text);
        }

        $sheet->setCellValue($cell, $text);
    }

    private function parseDate($value)
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

    private function findByHash($hash)
    {
        $stmt = $this->db->prepare('SELECT id FROM canevas_templates WHERE structure_hash = ? LIMIT 1');
        $stmt->execute([$hash]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function insertTemplate($filename, $binary, array $schema)
    {
        $nom = pathinfo($filename, PATHINFO_FILENAME);
        $stmt = $this->db->prepare('
            INSERT INTO canevas_templates (nom, nom_fichier, fichier, schema_json, structure_hash)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->bindValue(1, $nom);
        $stmt->bindValue(2, $filename);
        $stmt->bindValue(3, $binary, PDO::PARAM_LOB);
        $stmt->bindValue(4, json_encode($schema, JSON_UNESCAPED_UNICODE));
        $stmt->bindValue(5, $schema['structure_hash']);
        $stmt->execute();
        return (int) $this->db->lastInsertId();
    }

    private function updateTemplateFile($id, $filename, $binary, array $schema)
    {
        $stmt = $this->db->prepare('
            UPDATE canevas_templates
            SET nom_fichier = ?, fichier = ?, schema_json = ?, structure_hash = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->bindValue(1, $filename);
        $stmt->bindValue(2, $binary, PDO::PARAM_LOB);
        $stmt->bindValue(3, json_encode($schema, JSON_UNESCAPED_UNICODE));
        $stmt->bindValue(4, $schema['structure_hash']);
        $stmt->bindValue(5, (int) $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function countFields(array $schema)
    {
        $count = 0;
        foreach ($schema['sheets'] ?? [] as $sheet) {
            foreach ($sheet['fields'] ?? [] as $field) {
                if (($field['type'] ?? '') === 'recap_table') {
                    $count += count($field['rows'] ?? []) * 3;
                } else {
                    $count++;
                }
            }
        }
        return $count;
    }

    private function validateUpload(array $file)
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors du téléversement.');
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('Fichier trop volumineux.');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Format Excel requis (.xls, .xlsx).');
        }
    }

    public function bootstrapDefaultTemplate()
    {
        if ($this->getActiveTemplate()) {
            return;
        }

        $path = __DIR__ . '/../templates/canevas_master.xlsx';
        if (!file_exists($path)) {
            return;
        }

        $binary = file_get_contents($path);
        $spreadsheet = IOFactory::load($path);
        $schema = $this->detector->detectSchema($spreadsheet);
        $id = $this->insertTemplate('canevas_master.xlsx', $binary, $schema);
        $this->setActive($id);
    }
}
