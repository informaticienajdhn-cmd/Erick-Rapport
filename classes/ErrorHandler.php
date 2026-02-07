<?php
/**
 * Gestionnaire d'erreurs centralisé
 * @author SOMBINIAINA Erick
 */

class ErrorHandler
{
    private static $logFile;

    public static function init()
    {
        self::$logFile = LOG_DIR . 'error_' . date('Y-m-d') . '.log';
        
        // Configuration du gestionnaire d'erreurs PHP
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Gère les erreurs PHP
     */
    public static function handleError($severity, $message, $file, $line)
    {
        $errorMessage = sprintf(
            "[%s] ERROR: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $message,
            $file,
            $line
        );
        
        self::logError($errorMessage);
        
        // En mode développement, afficher l'erreur
        if (defined('DEBUG') && DEBUG === true) {
            echo $errorMessage;
        }
    }

    /**
     * Gère les exceptions non capturées
     */
    public static function handleException($exception)
    {
        $errorMessage = sprintf(
            "[%s] EXCEPTION: %s in %s on line %d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        self::logError($errorMessage);
        
        // Retourner une réponse JSON appropriée
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => 'Une erreur interne est survenue. Veuillez réessayer.'
        ]);
        exit;
    }

    /**
     * Enregistre une erreur dans le fichier de log
     */
    public static function logError($message)
    {
        if (self::$logFile) {
            error_log($message . PHP_EOL, 3, self::$logFile);
        }
    }

    /**
     * Retourne une réponse d'erreur JSON formatée
     */
    public static function jsonError($message, $code = 400)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    /**
     * Retourne une réponse de succès JSON formatée
     */
    public static function jsonSuccess($message, $data = null)
    {
        header('Content-Type: application/json');
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
}
?>
