<?php
/**
 * API configuration et génération des pages de garde
 */
require_once 'config.php';
require_once 'classes/CanevasGenerator.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $generator = new CanevasGenerator();
    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

    switch ($action) {
        case 'list':
            echo json_encode([
                'success' => true,
                'configs' => $generator->listConfigs(),
            ]);
            break;

        case 'get':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID requis');
            }
            $config = $generator->getConfig($id);
            if (!$config) {
                throw new Exception('Configuration introuvable');
            }
            echo json_encode(['success' => true, 'config' => $config]);
            break;

        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = $_POST;
            }
            $id = $generator->saveConfig($payload);
            echo json_encode([
                'success' => true,
                'message' => 'Configuration enregistrée',
                'config_id' => $id,
            ]);
            break;

        case 'generate':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = $_POST;
            }

            $configId = null;
            if (!empty($payload['config_id'])) {
                $configId = (int) $payload['config_id'];
            } else {
                $configId = $generator->saveConfig($payload);
            }

            $result = $generator->generateAndStore($configId);
            echo json_encode([
                'success' => true,
                'message' => 'Page de garde générée et enregistrée',
                'result' => $result,
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            $payload = json_decode(file_get_contents('php://input'), true);
            $id = (int) ($payload['id'] ?? $_POST['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID requis');
            }
            $generator->deleteConfig($id);
            echo json_encode(['success' => true, 'message' => 'Configuration supprimée']);
            break;

        case 'defaults':
            echo json_encode([
                'success' => true,
                'defaults' => $generator->getTemplateDefaults(),
            ]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
