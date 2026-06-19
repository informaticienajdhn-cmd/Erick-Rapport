<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/CanevasTemplateManager.php';
require_once __DIR__ . '/classes/CanevasAnalyzer.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }
    if (!isset($_FILES['canevas_file'])) {
        throw new Exception('Aucun fichier reçu');
    }

    $manager = new CanevasTemplateManager();
    $register = $manager->registerFromUpload($_FILES['canevas_file'], true);

    $tpl = $manager->getTemplate($register['template_id']);
    $tmp = tempnam(sys_get_temp_dir(), 'canevas_read_') . '.xlsx';
    file_put_contents($tmp, $tpl['fichier']);
    $spreadsheet = IOFactory::load($tmp);
    @unlink($tmp);

    $analyzer = new CanevasAnalyzer();
    $legacy = $analyzer->analyzeSpreadsheet($spreadsheet, $_FILES['canevas_file']['name']);
    $fields = array_merge($register['values'], $legacy['fields']);

    $sheetsFound = ['cover' => null, 'introduction' => null, 'recap' => null];
    foreach ($register['schema']['sheets'] ?? [] as $sheet) {
        if ($sheet['type'] === 'cover') {
            $sheetsFound['cover'] = $sheet['title'];
        } elseif ($sheet['type'] === 'intro') {
            $sheetsFound['introduction'] = $sheet['title'];
        } elseif ($sheet['type'] === 'recap') {
            $sheetsFound['recap'] = $sheet['title'];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Modèle analysé : ' . $register['field_count'] . ' champ(s), formulaire adapté',
        'analysis' => [
            'filename' => $_FILES['canevas_file']['name'],
            'template_id' => $register['template_id'],
            'schema' => $register['schema'],
            'fields' => $fields,
            'filled_count' => count(array_filter($fields, fn($v) => $v !== '' && $v !== null && $v !== [])),
            'matched_entities' => $legacy['matched_entities'],
            'sheets_found' => $sheetsFound,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
