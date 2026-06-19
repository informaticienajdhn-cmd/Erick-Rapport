<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Enregistrés</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body class="body-import">
    <header>
        <h1>📁 RAPPORTS ENREGISTRÉS</h1>
    </header>
    <div class="container">
        <div class="rapports-filter-bar">
            <label for="filtre-activite">Filtrer par Activité:</label>
            <select id="filtre-activite">
                <option value="">-- Tous les rapports --</option>
            </select>
        </div>
        
        <div id="rapports-list">
            <p class="rapports-empty">Chargement...</p>
        </div>
    </div>

</body>
</html>
