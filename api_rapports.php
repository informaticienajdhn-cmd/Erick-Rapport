<?php
/**
 * API pour gérer les rapports enregistrés
 */
require_once 'config.php';
require_once 'classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            // Lister tous les rapports
            $stmt = $db->getConnection()->query("
                SELECT r.id, r.nom, r.commune_id, r.activite_id, c.nom AS commune, a.nom AS activite, r.created_at
                FROM rapports_enregistres r
                LEFT JOIN communes c ON r.commune_id = c.id
                LEFT JOIN activites a ON r.activite_id = a.id
                ORDER BY r.created_at DESC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'save':
            // Enregistrer un nouveau rapport
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $nom = $_POST['nom'] ?? '';
            $commune_id = $_POST['commune_id'] ?? '';
            $activite_id = $_POST['activite_id'] ?? '';
            
            if (!$nom || !$commune_id || !$activite_id) {
                throw new Exception('Paramètres manquants');
            }

            if (empty($_FILES['fichier'])) {
                throw new Exception('Fichier manquant');
            }

            $fichier = file_get_contents($_FILES['fichier']['tmp_name']);
            
            $stmt = $db->getConnection()->prepare("
                INSERT INTO rapports_enregistres (nom, commune_id, activite_id, fichier)
                VALUES (:nom, :commune_id, :activite_id, :fichier)
            ");
            
            // Utiliser bindParam avec PDO::PARAM_LOB pour les données binaires
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':commune_id', $commune_id, PDO::PARAM_INT);
            $stmt->bindParam(':activite_id', $activite_id, PDO::PARAM_INT);
            $stmt->bindParam(':fichier', $fichier, PDO::PARAM_LOB);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Rapport enregistré avec succès']);
            break;

        case 'download':
            // Télécharger un rapport
            if (empty($_GET['id'])) {
                throw new Exception('ID manquant');
            }

            $stmt = $db->getConnection()->prepare("
                SELECT nom, fichier FROM rapports_enregistres WHERE id = :id
            ");
            $stmt->execute([':id' => $_GET['id']]);
            $rapport = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rapport) {
                throw new Exception('Rapport non trouvé');
            }

            if (empty($rapport['fichier'])) {
                throw new Exception('Fichier vide ou corrompu');
            }

            // Vérifier que c'est un vrai fichier XLSX (commence par PK)
            $fileContent = $rapport['fichier'];
            if (strpos($fileContent, 'PK') !== 0) {
                throw new Exception('Le fichier enregistré semble corrompu (pas de signature XLSX)');
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $rapport['nom']) . '.xlsx"');
            header('Content-Length: ' . strlen($fileContent));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo $fileContent;
            exit;

        case 'rename':
            // Renommer un rapport
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $id = $_POST['id'] ?? '';
            $nouveau_nom = $_POST['nouveau_nom'] ?? '';

            if (!$id || !$nouveau_nom) {
                throw new Exception('Paramètres manquants');
            }

            $stmt = $db->getConnection()->prepare("
                UPDATE rapports_enregistres SET nom = :nom WHERE id = :id
            ");
            $stmt->execute([':nom' => $nouveau_nom, ':id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Rapport renommé avec succès']);
            break;

        case 'delete':
            // Supprimer un rapport
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }

            $id = $_POST['id'] ?? '';

            if (!$id) {
                throw new Exception('ID manquant');
            }

            $stmt = $db->getConnection()->prepare("
                DELETE FROM rapports_enregistres WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Rapport supprimé avec succès']);
            break;

        default:
            throw new Exception('Action non reconnue');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
