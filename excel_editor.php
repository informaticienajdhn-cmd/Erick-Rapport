<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Chemin du fichier à éditer (par défaut le fichier fourni)
$defaultPath = __DIR__ . '/uploads/RAPPORT INTERMEDIARE ANDAKANA.xlsx';
$filePath = isset($_GET['file']) ? $_GET['file'] : $defaultPath;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_sheet') {
    // Reçoit JSON {sheet: name, data: [[cell,...],[...]]}
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['sheet']) || !isset($input['data'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit;
    }

    try {
        $sheetName = $input['sheet'];
        $data = $input['data'];

        // Charger le fichier source si possible
        if (file_exists($filePath)) {
            $spreadsheet = IOFactory::load($filePath);
        } else {
            $spreadsheet = new Spreadsheet();
        }

        // Retirer la feuille si existe et la recréer propre
        if ($spreadsheet->sheetNameExists($sheetName)) {
            $sheetIndex = $spreadsheet->getIndex($spreadsheet->getSheetByName($sheetName));
            $spreadsheet->removeSheetByIndex($sheetIndex);
        }

        $newSheet = $spreadsheet->createSheet();
        $newSheet->setTitle(substr($sheetName, 0, 31)); // max 31

        // Remplir la feuille
        foreach ($data as $r => $row) {
            foreach ($row as $c => $value) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
                $cell = $col . ($r + 1);
                $newSheet->setCellValue($cell, $value);
            }
        }

        // Sauvegarder dans uploads
        $outName = 'modified_' . preg_replace('/[^0-9A-Za-z_\-]/', '_', $sheetName) . '_' . date('Ymd_His') . '.xlsx';
        $outPath = __DIR__ . '/uploads/' . $outName;
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outPath);

        echo json_encode(['success' => true, 'path' => $outPath, 'name' => $outName]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Page GET: afficher l'éditeur
try {
    if (!file_exists($filePath)) {
        throw new Exception('Fichier non trouvé: ' . htmlspecialchars($filePath));
    }

    $xls = IOFactory::load($filePath);
    $sheetNames = $xls->getSheetNames();
} catch (Exception $e) {
    echo '<h2>Erreur</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Éditeur Excel</title>
    <style>
        table {border-collapse: collapse; margin-bottom: 1rem;}
        td, th {border:1px solid #ccc; padding:6px; min-width:80px}
        td[contenteditable] {background:#fffdf0}
        .sheet {margin-bottom:24px}
        .controls {margin:8px 0}
    </style>
</head>
<body>
<h1>Éditeur Excel</h1>
<p>Fichier : <strong><?php echo htmlspecialchars($filePath); ?></strong></p>

<?php foreach ($sheetNames as $sheetIndex => $sheetName):
    $sheet = $xls->getSheet($sheetIndex);
    $data = $sheet->toArray(null, true, true, true); // associative columns
    // compute numeric-indexed matrix
    $matrix = [];
    $maxRow = max(array_keys($data));
    $cols = [];
    foreach ($data as $r => $row) {
        $rowArr = [];
        foreach ($row as $colLetter => $val) {
            $cols[] = $colLetter;
            $rowArr[] = $val;
        }
        $matrix[] = $rowArr;
    }
?>
<div class="sheet" id="sheet-<?php echo $sheetIndex; ?>">
    <h2>Feuille: <?php echo htmlspecialchars($sheetName); ?></h2>
    <div class="controls">
        <button onclick="saveSheet(<?php echo $sheetIndex; ?>)">Enregistrer cette feuille</button>
        <span id="status-<?php echo $sheetIndex; ?>"></span>
    </div>
    <div class="table-wrap">
        <table data-sheet="<?php echo htmlspecialchars($sheetName); ?>">
            <tbody>
            <?php foreach ($matrix as $r => $row): ?>
                <tr>
                <?php foreach ($row as $c => $cell): ?>
                    <td contenteditable><?php echo htmlspecialchars((string)$cell); ?></td>
                <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<script>
async function saveSheet(index) {
    const container = document.getElementById('sheet-' + index);
    const table = container.querySelector('table');
    const sheetName = table.dataset.sheet;
    const rows = [];
    for (const tr of table.querySelectorAll('tr')) {
        const row = [];
        for (const td of tr.querySelectorAll('td')) {
            row.push(td.innerText.trim());
        }
        rows.push(row);
    }

    const status = document.getElementById('status-' + index);
    status.textContent = 'Envoi en cours...';

    try {
        const res = await fetch('?action=save_sheet', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({sheet: sheetName, data: rows})
        });
        const json = await res.json();
        if (json.success) {
            status.innerHTML = 'Enregistré: <a href="uploads/' + encodeURIComponent(json.name) + '" target="_blank">' + json.name + '</a>';
        } else {
            status.textContent = 'Erreur: ' + (json.message || 'inconnue');
        }
    } catch (err) {
        status.textContent = 'Erreur réseau: ' + err.message;
    }
}
</script>
</body>
</html>
