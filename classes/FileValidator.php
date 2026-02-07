<?php
/**
 * Validateur de fichiers sécurisé
 * @author SOMBINIAINA Erick
 */

class FileValidator
{
    /**
     * Valide un fichier uploadé
     */
    public static function validateUploadedFile($file)
    {
        // Vérifier si le fichier a été uploadé sans erreur
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(self::getUploadErrorMessage($file['error']));
        }

        // Vérifier la taille du fichier
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception(ERROR_MESSAGES['FILE_TOO_LARGE']);
        }

        // Vérifier l'extension du fichier
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            throw new Exception(ERROR_MESSAGES['INVALID_FILE_TYPE']);
        }

        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            throw new Exception(ERROR_MESSAGES['INVALID_MIME_TYPE']);
        }

        // Vérifier que le fichier est bien un fichier uploadé
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception(ERROR_MESSAGES['SECURITY_VIOLATION']);
        }

        return true;
    }

    /**
     * Génère un nom de fichier sécurisé
     */
    public static function generateSecureFileName($originalName)
    {
        // Nettoyer le nom de fichier
        $pathInfo = pathinfo($originalName);
        $baseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $pathInfo['filename']);
        $extension = strtolower($pathInfo['extension']);
        
        // Ajouter un timestamp pour éviter les conflits
        $timestamp = date('Y-m-d_H-i-s');
        $randomString = bin2hex(random_bytes(4));
        
        return $baseName . '_' . $timestamp . '_' . $randomString . '.' . $extension;
    }

    /**
     * Retourne le message d'erreur correspondant au code d'erreur d'upload
     */
    private static function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ERROR_MESSAGES['FILE_TOO_LARGE'];
            case UPLOAD_ERR_PARTIAL:
                return '❌ Le fichier n\'a été que partiellement téléchargé.';
            case UPLOAD_ERR_NO_FILE:
                return '❌ Aucun fichier n\'a été téléchargé.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '❌ Dossier temporaire manquant.';
            case UPLOAD_ERR_CANT_WRITE:
                return '❌ Impossible d\'écrire le fichier sur le disque.';
            case UPLOAD_ERR_EXTENSION:
                return '❌ Extension PHP a arrêté le téléchargement.';
            default:
                return ERROR_MESSAGES['UPLOAD_FAILED'];
        }
    }

    /**
     * Valide et nettoie un chemin de fichier
     */
    public static function sanitizePath($path)
    {
        // Supprimer les caractères dangereux
        $path = str_replace(['../', '..\\', '//', '\\\\'], '', $path);
        
        // Nettoyer le chemin
        $path = preg_replace('/[^a-zA-Z0-9._\/-]/', '_', $path);
        
        return $path;
    }
}
?>
