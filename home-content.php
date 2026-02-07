<?php
/**
 * Contenu d'accueil dynamique pour ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.1
 */

// Configuration de l'application
$appName = "ERICKRAPPORT";
$version = "2.1";
$author = "SOMBINIAINA Erick";
$currentYear = date('Y');

// Statistiques de l'application (simulées pour l'exemple)
$stats = [
    'totalFusions' => 0,
    'totalFiles' => 0,
    'lastActivity' => 'Aucune activité récente'
];

// Vérifier s'il y a des fichiers uploadés
if (file_exists('uploads/') && is_dir('uploads/')) {
    $files = glob('uploads/*.{xls,xlsx}', GLOB_BRACE);
    $stats['totalFiles'] = count($files);
    
    if ($stats['totalFiles'] > 0) {
        $stats['lastActivity'] = 'Dernière activité: ' . date('d/m/Y H:i', filemtime($files[0]));
    }
}

// Vérifier les logs pour les fusions
if (file_exists('logs/')) {
    $logFiles = glob('logs/*.log');
    $stats['totalFusions'] = count($logFiles);
}
?>

<div class="welcome-container">
    <div class="welcome-header">
        <h2>🚀 Bienvenue sur <?php echo $appName; ?> v<?php echo $version; ?></h2>
        <p class="welcome-subtitle">Votre solution professionnelle pour la gestion des rapports et suivis de paiements</p>
    </div>

    <div class="stats-container">
        <h3>📊 Statistiques de l'Application</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['totalFiles']; ?></div>
                <div class="stat-label">Fichiers Uploadés</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['totalFusions']; ?></div>
                <div class="stat-label">Fusions Réalisées</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $version; ?></div>
                <div class="stat-label">Version Actuelle</div>
            </div>
        </div>
        <p class="last-activity"><?php echo $stats['lastActivity']; ?></p>
    </div>

