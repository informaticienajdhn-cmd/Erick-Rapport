<?php
/**
 * Analyse un fichier canevas Excel existant et extrait les données pour pré-remplir le formulaire.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CanevasAnalyzer
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Analyse un fichier Excel uploadé et retourne les champs du formulaire.
     */
    public function analyzeUploadedFile(array $file)
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors du téléversement du fichier.');
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('Fichier trop volumineux (max ' . (MAX_FILE_SIZE / 1024 / 1024) . ' Mo).');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Seuls les fichiers Excel (.xls, .xlsx) sont acceptés.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'canevas_analyze_') . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $tmp)) {
            throw new Exception('Impossible de lire le fichier uploadé.');
        }

        try {
            $spreadsheet = IOFactory::load($tmp);
            return $this->analyzeSpreadsheet($spreadsheet, $file['name']);
        } finally {
            @unlink($tmp);
        }
    }

    public function analyzeSpreadsheet($spreadsheet, $filename = '')
    {
        $cover = $this->findSheet($spreadsheet, 'page de garde') ?: $spreadsheet->getSheet(0);
        $intro = $this->findSheet($spreadsheet, 'Introduction');
        $recap = $this->findSheet($spreadsheet, 'RECAP TECHN');

        $data = [];
        $matched = [];

        // --- Page de garde ---
        $data['en_tete_ong'] = $this->cellText($cover, 'C1');
        $data['direction_regionale'] = $this->cellText($cover, 'B5');
        $data['financement'] = $this->cellText($cover, 'B24');
        $data['contrat_numero'] = $this->stripPrefix($this->cellText($cover, 'B27'), ['CONTRAT N°', 'CONTRAT N', 'CONTRAT NO', 'CONTRAT']);
        $data['objet'] = $this->cellText($cover, 'B28');
        $data['lot'] = $this->stripPrefix($this->cellText($cover, 'B30'), ['LOT']);
        $data['type_rapport'] = $this->cellText($cover, 'B31');
        $data['code_activite_1'] = $this->cellText($cover, 'C38');
        $data['code_activite_2'] = $this->cellText($cover, 'C39');
        $data['code_activite_3'] = $this->cellText($cover, 'C40');
        $data['code_activite_4'] = $this->cellText($cover, 'C41');
        $data['periode_label'] = $this->cellText($cover, 'D38');
        $data['libelle_activite'] = $this->cellText($cover, 'D42');
        $data['date_os'] = $this->parseCoverDate($cover, 'D46');
        $data['date_notification'] = $this->parseCoverDate($cover, 'D47');
        $data['date_signature'] = $this->parseCoverDate($cover, 'D48');
        $data['delai_prestation'] = $this->parseCoverDate($cover, 'D49', false);
        $data['date_fin_contrat'] = $this->parseCoverDate($cover, 'D50');
        $data['transfert_label'] = $this->cellText($cover, 'F52');

        $regionLabel = $this->stripPrefix($this->cellText($cover, 'B33'), ['REGION']);
        $districtLabel = $this->stripPrefix($this->cellText($cover, 'B34'), ['DISTRICT DE', 'DISTRICT']);
        $terroirLabel = $this->stripPrefix($this->cellText($cover, 'B36'), ['TERROIR']);

        $regionMatch = $this->matchEntity('regions', $regionLabel);
        $districtMatch = $this->matchEntity('districts', $districtLabel);
        $terroirMatch = $this->matchEntity('terroirs', $terroirLabel);

        if ($regionMatch) {
            $data['region_id'] = $regionMatch['id'];
            $matched['region'] = $regionMatch['nom'];
        }
        if ($districtMatch) {
            $data['district_id'] = $districtMatch['id'];
            $matched['district'] = $districtMatch['nom'];
        }
        if ($terroirMatch) {
            $data['terroir_id'] = $terroirMatch['id'];
            $matched['terroir'] = $terroirMatch['nom'];
        }

        $communeMatch = $this->guessCommune($cover, $filename, $data['libelle_activite'], $terroirLabel);
        if ($communeMatch) {
            $data['commune_id'] = $communeMatch['id'];
            $matched['commune'] = $communeMatch['nom'];
        }

        $activiteMatch = $this->guessActivite($filename, $data['libelle_activite'], $data['transfert_label']);
        if ($activiteMatch) {
            $data['activite_id'] = $activiteMatch['id'];
            $matched['activite'] = $activiteMatch['nom'];
        }

        // --- Introduction ---
        if ($intro) {
            $parts = [];
            foreach (['B6', 'B13', 'B14', 'B16'] as $cell) {
                $text = trim($this->cellText($intro, $cell));
                if ($text !== '') {
                    $parts[] = $text;
                }
            }
            if (!empty($parts)) {
                $data['intro_texte'] = implode("\n\n", $parts);
            }
        }

        // --- RECAP TECHN ---
        if ($recap) {
            $data['recap_direction'] = $this->cellText($recap, 'B4');
            $data['recap_titre'] = $this->cellText($recap, 'B7');
            $data['recap_sous_titre'] = $this->cellText($recap, 'B8');
            $data['recap_region'] = $this->cellText($recap, 'B9');
            $data['recap_district'] = $this->cellText($recap, 'E9');
            $data['recap_problemes'] = $this->cellText($recap, 'B28');
            $data['recap_solutions'] = $this->cellText($recap, 'C28');

            $rows = [1 => 13, 2 => 15, 3 => 17, 4 => 19, 5 => 21, 6 => 23];
            foreach ($rows as $index => $row) {
                $data["recap_l{$index}_prevue"] = $this->cellDateText($recap, 'C' . $row);
                $data["recap_l{$index}_effective"] = $this->cellDateText($recap, 'D' . $row);
                $data["recap_l{$index}_obs"] = $this->cellText($recap, 'E' . $row);
            }
        }

        $filled = array_filter($data, fn($v) => $v !== null && $v !== '');

        return [
            'filename' => $filename,
            'sheets_found' => [
                'cover' => $cover ? $cover->getTitle() : null,
                'introduction' => $intro ? $intro->getTitle() : null,
                'recap' => $recap ? $recap->getTitle() : null,
            ],
            'fields' => $data,
            'filled_count' => count($filled),
            'matched_entities' => $matched,
        ];
    }

    private function findSheet($spreadsheet, $title)
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (strcasecmp($sheet->getTitle(), $title) === 0) {
                return $sheet;
            }
        }
        return null;
    }

    private function cellText($sheet, $coordinate)
    {
        if (!$sheet) {
            return '';
        }
        $value = $sheet->getCell($coordinate)->getCalculatedValue();
        if ($value === null) {
            return '';
        }
        return trim((string) $value);
    }

    private function cellDateText($sheet, $coordinate)
    {
        if (!$sheet) {
            return '';
        }
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

    private function parseCoverDate($sheet, $coordinate, $stripColon = true)
    {
        $value = $this->cellDateText($sheet, $coordinate);
        if ($value === '') {
            return '';
        }
        if (!$stripColon) {
            return ltrim($value, ': ');
        }
        if (strncmp($value, ':', 1) === 0) {
            return ltrim($value, ': ');
        }
        return $value;
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

    private function normalize($text)
    {
        $text = mb_strtoupper(trim($text));
        $text = preg_replace('/[^A-Z0-9\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    private function matchEntity($table, $label)
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        $allowed = ['regions', 'districts', 'terroirs', 'communes', 'activites'];
        if (!in_array($table, $allowed, true)) {
            return null;
        }

        $rows = $this->db->query("SELECT id, nom FROM $table ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        $needle = $this->normalize($label);
        if ($needle === '') {
            return null;
        }

        $best = null;
        $bestScore = 0;
        foreach ($rows as $row) {
            $candidate = $this->normalize($row['nom']);
            if ($candidate === $needle) {
                return $row;
            }
            if (strpos($needle, $candidate) !== false || strpos($candidate, $needle) !== false) {
                $score = min(strlen($needle), strlen($candidate));
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $row;
                }
            }
        }

        return $best;
    }

    private function guessCommune($cover, $filename, $libelleActivite, $terroirLabel)
    {
        $candidates = array_filter([
            $this->extractFromFilename($filename),
            $terroirLabel,
            $libelleActivite,
            $this->cellText($cover, 'D42'),
        ]);

        $communes = $this->db->query('SELECT id, nom FROM communes ORDER BY LENGTH(nom) DESC')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($candidates as $text) {
            $match = $this->findNameInText($communes, $text);
            if ($match) {
                return $match;
            }
        }
        return null;
    }

    private function guessActivite($filename, $libelleActivite, $transfertLabel)
    {
        $haystack = implode(' ', array_filter([$filename, $libelleActivite, $transfertLabel]));
        $activites = $this->db->query('SELECT id, nom FROM activites ORDER BY LENGTH(nom) DESC')->fetchAll(PDO::FETCH_ASSOC);
        return $this->findNameInText($activites, $haystack);
    }

    private function extractFromFilename($filename)
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/^CANEVA[_\s]*/i', '', $name);
        $name = preg_replace('/^CANEVAS[_\s]*/i', '', $name);
        return trim(str_replace(['_', '-'], ' ', $name));
    }

    private function findNameInText(array $rows, $text)
    {
        $normalizedText = $this->normalize($text);
        if ($normalizedText === '') {
            return null;
        }

        foreach ($rows as $row) {
            $candidate = $this->normalize($row['nom']);
            if ($candidate !== '' && strpos($normalizedText, $candidate) !== false) {
                return $row;
            }
        }
        return null;
    }
}
