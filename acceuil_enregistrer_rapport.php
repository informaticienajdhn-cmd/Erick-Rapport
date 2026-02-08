<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enregistrer le Rapport</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body class="body-import">

    <header>
        <h1>ENREGISTRER LE RAPPORT</h1>
    </header>
    <div class="container">
        <?php
        session_start();
        $sessionId = session_id();
        
        require_once 'config.php';
        require_once 'classes/Database.php';
        $db = Database::getInstance();
        $activites = $db->getAll('activites');
        
        // Lire les données de fusion depuis le fichier temporaire
        $fusionDataFile = __DIR__ . '/temp/fusion_data_' . $sessionId . '.json';
        $fusionData = null;
        
        if (file_exists($fusionDataFile)) {
            $fusionData = json_decode(file_get_contents($fusionDataFile), true);
        }
        
        // Debug: Logger les infos
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] acceuil_enregistrer_rapport.php - Session ID: ".$sessionId."\n", FILE_APPEND);
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Fusion data file exists: ".(file_exists($fusionDataFile) ? 'OUI' : 'NON')."\n", FILE_APPEND);
        file_put_contents(__DIR__.'/logs/debug_fusion.txt', "[".date('Y-m-d H:i:s')."] Fusion data: ".print_r($fusionData, true)."\n", FILE_APPEND);
        
        // Vérifier que la fusion s'est bien déroulée
        if (!$fusionData || empty($fusionData['file_path']) || !file_exists($fusionData['file_path'])) {
            echo '<div class="error-message">Aucun rapport fusionné à enregistrer. Veuillez d\'abord effectuer une fusion.</div>';
            echo '<button class="btn-retour" onclick="navigation.loadContent(\'fusion\')">Retour à la fusion</button>';
            echo '</div></body></html>';
            exit;
        }
        
        $params = $fusionData['params'] ?? [];
        $fileName = $fusionData['file_name'] ?? 'rapport_fusionne.xlsx';
        $filePath = $fusionData['file_path'];

        // Déterminer l'ID de la commune à partir du nom (pour charger canevas/conclusions)
        $communeId = null;
        if (!empty($params['commune'])) {
            $communes = $db->getAll('communes');
            foreach ($communes as $commune) {
                if ($commune['nom'] === $params['commune']) {
                    $communeId = $commune['id'];
                    break;
                }
            }
        }
        ?>

        <div class="form-section">
            <h3>Informations du Rapport</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Terroir:</strong> <?php echo htmlspecialchars($params['terroir'] ?? 'N/A'); ?>
                </div>
                <div class="info-item">
                    <strong>Commune:</strong> <?php echo htmlspecialchars($params['commune'] ?? 'N/A'); ?>
                </div>
                <div class="info-item">
                    <strong>Titre du transfert:</strong> <?php echo htmlspecialchars($params['transfert_title'] ?? 'N/A'); ?>
                </div>
                <div class="info-item">
                    <strong>Nom du fichier:</strong> <?php echo htmlspecialchars($fileName); ?>
                </div>
            </div>
        </div>

        <form id="saveReportForm" class="form-section" style="margin-top: 20px;">
            <h3>Enregistrement</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="nom">Nom du rapport :</label>
                    <input type="text" id="nom" name="nom" required 
                           value="Rapport <?php echo htmlspecialchars($params['commune'] ?? ''); ?> - <?php echo date('d/m/Y'); ?>"
                           style="width: 100%; padding: 8px;">
                    <div class="helper-text">Donnez un nom descriptif à ce rapport</div>
                </div>
                
                <div class="form-group">
                    <label for="activite_id">Activité :</label>
                    <select id="activite_id" name="activite_id" required style="width: 100%; padding: 8px;">
                        <option value="">Sélectionner une activité...</option>
                        <?php if (empty($activites)): ?>
                            <option value="" disabled>Aucune activité disponible</option>
                        <?php else: ?>
                            <?php foreach ($activites as $activite): ?>
                                <option value="<?php echo htmlspecialchars($activite['id']); ?>" <?php echo count($activites) === 1 ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($activite['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="helper-text">Type d'activité de ce rapport</div>
                </div>

                <div class="form-group">
                    <label for="canevas_id">📄 Page de garde :</label>
                    <select id="canevas_id" name="canevas_id" style="width: 100%; padding: 8px;">
                        <option value="">-- Aucune --</option>
                    </select>
                    <div class="helper-text">Liée à l'activité et à la commune</div>
                </div>

                <div class="form-group">
                    <label for="conclusion_id">📋 Conclusion :</label>
                    <select id="conclusion_id" name="conclusion_id" style="width: 100%; padding: 8px;">
                        <option value="">-- Aucune --</option>
                    </select>
                    <div class="helper-text">Liée à l'activité et à la commune</div>
                </div>
            </div>

            <input type="hidden" id="commune_id" name="commune_id" value="<?php echo htmlspecialchars($communeId ?? ''); ?>">

            <div class="button-group" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-retour" onclick="navigation.loadContent('fusion')">
                    ← Retour
                </button>
                <button type="submit" class="btn-fusionner" data-ignore-fusion-state="true">
                    💾 Enregistrer le rapport
                </button>
            </div>
        </form>

        <!-- Zone de progression -->
        <div id="progressContainer" style="display: none; margin-top: 20px;">
            <div class="progress-bar">
                <div id="progressBar" class="progress-fill"></div>
            </div>
            <p id="progressMessage"></p>
        </div>

        <!-- Zone de messages -->
        <div id="messageContainer" style="margin-top: 20px;"></div>

    </div>

<script>
    (function() {
        const activiteSelect = document.getElementById('activite_id');
        const communeIdInput = document.getElementById('commune_id');
        const canevasSelect = document.getElementById('canevas_id');
        const conclusionSelect = document.getElementById('conclusion_id');

        function resetOptions() {
            if (canevasSelect) {
                canevasSelect.innerHTML = '<option value="">-- Aucune --</option>';
            }
            if (conclusionSelect) {
                conclusionSelect.innerHTML = '<option value="">-- Aucune --</option>';
            }
        }

        function loadSupplementaryDocuments() {
            const activiteId = activiteSelect ? activiteSelect.value : '';
            const communeId = communeIdInput ? communeIdInput.value : '';

            if (!activiteId || !communeId) {
                resetOptions();
                return;
            }

            // Réinitialiser une seule fois au début
            resetOptions();

            // Charger les pages de garde
            fetch(`api_list_canevas.php?commune_id=${communeId}&activite_id=${activiteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.canevas && data.canevas.length > 0) {
                        data.canevas.forEach(c => {
                            const option = document.createElement('option');
                            option.value = c.nom_fichier; // Utiliser le nom du fichier comme valeur
                            option.textContent = '📄 ' + c.nom_fichier;
                            option.dataset.id = c.id; // Stocker l'ID comme attribut de données
                            canevasSelect.appendChild(option);
                        });
                    }
                })
                .catch(err => console.error('Erreur chargement canevas:', err));

            // Charger les conclusions
            fetch(`api_list_conclusions.php?commune_id=${communeId}&activite_id=${activiteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.conclusions && data.conclusions.length > 0) {
                        data.conclusions.forEach(c => {
                            const option = document.createElement('option');
                            option.value = c.nom_fichier; // Utiliser le nom du fichier comme valeur
                            option.textContent = '📋 ' + c.nom_fichier;
                            option.dataset.id = c.id; // Stocker l'ID comme attribut de données
                            conclusionSelect.appendChild(option);
                        });
                    }
                })
                .catch(err => console.error('Erreur chargement conclusions:', err));
        }

        if (activiteSelect) {
            activiteSelect.addEventListener('change', loadSupplementaryDocuments);
        }

        // Chargement initial si activité pré-sélectionnée
        loadSupplementaryDocuments();
    })();
</script>
</body>
</html>
