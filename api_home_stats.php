<?php
/**
 * API des statistiques de la page d'accueil
 */
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode([
    'success' => true,
    'stats' => getHomeStats()
]);
