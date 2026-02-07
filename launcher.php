<?php
/**
 * Lanceur Portable pour ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

// Vérification de PHP
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("❌ PHP 7.4 ou supérieur requis. Version actuelle : " . PHP_VERSION);
}

// Configuration du serveur PHP intégré
$host = '127.0.0.1';
$port = 8080;
$root = __DIR__;
$router = __DIR__ . '/router.php';

echo "🚀 Lancement d'ERICKRAPPORT v2.0.0\n";
echo "=====================================\n";
echo "📁 Répertoire : $root\n";
echo "🌐 URL : http://$host:$port\n";
echo "📝 Logs : $root/logs/\n";
echo "=====================================\n";
echo "✅ Serveur démarré ! Ouvrez votre navigateur à l'adresse ci-dessus.\n";
echo "⏹️  Appuyez sur Ctrl+C pour arrêter le serveur.\n\n";

// Démarrer le serveur PHP intégré
$command = "php -S $host:$port -t $root $router";

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows
    $command = "start /B $command";
    pclose(popen($command, 'r'));
    
    // Ouvrir automatiquement le navigateur
    sleep(2);
    exec("start http://$host:$port");
} else {
    // Linux/Mac
    exec($command);
}
?>
