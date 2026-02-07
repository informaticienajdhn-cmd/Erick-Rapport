<?php
header('Content-Type: application/json');

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$allowedExtensions = ['xls', 'xlsx'];
$messages = [];

if (isset($_FILES['excel_files'])) {
    foreach ($_FILES['excel_files']['name'] as $key => $fileName) {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $filePath = $uploadDir . basename($fileName);

        if (!in_array($fileExtension, $allowedExtensions)) {
            $messages[] = ["status" => "error", "message" => "❌ Fichier **" . htmlspecialchars($fileName) . "** non autorisé."];
            continue;
        }

        // ✅ Ajout du symbole clignotant dans le message de succès
        $messages[] = move_uploaded_file($_FILES['excel_files']['tmp_name'][$key], $filePath)
            ? ["status" => "success", "message" => "<span class='success-icon'>✅</span> Fichier **" . htmlspecialchars($fileName) . "** téléchargé avec succès."]
            : ["status" => "error", "message" => "<span class='echec-icon'>❌</span> Échec du téléchargement du fichier **" . htmlspecialchars($fileName) . "**."];
    }
}

echo json_encode($messages);
?>
