<?php
/**
 * Configuration CSS pour ERICKRAPPORT
 * Permet de basculer entre les versions optimisées et minifiées
 * 
 * @author SOMBINIAINA Erick
 * @version 2.1
 */

// Configuration de l'environnement
define('CSS_ENVIRONMENT', 'development'); // 'development' ou 'production'

// Configuration des versions CSS
$cssConfig = [
    'development' => [
        'file' => 'styles-optimized.css',
        'version' => '2.1-dev',
        'minified' => false
    ],
    'production' => [
        'file' => 'styles-minified.css',
        'version' => '2.1-prod',
        'minified' => true
    ]
];

// Fonction pour obtenir la configuration CSS
function getCSSConfig() {
    global $cssConfig;
    $env = defined('CSS_ENVIRONMENT') ? CSS_ENVIRONMENT : 'development';
    return $cssConfig[$env];
}

// Fonction pour générer la balise link CSS
function getCSSLink() {
    $config = getCSSConfig();
    $version = $config['version'];
    $file = $config['file'];
    
    // Ajouter un timestamp en développement pour éviter le cache
    if ($config['minified'] === false) {
        $version .= '-' . time();
    }
    
    return "<link rel=\"stylesheet\" href=\"{$file}?v={$version}\">";
}

// Fonction pour obtenir les statistiques CSS
function getCSSStats() {
    $config = getCSSConfig();
    $filePath = __DIR__ . '/' . $config['file'];
    
    if (!file_exists($filePath)) {
        return ['error' => 'Fichier CSS non trouvé'];
    }
    
    $content = file_get_contents($filePath);
    $size = filesize($filePath);
    $lines = substr_count($content, "\n");
    
    return [
        'file' => $config['file'],
        'size' => $size,
        'size_kb' => round($size / 1024, 2),
        'lines' => $lines,
        'minified' => $config['minified'],
        'version' => $config['version']
    ];
}

// Fonction pour basculer l'environnement
function switchCSSEnvironment($environment) {
    if (!in_array($environment, ['development', 'production'])) {
        throw new InvalidArgumentException('Environnement invalide. Utilisez "development" ou "production".');
    }
    
    // Mettre à jour la constante (nécessite un redémarrage du serveur)
    define('CSS_ENVIRONMENT', $environment);
    return "Environnement CSS basculé vers : {$environment}";
}

// Fonction pour optimiser automatiquement
function autoOptimizeCSS() {
    $devFile = __DIR__ . '/styles-optimized.css';
    $prodFile = __DIR__ . '/styles-minified.css';
    
    if (!file_exists($devFile)) {
        return ['error' => 'Fichier de développement non trouvé'];
    }
    
    // Lire le fichier de développement
    $content = file_get_contents($devFile);
    
    // Minification basique
    $minified = preg_replace('/\/\*.*?\*\//s', '', $content); // Supprimer les commentaires
    $minified = preg_replace('/\s+/', ' ', $minified); // Remplacer les espaces multiples
    $minified = str_replace(['; ', ' {', '} ', ' {', '}'], [';', '{', '}', '{', '}'], $minified); // Optimiser
    $minified = trim($minified);
    
    // Écrire le fichier minifié
    $result = file_put_contents($prodFile, $minified);
    
    if ($result === false) {
        return ['error' => 'Erreur lors de l\'écriture du fichier minifié'];
    }
    
    return [
        'success' => true,
        'original_size' => filesize($devFile),
        'minified_size' => filesize($prodFile),
        'compression_ratio' => round((1 - filesize($prodFile) / filesize($devFile)) * 100, 2) . '%'
    ];
}

// Fonction pour valider le CSS
function validateCSS($file = null) {
    $config = getCSSConfig();
    $filePath = $file ?: __DIR__ . '/' . $config['file'];
    
    if (!file_exists($filePath)) {
        return ['error' => 'Fichier CSS non trouvé'];
    }
    
    $content = file_get_contents($filePath);
    $errors = [];
    $warnings = [];
    
    // Vérifications basiques
    if (strpos($content, 'var(--') === false) {
        $warnings[] = 'Aucune variable CSS détectée';
    }
    
    if (strpos($content, '@media') === false) {
        $warnings[] = 'Aucune requête média détectée';
    }
    
    if (strpos($content, '@keyframes') === false) {
        $warnings[] = 'Aucune animation détectée';
    }
    
    // Vérifier les accolades non fermées
    $openBraces = substr_count($content, '{');
    $closeBraces = substr_count($content, '}');
    if ($openBraces !== $closeBraces) {
        $errors[] = "Accolades non équilibrées (ouvertes: {$openBraces}, fermées: {$closeBraces})";
    }
    
    // Vérifier les points-virgules manquants
    $semicolons = substr_count($content, ';');
    $properties = preg_match_all('/[a-zA-Z-]+:\s*[^;]+/', $content);
    if ($semicolons < $properties) {
        $warnings[] = 'Points-virgules potentiellement manquants';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
        'file' => basename($filePath)
    ];
}

// Fonction pour générer un rapport CSS
function generateCSSReport() {
    $stats = getCSSStats();
    $validation = validateCSS();
    
    return [
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => defined('CSS_ENVIRONMENT') ? CSS_ENVIRONMENT : 'development',
        'statistics' => $stats,
        'validation' => $validation,
        'recommendations' => [
            'Utilisez la version minifiée en production',
            'Activez la compression gzip sur votre serveur',
            'Utilisez un CDN pour les polices Google Fonts',
            'Considérez l\'utilisation de CSS custom properties pour la thématisation'
        ]
    ];
}

// Exemple d'utilisation
if (basename($_SERVER['PHP_SELF']) === 'css-config.php') {
    header('Content-Type: application/json');
    echo json_encode(generateCSSReport(), JSON_PRETTY_PRINT);
}
?>
