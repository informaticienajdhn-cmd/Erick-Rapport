<?php
/**
 * Contenu d'accueil dynamique pour ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.1
 */

require_once __DIR__ . '/config.php';

$appName = "ERICKRAPPORT";
$version = "2.1";
$stats = getHomeStats();
?>

<div class="welcome-container">
    <div class="welcome-header">
        <h2 class="welcome-title">
            <span class="welcome-rocket" aria-hidden="true">🚀</span>
            Bienvenue sur <span class="welcome-app-name"><?php echo $appName; ?></span> v<?php echo $version; ?>
        </h2>
        <p class="welcome-subtitle">Votre solution professionnelle pour la gestion des rapports et suivis de paiements</p>
    </div>

    <div class="stats-container">
        <h3>📊 Statistiques de l'Application</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number" id="stat-uploaded-files"><?php echo (int) $stats['uploadedFiles']; ?></div>
                <div class="stat-label">Fichiers Uploadés</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="stat-total-fusions"><?php echo (int) $stats['totalFusions']; ?></div>
                <div class="stat-label">Fusions Réalisées</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="stat-version"><?php echo htmlspecialchars($stats['version']); ?></div>
                <div class="stat-label">Version Actuelle</div>
            </div>
        </div>
        <p class="last-activity" id="stat-last-activity"><?php echo htmlspecialchars($stats['lastActivity']); ?></p>
    </div>
</div>
