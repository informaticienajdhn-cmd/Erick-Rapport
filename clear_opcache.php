<?php
/**
 * Script pour forcer le vidage de l'OPcache PHP
 */

error_log("[clear_opcache.php] Tentative de vidage de l'OPcache");

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        error_log("[clear_opcache.php] OPcache vidé avec succès");
        echo "✅ OPcache vidé avec succès\n";
    } else {
        error_log("[clear_opcache.php] Échec du vidage de l'OPcache");
        echo "❌ Échec du vidage de l'OPcache\n";
    }
} else {
    error_log("[clear_opcache.php] opcache_reset() non disponible");
    echo "⚠️ opcache_reset() non disponible\n";
    echo "OPcache peut être désactivé ou la fonction est interdite\n";
}

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "\n--- Statut OPcache ---\n";
    echo "Enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    echo "Memory Usage: " . $status['memory_usage']['used_memory'] . " bytes\n";
}

echo "\nVisitez une page du site pour vérifier les nouveaux logs.\n";
?>
