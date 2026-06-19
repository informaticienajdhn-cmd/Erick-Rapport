<?php
/**
 * API upload / liste / aperçu des logos canevas
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/CanevasLogoManager.php';

try {
    $manager = new CanevasLogoManager();
    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

    switch ($action) {
        case 'list':
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'logos' => array_values($manager->listLogos()),
            ]);
            break;

        case 'preview':
            $slot = $_GET['slot'] ?? '';
            $path = $manager->getSlotPath($slot);
            if (!$path) {
                http_response_code(404);
                exit;
            }
            $mime = mime_content_type($path) ?: 'image/png';
            header('Content-Type: ' . $mime);
            header('Cache-Control: no-store, no-cache, must-revalidate');
            readfile($path);
            break;

        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            header('Content-Type: application/json; charset=utf-8');
            $slot = $_POST['slot'] ?? '';
            if (!isset($_FILES['logo_file'])) {
                throw new Exception('Aucun fichier reçu');
            }
            $result = $manager->upload($slot, $_FILES['logo_file']);
            echo json_encode([
                'success' => true,
                'message' => 'Logo enregistré',
                'logo' => $result,
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            header('Content-Type: application/json; charset=utf-8');
            $payload = json_decode(file_get_contents('php://input'), true);
            if (!is_array($payload)) {
                $payload = $_POST;
            }
            $slot = $payload['slot'] ?? '';
            $manager->delete($slot);
            echo json_encode([
                'success' => true,
                'message' => 'Logo supprimé',
            ]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    if (($action ?? '') === 'preview') {
        http_response_code(404);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
