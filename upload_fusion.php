<?php
/**
 * Gestionnaire d'upload sécurisé pour les fichiers Excel
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

// Chargement de la configuration et des classes
require_once 'config.php';
require_once 'classes/ErrorHandler.php';
require_once 'classes/FileValidator.php';

// Initialisation du gestionnaire d'erreurs
ErrorHandler::init();

// Démarrage de la session sécurisée
session_start();

try {
    // Vérification de la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode HTTP non autorisée.');
    }


    // Vérification et création du dossier uploads si besoin
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0777, true)) {
            throw new Exception('Impossible de créer le dossier uploads. Vérifiez les permissions.');
        }
    }
    if (!is_writable(UPLOAD_DIR)) {
        throw new Exception('Le dossier uploads n\'est pas accessible en écriture.');
    }

    // Nettoyer les anciens fichiers Excel avant un nouvel upload
    $existingFiles = array_diff(scandir(UPLOAD_DIR), ['.', '..']);
    foreach ($existingFiles as $existingFile) {
        $extension = strtolower(pathinfo($existingFile, PATHINFO_EXTENSION));
        if (in_array($extension, ALLOWED_EXTENSIONS)) {
            @unlink(UPLOAD_DIR . $existingFile);
        }
    }

    // Vérification de la présence de fichiers
    if (!isset($_FILES['excel_files']) || empty($_FILES['excel_files']['name'])) {
        throw new Exception('Aucun fichier n\'a été sélectionné.');
    }

    $messages = [];
    $uploadedCount = 0;
    
    // Normalisation des données de fichiers (gestion fichier unique vs multiple)
    $fileNames = is_array($_FILES['excel_files']['name']) ? $_FILES['excel_files']['name'] : [$_FILES['excel_files']['name']];
    $fileTypes = is_array($_FILES['excel_files']['type']) ? $_FILES['excel_files']['type'] : [$_FILES['excel_files']['type']];
    $fileTmpNames = is_array($_FILES['excel_files']['tmp_name']) ? $_FILES['excel_files']['tmp_name'] : [$_FILES['excel_files']['tmp_name']];
    $fileErrors = is_array($_FILES['excel_files']['error']) ? $_FILES['excel_files']['error'] : [$_FILES['excel_files']['error']];
    $fileSizes = is_array($_FILES['excel_files']['size']) ? $_FILES['excel_files']['size'] : [$_FILES['excel_files']['size']];
    
    $totalFiles = count($fileNames);

    // Vérification du nombre maximum de fichiers
    if ($totalFiles > MAX_UPLOAD_FILES) {
        throw new Exception("Nombre maximum de fichiers dépassé. Maximum autorisé : " . MAX_UPLOAD_FILES);
    }

    // Traitement de chaque fichier
    for ($i = 0; $i < $totalFiles; $i++) {
        try {
            // Préparation des données du fichier
            $file = [
                'name' => $fileNames[$i],
                'type' => $fileTypes[$i],
                'tmp_name' => $fileTmpNames[$i],
                'error' => $fileErrors[$i],
                'size' => $fileSizes[$i]
            ];

            // Validation du fichier
            FileValidator::validateUploadedFile($file);

            // Génération d'un nom de fichier sécurisé
            $secureFileName = FileValidator::generateSecureFileName($file['name']);
            $filePath = UPLOAD_DIR . $secureFileName;

            // Vérification des doublons
            if (file_exists($filePath)) {
                // Le fichier existe déjà, vérifier s'il est identique
                $uploadedFileHash = md5_file($file['tmp_name']);
                $existingFileHash = md5_file($filePath);
                
                if ($uploadedFileHash === $existingFileHash) {
                    throw new Exception("Le fichier '" . htmlspecialchars($file['name']) . "' existe déjà et est identique. Upload annulé pour éviter les doublons.");
                }
                // Si les hashes sont différents, on laisse passer (fichier différent, même nom)
            }

            // Déplacement du fichier vers le dossier de destination
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $uploadedCount++;
                $messages[] = [
                    "status" => "success",
                    "message" => "<span class='success-icon'>✅</span> Fichier **" . htmlspecialchars($file['name']) . "** téléchargé avec succès.",
                    "filename" => $secureFileName,
                    "original_name" => $file['name']
                ];
                
                // Log de l'upload réussi
                ErrorHandler::logError("Fichier uploadé avec succès : " . $file['name'] . " -> " . $secureFileName);
            } else {
                throw new Exception("Impossible de déplacer le fichier vers le dossier de destination.");
            }

        } catch (Exception $e) {
            $messages[] = [
                "status" => "error",
                "message" => "<span class='echec-icon'>❌</span> " . $e->getMessage() . " (Fichier: " . htmlspecialchars($file['name']) . ")",
                "filename" => isset($file['name']) ? $file['name'] : 'inconnu'
            ];
            
            // Log de l'erreur
            ErrorHandler::logError("Erreur upload fichier : " . $e->getMessage());
        }
    }

    // Réponse finale
    $response = [
        'success' => $uploadedCount > 0,
        'messages' => $messages,
        'uploaded_count' => $uploadedCount,
        'total_files' => $totalFiles,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    ErrorHandler::jsonError($e->getMessage());
}
?>
