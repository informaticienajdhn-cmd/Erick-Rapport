<?php
/**
 * Lanceur Simple pour ERICKRAPPORT - Version Portable
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

echo "\n";
echo "🚀 ERICKRAPPORT v2.0.0 - Version Portable\n";
echo "==========================================\n";

// Vérification de PHP
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("❌ PHP 7.4+ requis. Version actuelle: " . PHP_VERSION . "\n");
}

echo "✅ PHP " . PHP_VERSION . " détecté\n";

// Vérification des extensions
$required_extensions = ['zip', 'xml', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "❌ Extensions manquantes: " . implode(', ', $missing_extensions) . "\n";
    echo "💡 Installez ces extensions PHP pour continuer\n";
    exit(1);
}

echo "✅ Extensions PHP vérifiées\n";

// Vérification de Composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "⚠️  Dépendances Composer manquantes\n";
    echo "💡 Installation automatique...\n";
    
    if (file_exists(__DIR__ . '/composer.phar')) {
        system('php composer.phar install --no-dev --optimize-autoloader');
    } elseif (file_exists(__DIR__ . '/composer.json')) {
        echo "❌ Composer non trouvé. Installez les dépendances manuellement:\n";
        echo "   composer install --no-dev --optimize-autoloader\n";
        exit(1);
    }
}

echo "✅ Dépendances vérifiées\n";

// Création des dossiers nécessaires
$directories = ['uploads', 'logs', 'temp'];
foreach ($directories as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) {
        mkdir(__DIR__ . '/' . $dir, 0755, true);
        echo "📁 Dossier créé: $dir\n";
    }
}

// Configuration du serveur
$host = '127.0.0.1';
$port = 8080;

// Vérifier si le port est disponible
$connection = @fsockopen($host, $port, $errno, $errstr, 1);
if ($connection) {
    fclose($connection);
    echo "⚠️  Port $port déjà utilisé. Tentative avec le port 8081...\n";
    $port = 8081;
    
    $connection = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        echo "❌ Ports 8080 et 8081 occupés. Arrêt.\n";
        exit(1);
    }
}

echo "✅ Port $port disponible\n";

echo "\n";
echo "🌐 Serveur démarré sur: http://$host:$port\n";
echo "📁 Répertoire: " . __DIR__ . "\n";
echo "⏹️  Ctrl+C pour arrêter\n";
echo "\n";

// Ouverture automatique du navigateur (Windows)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    sleep(1);
    exec("start http://$host:$port");
}

// Démarrage du serveur PHP intégré
$command = "php -S $host:$port";
exec($command);
?>

