<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();
$activites = $db->getAll('activites');
$communes = $db->getAll('communes');
$terroirs = $db->getAll('terroirs');
$regions = $db->getAll('regions');
$districts = $db->getAll('districts');

$defaultEnTete = "Action des Jeunes pour le Développement de l'Humanité et de la Nature, Organisation pour l'Amélioration et la Gestion de l'Environnement et le Développement durable de l'être Humain\n"
    . "Adresse : En face Est de Restaurent EZAKA AMPASY VANGAINDRANO\n"
    . "Contact : 034 27 566 66  et  032 41 798 19\n"
    . "Email : velonavyphilos@gmail.com";
?>

<div class="pg-page">
    <header>
        <h1>📄 Créer une page de garde</h1>
    </header>

    <div class="container pg-container">
        <p class="pg-intro">Renseignez les informations une seule fois par couple <strong>activité + commune</strong>, puis générez automatiquement le fichier Excel (sans upload manuel).</p>

        <div id="pg-message"></div>

        <div class="form-section pg-import-section">
            <div class="pg-import-header">
                <div>
                    <h3>Importer depuis un canevas existant</h3>
                    <p class="helper-text">Uploadez votre modèle Excel : l'application détecte sa structure et génère un formulaire adapté (page de garde, introduction, RECAP, etc.).</p>
                </div>
            </div>
            <div class="pg-import-box">
                <input type="file" id="pgImportFile" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" hidden>
                <label for="pgImportFile" class="pg-import-file-label">
                    <span id="pgImportFileName">Choisir un fichier Excel (.xlsx)</span>
                </label>
                <button type="button" class="btn-fusionner" id="pgBtnAnalyze" disabled>Analyser et remplir le formulaire</button>
            </div>
            <div id="pg-import-result" class="pg-import-result" hidden></div>
        </div>

        <div class="form-section pg-logos-section">
            <h3>Logos du canevas</h3>
            <p class="helper-text">Ces logos sont appliqués automatiquement à chaque génération (page de garde et RECAP TECHN). Formats : JPG, PNG, GIF — max 2 Mo.</p>
            <div id="pg-logos-grid" class="pg-logos-grid">
                <p class="helper-text">Chargement des logos...</p>
            </div>
        </div>

        <div class="form-section">
            <h3>Configurations existantes</h3>
            <div id="pg-config-list" class="pg-config-list">
                <p class="helper-text">Chargement...</p>
            </div>
        </div>

        <form id="pgForm" class="pg-form">
            <input type="hidden" id="pg_config_id" name="config_id" value="">

            <div class="form-section">
                <h3>Liaison (commune + activité)</h3>
                <div class="form-grid form-grid--two-cols">
                    <div class="form-group">
                        <label for="pg_activite_id">Activité *</label>
                        <select id="pg_activite_id" name="activite_id" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($activites as $a): ?>
                                <option value="<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pg_commune_id">Commune *</label>
                        <select id="pg_commune_id" name="commune_id" required>
                            <option value="">Sélectionner...</option>
                            <?php foreach ($communes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pg_terroir_id">Terroir</label>
                        <select id="pg_terroir_id" name="terroir_id">
                            <option value="">Sélectionner...</option>
                            <?php foreach ($terroirs as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pg_region_id">Région</label>
                        <select id="pg_region_id" name="region_id">
                            <option value="">Sélectionner...</option>
                            <?php foreach ($regions as $r): ?>
                                <option value="<?= (int) $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pg_district_id">District</label>
                        <select id="pg_district_id" name="district_id">
                            <option value="">Sélectionner...</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <input type="hidden" id="pg_template_id" name="template_id" value="">

            <div class="form-section pg-template-bar">
                <div class="form-group">
                    <label for="pgTemplateSelect">Modèle de canevas actif</label>
                    <select id="pgTemplateSelect">
                        <option value="">Chargement...</option>
                    </select>
                    <p id="pgTemplateInfo" class="helper-text">Importez un fichier Excel pour créer un formulaire sur mesure.</p>
                </div>
            </div>

            <div id="pg-dynamic-tabs" class="pg-sheet-tabs"></div>
            <div id="pg-dynamic-panels"></div>

            <div class="button-group pg-actions">
                <button type="button" class="btn-retour" id="pgBtnReset">Nouveau</button>
                <button type="button" class="btn-fusionner" id="pgBtnSave" data-ignore-fusion-state="true">💾 Enregistrer la configuration</button>
                <button type="button" class="btn-telecharger-compact" id="pgBtnGenerate" data-ignore-fusion-state="true">⚡ Générer la page de garde</button>
            </div>
        </form>
    </div>
</div>

<script src="js/pg-dynamic-form.js"></script>
