<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/CanevasTemplateManager.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $manager = new CanevasTemplateManager();
    $manager->bootstrapDefaultTemplate();

    $action = $_GET['action'] ?? $_POST['action'] ?? 'active';

    switch ($action) {
        case 'list':
            echo json_encode(['success' => true, 'templates' => $manager->listTemplates()]);
            break;

        case 'active':
            $tpl = $manager->getActiveTemplate();
            echo json_encode([
                'success' => true,
                'template' => $tpl ? [
                    'id' => $tpl['id'],
                    'nom' => $tpl['nom'],
                    'nom_fichier' => $tpl['nom_fichier'],
                    'structure_hash' => $tpl['structure_hash'],
                    'schema' => $tpl['schema'],
                ] : null,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'get':
            $id = (int) ($_GET['id'] ?? 0);
            $tpl = $manager->getTemplate($id);
            if (!$tpl) {
                throw new Exception('Modèle introuvable');
            }
            echo json_encode([
                'success' => true,
                'template' => [
                    'id' => $tpl['id'],
                    'nom' => $tpl['nom'],
                    'nom_fichier' => $tpl['nom_fichier'],
                    'schema' => $tpl['schema'],
                ],
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'register':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['canevas_file'])) {
                throw new Exception('Fichier requis');
            }
            $result = $manager->registerFromUpload($_FILES['canevas_file'], true);
            echo json_encode([
                'success' => true,
                'message' => 'Modèle enregistré : ' . $result['field_count'] . ' champ(s) détecté(s)',
                'result' => $result,
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'activate':
            $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = (int) ($payload['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID requis');
            }
            $manager->setActive($id);
            $tpl = $manager->getTemplate($id);
            echo json_encode(['success' => true, 'template' => [
                'id' => $tpl['id'],
                'nom' => $tpl['nom'],
                'schema' => $tpl['schema'],
            ]], JSON_UNESCAPED_UNICODE);
            break;

        case 'delete':
            $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = (int) ($payload['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID requis');
            }
            $manager->deleteTemplate($id);
            $manager->bootstrapDefaultTemplate();
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
