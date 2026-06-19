<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importer fichiers PGP</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>"> <!-- Lien vers le fichier CSS -->
</head>
<body class="body-import">

    <header>
        <h1>EDITION  DU RAPPORT</h1>
    </header>
    <div class="container">
        <!-- Formulaire AJAX pour téléchargement -->
        <form id="uploadForm" class="upload-form-row" enctype="multipart/form-data" method="POST">
            <div class="upload-field">
                <label for="file">Fichier Excel :</label>
                <input type="file" name="excel_files[]" id="file" accept=".xls,.xlsx" multiple required>
            </div>
            <button type="button" class="btn-telecharger-compact">📂 Upload</button>
        </form>
        <!-- Formulaire de fusion des listes -->
        <?php
        $uploadDir = 'uploads/';
        $hasFiles = is_dir($uploadDir) && count(array_diff(scandir($uploadDir), ['.', '..'])) > 0;
        ?>
        <form id="fusionForm" action="fusionner.php" method="POST">
            <div class="form-section">
                <h3>Paramètres du Rapport</h3>
                <div id="no-files-message" class="error-message" style="display: none; margin-bottom: 10px;">
                    ⚠️ Aucun fichier détecté. Veuillez uploader vos fichiers Excel avant de fusionner.
                </div>
                <div class="form-grid form-grid--two-cols">
                    <div class="form-group" style="min-width: 0;">
                        <label for="terroir">Terroir :</label>
                        <select id="terroir" name="terroir" required style="width: 100%;">
                            <option value="">Sélectionner un terroir...</option>
                        </select>
                        <div class="helper-text">Champ obligatoire</div>
                    </div>
                    
                    <div class="form-group" style="min-width: 0;">
                        <label for="commune">Commune :</label>
                        <select id="commune" name="commune" required style="width: 100%;">
                            <option value="">Sélectionner une commune...</option>
                        </select>
                        <div class="helper-text">Champ obligatoire</div>
                    </div>
                    
                    <div class="form-group" style="min-width: 0;">
                        <label for="transfertTitle">Titre du Transfert :</label>
                        <select id="transfertTitle" name="transfert_title" required style="width: 100%;">
                            <option value="">Sélectionner un titre...</option>
                        </select>
                        <div class="helper-text">Utilisé sur la feuille RECAP FIN</div>
                    </div>
                    
                    <div class="form-group" style="min-width: 0;">
                        <label for="region">Région :</label>
                        <select id="region" name="region" required style="width: 100%;">
                            <option value="">Sélectionner une région...</option>
                        </select>
                        <div class="helper-text">Affiché dans l’en-tête</div>
                    </div>
                    
                    <div class="form-group" style="min-width: 0; grid-column: 1 / -1;">
                        <label for="district">District :</label>
                        <select id="district" name="district" required style="width: 100%;">
                            <option value="">Sélectionner un district...</option>
                        </select>
                        <div class="helper-text">Affiché dans l’en-tête</div>
                    </div>                    
                </div>
            </div>
            
            <div class="btns-row">
                <button type="submit" class="btn-fusionner" <?php echo $hasFiles ? '' : 'disabled'; ?>>🔗 Fusionner les listes</button>
            </div>
        </form>
        <!-- Barre de progression -->
        <div class="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
            <p id="progress-text">0%</p> <!-- ✅ Affiche le pourcentage -->
        </div>
        <div class="progress-details" id="progress-details">
            <ul class="progress-steps">
                <li data-step="init">Initialisation</li>
                <li data-step="read">Lecture fichiers</li>
                <li data-step="merge">Fusion données</li>
                <li data-step="sheets">Feuilles finales</li>
                <li data-step="final">Finalisation</li>
            </ul>
            <div class="progress-log" id="progress-log"></div>
        </div>
    </div>

    <script>
        console.log('🎬 Script acceuil_fusion.php chargé');

        // Charger les paramètres de base (terroir, commune, etc.)
        function loadFusionParameters() {
            fetch('api_get_params.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remplir Terroir
                        const terroirSelect = document.getElementById('terroir');
                        if (data.terroirs) {
                            data.terroirs.forEach(t => {
                                const option = document.createElement('option');
                                option.value = t.id;
                                option.textContent = t.nom;
                                terroirSelect.appendChild(option);
                            });
                        }
                        
                        // Remplir Commune
                        const communeSelect = document.getElementById('commune');
                        if (data.communes) {
                            data.communes.forEach(c => {
                                const option = document.createElement('option');
                                option.value = c.id;
                                option.textContent = c.nom;
                                communeSelect.appendChild(option);
                            });
                        }
                        
                        // Remplir Titre du Transfert
                        const transfertSelect = document.getElementById('transfertTitle');
                        if (data.transferts) {
                            data.transferts.forEach(t => {
                                const option = document.createElement('option');
                                option.value = t.titre;
                                option.textContent = t.titre;
                                transfertSelect.appendChild(option);
                            });
                        }
                        
                        // Remplir Région
                        const regionSelect = document.getElementById('region');
                        if (data.regions) {
                            data.regions.forEach(r => {
                                const option = document.createElement('option');
                                option.value = r.id;
                                option.textContent = r.nom;
                                regionSelect.appendChild(option);
                            });
                        }
                        
                        // Remplir District
                        const districtSelect = document.getElementById('district');
                        if (data.districts) {
                            data.districts.forEach(d => {
                                const option = document.createElement('option');
                                option.value = d.id;
                                option.textContent = d.nom;
                                districtSelect.appendChild(option);
                            });
                        }
                    }
                })
                .catch(error => console.error('Erreur chargement paramètres:', error));
        }
        
    </script>
</body>
</html>