<style>
/* Bloc statistiques centré et harmonisé avec l'ancien bloc bienvenue */
.stats-container {
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}
.main-container {
    min-height: calc(100vh - 70px); /* 70px = hauteur approx. du footer */
    display: flex;
    flex-direction: row;
    align-items: flex-start;
}
.stats-container {
    max-width: 800px;
    margin: 40px auto 0 auto;
    padding: 30px 32px 40px 32px;
    background: var(--white, #fff);
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(30,64,175,0.07);
    text-align: center;
    min-height: 320px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.footer {
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100vw;
    background: var(--accent-grey-dark, #e5e7eb);
    border-top: 1.5px solid var(--glass-border, #c7d2fe);
    padding: 15px 0;
    text-align: center;
    font-family: inherit;
    color: var(--primary-blue-dark, #1e40af);
    box-shadow: none;
    z-index: 10;
}
@media (max-width: 700px) {
    .stats-container {
        min-height: 220px;
        padding: 16px 4vw 24px 4vw;
    }
}
@media (max-width: 480px) {
    .stats-container {
        min-height: 120px;
        padding: 8px 2vw 16px 2vw;
    }
}
}


body, html {
    min-height: 100vh;
}

.main-container {
    min-height: 100vh;
    display: flex;
    flex-direction: row;
}

.stats-container h3 {
    font-size: 1.5em;
    margin-bottom: 24px;
    color: var(--primary-blue-dark, #1e40af);
}
.stats-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 24px;
    margin-bottom: 18px;
}
.stat-item {
    flex: 1 1 120px;
    min-width: 120px;
    max-width: 180px;
    background: var(--accent-grey-dark, #e5e7eb);
    border-radius: 12px;
    padding: 18px 8px;
    box-shadow: 0 2px 8px rgba(37,99,235,0.08);
    margin: 0 4px;
}
.stat-number {
    font-size: 2.2em;
    font-weight: 700;
    color: var(--primary-blue, #2563eb);
    margin-bottom: 6px;
}
.stat-label {
    font-size: 1em;
    color: var(--primary-blue-dark, #1e40af);
    opacity: 0.85;
}
.last-activity {
    margin-top: 18px;
    font-size: 1em;
    color: #555;
}
@media (max-width: 700px) {
    .stats-container {
        max-width: 98vw;
        padding: 16px 4vw 24px 4vw;
    }
    .stats-grid {
        gap: 12px;
    }
    .stat-item {
        min-width: 90px;
        max-width: 100%;
        padding: 14px 4px;
    }
    .stat-number {
        font-size: 1.5em;
    }
}
@media (max-width: 480px) {
    .stats-container {
        padding: 8px 2vw 16px 2vw;
    }
    .stats-grid {
        flex-direction: column;
        gap: 8px;
    }
    .stat-item {
        min-width: unset;
        padding: 10px 2px;
    }
    .stat-number {
        font-size: 1.1em;
    }
}
</style>



</div>

<style>
/* Styles pour le contenu d'accueil */
.welcome-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.welcome-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(145deg, var(--glass-bg), rgba(0, 255, 255, 0.02));
    border: 2px solid var(--glass-border);
    border-radius: 20px;
    backdrop-filter: blur(20px);
}

.welcome-header h2 {
    font-family: 'Orbitron', monospace;
    font-size: 2.5em;
    color: var(--neon-cyan);
    margin: 0 0 15px 0;
    text-shadow: var(--glow-cyan);
    animation: titleGlow 3s ease-in-out infinite;
}

.welcome-subtitle {
    font-size: 1.2em;
    color: var(--neon-cyan);
    margin: 0;
    opacity: 0.9;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin: 40px 0;
}

.feature-card {
    background: linear-gradient(145deg, var(--glass-bg), rgba(0, 255, 255, 0.02));
    border: 2px solid var(--glass-border);
    border-radius: 15px;
    padding: 25px;
    backdrop-filter: blur(20px);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.1), transparent);
    transition: left 0.5s ease;
}

.feature-card:hover::before {
    left: 100%;
}

.feature-card:hover {
    border-color: var(--neon-cyan);
    box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
    transform: translateY(-5px);
}

.feature-icon {
    font-size: 3em;
    margin-bottom: 15px;
    text-align: center;
}

.feature-card h3 {
    font-family: 'Orbitron', monospace;
    color: var(--neon-cyan);
    margin: 0 0 15px 0;
    font-size: 1.3em;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.feature-card p {
    color: var(--neon-cyan);
    line-height: 1.6;
    margin: 0 0 15px 0;
    opacity: 0.9;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    color: var(--neon-green);
    margin: 8px 0;
    font-size: 0.9em;
}

.stats-container {
    background: linear-gradient(145deg, var(--glass-bg), rgba(0, 255, 255, 0.02));
    border: 2px solid var(--glass-border);
    border-radius: 15px;
    padding: 30px;
    margin: 40px 0;
    backdrop-filter: blur(20px);
    text-align: center;
}

.stats-container h3 {
    font-family: 'Orbitron', monospace;
    color: var(--neon-cyan);
    margin: 0 0 25px 0;
    font-size: 1.5em;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-item {
    background: var(--darker-bg);
    border: 1px solid var(--neon-cyan);
    border-radius: 10px;
    padding: 20px;
    transition: all var(--transition-normal);
}

.stat-item:hover {
    border-color: var(--neon-green);
    box-shadow: 0 0 20px rgba(57, 255, 20, 0.3);
}

.stat-number {
    font-family: 'Orbitron', monospace;
    font-size: 2.5em;
    font-weight: 900;
    color: var(--neon-green);
    margin: 0 0 10px 0;
    text-shadow: var(--glow-green);
}

.stat-label {
    color: var(--neon-cyan);
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.last-activity {
    color: var(--neon-cyan);
    font-style: italic;
    margin: 20px 0 0 0;
    opacity: 0.8;
}

.quick-actions {
    margin: 40px 0;
}

.quick-actions h3 {
    font-family: 'Orbitron', monospace;
    color: var(--neon-cyan);
    margin: 0 0 25px 0;
    font-size: 1.5em;
    text-transform: uppercase;
    letter-spacing: 2px;
    text-align: center;
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.action-btn {
    background: linear-gradient(145deg, var(--darker-bg), var(--dark-bg));
    border: 2px solid var(--neon-cyan);
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.action-btn:hover {
    border-color: var(--neon-green);
    box-shadow: var(--glow-green);
    transform: translateY(-3px);
}

.btn-icon {
    font-size: 2.5em;
    margin-bottom: 10px;
}

.btn-text {
    font-family: 'Orbitron', monospace;
    font-size: 1.2em;
    font-weight: 700;
    color: var(--neon-cyan);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.btn-desc {
    font-size: 0.9em;
    color: var(--neon-cyan);
    opacity: 0.8;
    line-height: 1.4;
}

.help-section {
    background: linear-gradient(145deg, var(--glass-bg), rgba(0, 255, 255, 0.02));
    border: 2px solid var(--glass-border);
    border-radius: 15px;
    padding: 30px;
    margin: 40px 0;
    backdrop-filter: blur(20px);
}

.help-section h3 {
    font-family: 'Orbitron', monospace;
    color: var(--neon-cyan);
    margin: 0 0 25px 0;
    font-size: 1.5em;
    text-transform: uppercase;
    letter-spacing: 2px;
    text-align: center;
}

.help-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.help-item {
    background: var(--darker-bg);
    border: 1px solid var(--neon-cyan);
    border-radius: 10px;
    padding: 20px;
    transition: all var(--transition-normal);
}

.help-item:hover {
    border-color: var(--neon-green);
    box-shadow: 0 0 15px rgba(57, 255, 20, 0.2);
}

.help-item strong {
    color: var(--neon-green);
    display: block;
    margin-bottom: 10px;
    font-size: 1.1em;
}

.help-item p {
    color: var(--neon-cyan);
    margin: 0;
    line-height: 1.5;
    opacity: 0.9;
}

.footer-info {
    text-align: center;
    margin-top: 40px;
    padding: 20px;
    background: linear-gradient(145deg, var(--darker-bg), var(--dark-bg));
    border: 1px solid var(--neon-cyan);
    border-radius: 10px;
}

.footer-info p {
    color: var(--neon-cyan);
    margin: 10px 0;
    opacity: 0.9;
}

.social-links {
    margin-top: 15px;
}

.social-link {
    color: var(--neon-cyan);
    text-decoration: none;
    margin: 0 15px;
    padding: 8px 15px;
    border: 1px solid var(--neon-cyan);
    border-radius: 5px;
    transition: all var(--transition-normal);
    display: inline-block;
    font-size: 0.9em;
}

.social-link:hover {
    background: var(--neon-cyan);
    color: var(--dark-bg);
    box-shadow: 0 0 15px var(--neon-cyan);
    transform: translateY(-2px);
}

/* Responsive */
@media screen and (max-width: 768px) {
    .welcome-header h2 {
        font-size: 2em;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons {
        grid-template-columns: 1fr;
    }
    
    .help-content {
        grid-template-columns: 1fr;
    }
}

@media screen and (max-width: 480px) {
    .welcome-container {
        padding: 10px;
    }
    
    .welcome-header {
        padding: 20px;
    }
    
    .welcome-header h2 {
        font-size: 1.5em;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
