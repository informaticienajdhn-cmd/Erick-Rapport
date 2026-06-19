<?php
/**
 * Détecte la structure d'un canevas Excel et produit un schéma de formulaire adaptatif.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CanevasStructureDetector
{
    /** @var array<string, true> */
    private $usedKeys = [];

    public function detectSchema($spreadsheet)
    {
        $this->usedKeys = [];
        $sheets = [];

        foreach ($spreadsheet->getAllSheets() as $index => $sheet) {
            $type = $this->classifySheet($sheet->getTitle(), $index);
            $fields = $this->detectSheetFields($sheet, $type);

            if (empty($fields)) {
                continue;
            }

            $sheets[] = [
                'id' => $type . '_' . $index,
                'type' => $type,
                'title' => $sheet->getTitle(),
                'index' => $index,
                'fields' => $fields,
            ];
        }

        return [
            'version' => 1,
            'sheets' => $sheets,
            'structure_hash' => $this->computeHash($sheets),
        ];
    }

    public function extractValues($spreadsheet, array $schema)
    {
        $values = [];
        foreach ($schema['sheets'] ?? [] as $sheetDef) {
            $sheet = $spreadsheet->getSheet($sheetDef['index']);
            foreach ($sheetDef['fields'] as $field) {
                $values[$field['key']] = $this->readFieldValue($sheet, $field);
            }
        }
        return $values;
    }

    private function classifySheet($title, $index)
    {
        $t = mb_strtolower(trim($title));
        if (strpos($t, 'intro') !== false) {
            return 'intro';
        }
        if (strpos($t, 'recap') !== false || strpos($t, 'techn') !== false) {
            return 'recap';
        }
        if (strpos($t, 'garde') !== false || strpos($t, 'couverture') !== false) {
            return 'cover';
        }
        return $index === 0 ? 'cover' : 'other';
    }

    private function detectSheetFields(Worksheet $sheet, $type)
    {
        switch ($type) {
            case 'cover':
                return $this->detectCoverFields($sheet);
            case 'intro':
                return $this->detectIntroFields($sheet);
            case 'recap':
                return $this->detectRecapFields($sheet);
            default:
                return $this->detectGenericFields($sheet);
        }
    }

    private function detectCoverFields(Worksheet $sheet)
    {
        $fields = [];
        $maxRow = min((int) $sheet->getHighestRow(), 55);
        $maxCol = min(Coordinate::columnIndexFromString($sheet->getHighestColumn()), 12);

        for ($row = 1; $row <= $maxRow; $row++) {
            for ($col = 1; $col <= $maxCol; $col++) {
                $coord = Coordinate::stringFromColumnIndex($col) . $row;
                $text = $this->cellText($sheet, $coord);
                if ($text === '') {
                    continue;
                }

                $matched = $this->matchCoverRule($sheet, $coord, $text, $row, $col, $maxCol);
                if ($matched) {
                    $fields[] = $matched;
                }
            }
        }

        for ($i = 0; $i < 4; $i++) {
            $key = 'code_activite_' . ($i + 1);
            if (isset($this->usedKeys[$key])) {
                continue;
            }
            $cCoord = 'C' . (38 + $i);
            $code = trim($this->cellText($sheet, $cCoord));
            if ($code !== '') {
                $field = $this->makeField($key, 'Code activité ' . ($i + 1), $cCoord, 'text');
                if ($field) {
                    $fields[] = $field;
                }
            }
        }

        return $this->uniqueFields($fields);
    }

    private function matchCoverRule(Worksheet $sheet, $coord, $text, $row, $col, $maxCol)
    {
        $upper = mb_strtoupper($text);

        $singleCellRules = [
            ['match' => '/FINANCEMENT/i', 'key' => 'financement', 'label' => 'Financement', 'type' => 'text'],
            ['match' => '/^CONTRAT/i', 'key' => 'contrat_numero', 'label' => 'N° contrat', 'type' => 'text', 'strip' => ['CONTRAT N°', 'CONTRAT N', 'CONTRAT NO', 'CONTRAT']],
            ['match' => '/^LOT\s/i', 'key' => 'lot', 'label' => 'Lot', 'type' => 'text', 'strip' => ['LOT']],
            ['match' => '/RAPPORT\s+(INTER|FINAL|ANNUEL)/i', 'key' => 'type_rapport', 'label' => 'Type de rapport', 'type' => 'text'],
            ['match' => '/^REGION\s/i', 'key' => 'region_label', 'label' => 'Région', 'type' => 'text', 'entity' => 'region'],
            ['match' => '/^DISTRICT(\s+DE)?/i', 'key' => 'district_label', 'label' => 'District', 'type' => 'text', 'entity' => 'district'],
            ['match' => '/^TERROIR\s/i', 'key' => 'terroir_label', 'label' => 'Terroir', 'type' => 'text', 'entity' => 'terroir'],
            ['match' => '/DIRECTION.*REGION/i', 'key' => 'direction_regionale', 'label' => 'Direction régionale', 'type' => 'text'],
            ['match' => '/TRANSFERT\s+(INT|MON)/i', 'key' => 'transfert_label', 'label' => 'Libellé transfert', 'type' => 'text'],
        ];

        foreach ($singleCellRules as $rule) {
            if (preg_match($rule['match'], $text)) {
                return $this->makeField($rule['key'], $rule['label'], $coord, $rule['type'], [
                    'strip_prefixes' => $rule['strip'] ?? null,
                    'entity' => $rule['entity'] ?? null,
                ]);
            }
        }

        if ($coord === 'C1' || ($row <= 3 && $col >= 3 && mb_strlen($text) > 80)) {
            return $this->makeField('en_tete_ong', 'En-tête ONG', $coord, 'textarea');
        }

        if (preg_match('/PRESTATION|OBJET|SERVICE/i', $upper) && mb_strlen($text) > 25) {
            return $this->makeField('objet', 'Objet', $coord, 'text');
        }

        if (preg_match('/^ACTIVIT/i', $upper)) {
            $valueCoord = $this->findValueCellRight($sheet, $row, $col, $maxCol);
            if ($valueCoord) {
                return $this->makeField('libelle_activite', 'Libellé activité', $valueCoord, 'text');
            }
        }

        if (preg_match('/PERIODE/i', $upper)) {
            $valueCoord = $this->findValueCellRight($sheet, $row, $col, $maxCol);
            if ($valueCoord) {
                return $this->makeField('periode_label', 'Période', $valueCoord, 'text');
            }
        }

        $dateLabels = [
            '/DATE\s*OS/i' => ['date_os', 'Date OS'],
            '/NOTIFICATION/i' => ['date_notification', 'Date de notification'],
            '/SIGNATURE/i' => ['date_signature', 'Date de signature'],
            '/DELAI.*PREST/i' => ['delai_prestation', 'Délai de prestation'],
            '/FIN\s*CONTRAT|PREVISION/i' => ['date_fin_contrat', 'Date fin contrat'],
        ];

        foreach ($dateLabels as $pattern => [$key, $label]) {
            if (preg_match($pattern, $upper)) {
                $valueCoord = $this->findValueCellRight($sheet, $row, $col, $maxCol);
                if ($valueCoord) {
                    return $this->makeField($key, $label, $valueCoord, 'date');
                }
            }
        }

        if (preg_match('/^[0-9A-Z]{3,5}$/i', trim($text)) && $col === 3) {
            for ($i = 0; $i < 4; $i++) {
                $cCoord = 'C' . ($row + $i);
                $code = trim($this->cellText($sheet, $cCoord));
                if ($code !== '' && preg_match('/^[0-9A-Z]{2,6}$/i', $code)) {
                    $this->makeField('code_activite_' . ($i + 1), 'Code activité ' . ($i + 1), $cCoord, 'text');
                }
            }
        }

        return null;
    }

    private function detectIntroFields(Worksheet $sheet)
    {
        $cells = [];
        $maxRow = min((int) $sheet->getHighestRow(), 40);

        for ($row = 1; $row <= $maxRow; $row++) {
            $coord = 'B' . $row;
            $text = trim($this->cellRaw($sheet, $coord));
            if (mb_strlen($text) >= 40) {
                $cells[] = $coord;
            }
        }

        if (empty($cells)) {
            foreach (['B6', 'B13', 'B14', 'B16'] as $coord) {
                if ($this->cellText($sheet, $coord) !== '') {
                    $cells[] = $coord;
                }
            }
        }

        if (empty($cells)) {
            return [];
        }

        return [[
            'key' => 'intro_texte',
            'label' => 'Texte complet de l\'introduction',
            'type' => 'textarea_multicell',
            'cells' => $cells,
            'help' => 'Séparez les paragraphes par une ligne vide.',
        ]];
    }

    private function detectRecapFields(Worksheet $sheet)
    {
        $fields = [];
        $headerRules = [
            ['cell' => null, 'scan' => '/DIRECTION.*REGION/i', 'key' => 'recap_direction', 'label' => 'Direction (RECAP)'],
            ['cell' => null, 'scan' => '/RECAPITULATION/i', 'key' => 'recap_titre', 'label' => 'Titre RECAP'],
            ['cell' => null, 'scan' => '/TRANSFERT\s+MON/i', 'key' => 'recap_sous_titre', 'label' => 'Sous-titre RECAP'],
        ];

        $maxRow = min((int) $sheet->getHighestRow(), 45);
        for ($row = 1; $row <= $maxRow; $row++) {
            foreach (['B', 'E'] as $col) {
                $coord = $col . $row;
                $text = $this->cellText($sheet, $coord);
                if ($text === '') {
                    continue;
                }
                if ($col === 'B' && preg_match('/REGION\s*:/i', $text)) {
                    $fields[] = $this->makeField('recap_region', 'Région (RECAP)', $coord, 'text');
                }
                if ($col === 'E' && preg_match('/DISTRICT\s*:/i', $text)) {
                    $fields[] = $this->makeField('recap_district', 'District (RECAP)', $coord, 'text');
                }
                if (preg_match('/RECAPITULATION/i', $text)) {
                    $fields[] = $this->makeField('recap_titre', 'Titre RECAP', $coord, 'text');
                }
                if (preg_match('/TRANSFERT\s+MON/i', $text)) {
                    $fields[] = $this->makeField('recap_sous_titre', 'Sous-titre RECAP', $coord, 'text');
                }
                if (preg_match('/DIRECTION.*REGION/i', $text) && $row <= 6) {
                    $fields[] = $this->makeField('recap_direction', 'Direction (RECAP)', $coord, 'text');
                }
            }
        }

        $tableRows = [];
        for ($row = 10; $row <= 35; $row++) {
            $label = $this->cellText($sheet, 'B' . $row);
            if ($label === '' || preg_match('/^(DATE|PREVUE|EFFECTIVE|OBSERVATION)/i', $label)) {
                continue;
            }
            if (preg_match('/PROBLEME|SOLUTION/i', $label)) {
                continue;
            }

            $prevue = $this->cellText($sheet, 'C' . $row);
            $effective = $this->cellText($sheet, 'D' . $row);
            $obs = $this->cellText($sheet, 'E' . $row);

            if ($prevue === '' && $effective === '' && $obs === '') {
                continue;
            }

            $tableRows[] = [
                'row' => $row,
                'label' => $label,
                'prevue_col' => 'C',
                'effective_col' => 'D',
                'obs_col' => 'E',
            ];
        }

        if (!empty($tableRows)) {
            $fields[] = [
                'key' => 'recap_table',
                'label' => 'Calendrier des réalisations',
                'type' => 'recap_table',
                'rows' => $tableRows,
                'sync_prevue_effective' => true,
            ];
        }

        $problemes = $this->cellText($sheet, 'B28');
        $solutions = $this->cellText($sheet, 'C28');
        if ($problemes !== '' || preg_match('/PROBLEME/i', $this->cellText($sheet, 'B27'))) {
            $fields[] = $this->makeField('recap_problemes', 'Problèmes majeurs', 'B28', 'textarea');
            $fields[] = $this->makeField('recap_solutions', 'Solutions prises', 'C28', 'textarea');
        }

        return $this->uniqueFields($fields);
    }

    private function detectGenericFields(Worksheet $sheet)
    {
        $fields = [];
        $maxRow = min((int) $sheet->getHighestRow(), 30);
        $maxCol = min(Coordinate::columnIndexFromString($sheet->getHighestColumn()), 8);

        for ($row = 1; $row <= $maxRow; $row++) {
            for ($col = 1; $col <= $maxCol; $col++) {
                $coord = Coordinate::stringFromColumnIndex($col) . $row;
                $text = $this->cellText($sheet, $coord);
                if (mb_strlen($text) < 15) {
                    continue;
                }
                $key = 'custom_' . strtolower($coord);
                $fields[] = $this->makeField($key, 'Champ ' . $coord, $coord, mb_strlen($text) > 80 ? 'textarea' : 'text');
            }
        }

        return array_slice($this->uniqueFields($fields), 0, 15);
    }

    private function makeField($key, $label, $cell, $type, array $extra = [])
    {
        if (isset($this->usedKeys[$key])) {
            return null;
        }
        $this->usedKeys[$key] = true;

        $field = array_merge([
            'key' => $key,
            'label' => $label,
            'cell' => $cell,
            'type' => $type,
        ], $extra);

        return $field;
    }

    private function uniqueFields(array $fields)
    {
        $out = [];
        $seen = [];
        foreach ($fields as $field) {
            if (!$field || isset($seen[$field['key']])) {
                continue;
            }
            $seen[$field['key']] = true;
            $out[] = $field;
        }
        return $out;
    }

    private function findValueCellRight(Worksheet $sheet, $row, $col, $maxCol)
    {
        for ($c = $col + 1; $c <= $maxCol; $c++) {
            $coord = Coordinate::stringFromColumnIndex($c) . $row;
            $text = $this->cellText($sheet, $coord);
            if ($text !== '' && !preg_match('/^(DATE|ACTIVIT|PERIODE)/i', $text)) {
                return $coord;
            }
        }
        return null;
    }

    private function readFieldValue(Worksheet $sheet, array $field)
    {
        $type = $field['type'] ?? 'text';

        if ($type === 'textarea_multicell') {
            $parts = [];
            foreach ($field['cells'] ?? [] as $cell) {
                $text = trim($this->cellRaw($sheet, $cell));
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
            return implode("\n\n", $parts);
        }

        if ($type === 'recap_table') {
            $rows = [];
            foreach ($field['rows'] ?? [] as $i => $rowDef) {
                $rowNum = $rowDef['row'];
                $rows[] = [
                    'prevue' => $this->cellDateText($sheet, ($rowDef['prevue_col'] ?? 'C') . $rowNum),
                    'effective' => $this->cellDateText($sheet, ($rowDef['effective_col'] ?? 'D') . $rowNum),
                    'obs' => $this->cellText($sheet, ($rowDef['obs_col'] ?? 'E') . $rowNum),
                ];
            }
            return $rows;
        }

        $text = $this->cellText($sheet, $field['cell'] ?? 'A1');
        if (!empty($field['strip_prefixes'])) {
            $text = $this->stripPrefix($text, $field['strip_prefixes']);
        }
        if (($field['type'] ?? '') === 'date') {
            return $this->formatDateValue($text);
        }
        return $text;
    }

    private function cellText(Worksheet $sheet, $coord)
    {
        $value = $sheet->getCell($coord)->getCalculatedValue();
        return trim((string) ($value ?? ''));
    }

    private function cellRaw(Worksheet $sheet, $coord)
    {
        return (string) ($sheet->getCell($coord)->getValue() ?? '');
    }

    private function cellDateText(Worksheet $sheet, $coord)
    {
        $value = $sheet->getCell($coord)->getCalculatedValue();
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

    private function formatDateValue($value)
    {
        $value = trim((string) $value);
        return ltrim($value, ': ');
    }

    private function stripPrefix($value, array $prefixes)
    {
        $value = trim($value);
        foreach ($prefixes as $prefix) {
            if (stripos($value, $prefix) === 0) {
                return trim(substr($value, strlen($prefix)), " \t\n\r\0\x0B:-");
            }
        }
        return $value;
    }

    private function computeHash(array $sheets)
    {
        $payload = [];
        foreach ($sheets as $sheet) {
            $payload[] = $sheet['title'];
            foreach ($sheet['fields'] as $field) {
                $payload[] = ($field['key'] ?? '') . '@' . ($field['cell'] ?? json_encode($field['cells'] ?? $field['rows'] ?? []));
            }
        }
        return substr(sha1(implode('|', $payload)), 0, 16);
    }
}
