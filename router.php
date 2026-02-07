<?php
/**
 * Routeur pour ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Routes principales
$routes = [
    '/' => 'index.php',
    '/index.php' => 'index.php',
    '/acceuil_fusion.php' => 'acceuil_fusion.php',
    '/acceuil_suivi_paiement.php' => 'acceuil_suivi_paiement.php',
    '/upload_fusion.php' => 'upload_fusion.php',
    '/upload_suivi_paiement.php' => 'upload_suivi_paiement.php',
    '/fusionner.php' => 'fusionner.php',
    '/suivipaiement.php' => 'suivipaiement.php',
    '/getProgress.php' => 'getProgress.php',
    '/check_files.php' => 'check_files.php',
    '/progression.php' => 'progression.php',
    '/message_fusion.php' => 'message_fusion.php',
    '/message_suivi.php' => 'message_suivi.php'
];

// Vérifier si la route existe
if (isset($routes[$uri])) {
    $file = __DIR__ . '/' . $routes[$uri];
    
    if (file_exists($file)) {
        // Inclure le fichier PHP
        include $file;
        return true;
    }
}

// Gestion des fichiers statiques
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf'];
$extension = pathinfo($uri, PATHINFO_EXTENSION);

if (in_array($extension, $staticExtensions)) {
    $filePath = __DIR__ . $uri;
    
    if (file_exists($filePath)) {
        // Définir le type MIME approprié
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf'
        ];
        
        if (isset($mimeTypes[$extension])) {
            header('Content-Type: ' . $mimeTypes[$extension]);
        }
        
        readfile($filePath);
        return true;
    }
}

// Route par défaut - page d'accueil
if ($uri === '/' || $uri === '/index.php' || empty($uri)) {
    include __DIR__ . '/index.php';
    return true;
}

// Erreur 404
http_response_code(404);
echo "<h1>404 - Page non trouvée</h1>";
echo "<p>La page demandée n'existe pas.</p>";
echo "<a href='/'>Retour à l'accueil</a>";
?>
