<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body class="body-import">
    <header>
        <h1>GESTION DES SUIVIS DES PAIEMENTS</h1>
    </header>
    <div class="container">
        <!-- Formulaire pour télécharger les fichiers -->
        <form id="uploadFormSuivi" class="upload-form-row" action="upload_suivi_paiement.php" method="POST" enctype="multipart/form-data">
            <div class="upload-field">
                <label for="file">Fichier Excel :</label>
                <input type="file" name="excel_files[]" id="file" accept=".xls,.xlsx" multiple required>
            </div>
            <button type="button" class="btn-telecharger-compact">📂 Upload</button>
        </form>
        
        <!-- Formulaire pour fusionner les listes -->
        <?php
        $uploadDir = 'uploads/';
        $hasFiles = is_dir($uploadDir) && count(array_diff(scandir($uploadDir), ['.', '..'])) > 0;
        ?>
        <form id="suiviForm" action="suivipaiement.php" method="POST" style="margin-bottom: 10px;">
            <!-- Section de paramétrage -->
            <div class="suivi-params-row">
                <div class="suivi-field">
                    <label for="titre_suivi">Titre du rapport :</label>
                    <input type="text" id="titre_suivi" name="titre_suivi" value="SUIVI DES PAIEMENTS">
                </div>
                <div class="suivi-field">
                    <label for="commune_suivi">Commune :</label>
                    <input type="text" id="commune_suivi" name="commune_suivi" placeholder="Ex: KARIANGA">
                </div>
                <div class="suivi-field">
                    <label for="terroir_suivi">Terroir :</label>
                    <input type="text" id="terroir_suivi" name="terroir_suivi" placeholder="Ex: KARIANGA">
                </div>
            </div>
            <button type="submit" class="btn-fusionner" <?php echo $hasFiles ? '' : 'disabled'; ?>>🔗 Fusionner les listes</button>
        </form>
        
        <!-- Barre de progression -->
        <div class="progress-container" style="opacity: 0;">
            <div class="progress-bar" id="progress-bar"></div>
            <p id="progress-text">0%</p>
        </div>
        
        <!-- Onglets de progression détaillée -->
        <div class="progress-details" id="progress-details" style="display: none;">
            <ul class="progress-steps">
                <li data-step="init">Initialisation</li>
                <li data-step="read">Lecture fichiers</li>
                <li data-step="merge">Fusion données</li>
                <li data-step="sheets">Feuilles finales</li>
                <li data-step="final">Finalisation</li>
            </ul>
            <div class="progress-log" id="progress-log"></div>
        </div>
        
        <div id="message-container"></div>
    </div>

<script>
// Charger les activités et communes pour le formulaire de canevas
// Note: Page chargée via AJAX, utiliser plutôt chargerActivitesFiltre() ou appeler directement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
    fetch('api_get_params.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Charger les activités
                const selectActivite = document.getElementById('canevas_activite');
                data.activites.forEach(activite => {
                    const option = document.createElement('option');
                    option.value = activite.id;
                    option.textContent = activite.nom;
                    selectActivite.appendChild(option);
                });
                
                // Charger les communes
                const selectCommune = document.getElementById('canevas_commune');
                data.communes.forEach(commune => {
                    const option = document.createElement('option');
                    option.value = commune.id;
                    option.textContent = commune.nom;
                    selectCommune.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Erreur chargement params:', error));
    
    // Gérer la soumission du formulaire de canevas
    document.getElementById('canevasForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const messageDiv = document.getElementById('canevas-message');
        
        messageDiv.innerHTML = '<span style="color: blue;">⏳ Enregistrement en cours...</span>';
        
        fetch('upload_canevas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<span style="color: green;">✅ ' + data.message + '</span>';
                document.getElementById('canevasForm').reset();
            } else {
                messageDiv.innerHTML = '<span style="color: red;">❌ ' + data.error + '</span>';
            }
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 3000);
        })
        .catch(error => {
            messageDiv.innerHTML = '<span style="color: red;">❌ Erreur: ' + error.message + '</span>';
        });
    });
    });
} else {
    // Content already loaded, execute immediately
    fetch('api_get_params.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const terroirs = data.terroirs || [];
                const communes = data.communes || [];
                const activites = data.activites || [];
                const params = data.params || {};
                
                const activitesSelect = document.getElementById('activite-suivi');
                const communesSelect = document.getElementById('commune-suivi');
                const terroirsSelect = document.getElementById('terroir-suivi');
                
                if (terroirsSelect) {
                    terroirsSelect.innerHTML = '<option value="">-- Sélectionner un terroir --</option>';
                    terroirs.forEach(t => {
                        const option = document.createElement('option');
                        option.value = t.id;
                        option.textContent = t.nom;
                        terroirsSelect.appendChild(option);
                    });
                }
                
                if (activitesSelect) {
                    activitesSelect.innerHTML = '<option value="">-- Sélectionner une activité --</option>';
                    activites.forEach(a => {
                        const option = document.createElement('option');
                        option.value = a.id;
                        option.textContent = a.nom;
                        activitesSelect.appendChild(option);
                    });
                }
                
                if (communesSelect) {
                    communesSelect.innerHTML = '<option value="">-- Sélectionner une commune --</option>';
                    communes.forEach(c => {
                        const option = document.createElement('option');
                        option.value = c.id;
                        option.textContent = c.nom;
                        communesSelect.appendChild(option);
                    });
                }
            }
        });
}
</script>

</body>
</html>
