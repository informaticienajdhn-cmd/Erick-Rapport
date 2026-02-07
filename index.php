<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ErickRapport - Gestion Professionnelle des Rapports Excel</title>
     <meta name="description" content="Outil professionnel pour la fusion et le suivi des paiements via fichiers Excel">
    <link rel="stylesheet" href="styles-optimized.css?v=<?php echo time(); ?>">
    <script src="js/common.js"></script>
</head>
<!-- Le JavaScript est maintenant géré par js/common.js -->


<body class="body-index">



    <div class="main-container">
        <aside class="sidebar">
            <button class="menu-btn" data-page="fusion">Fusionner</button>
            <button class="menu-btn" data-page="suivi">Suivi des paiements</button>
            <button class="menu-btn" data-page="rapports">📁 Rapports</button>
            <button class="menu-btn" data-page="canevas">📄 Canevas</button>
            <button class="menu-btn" data-page="parametres">⚙️ Paramètres</button>
            <button class="menu-btn return-btn" data-action="home">
                <span class="btn-icon">🏠</span>
                <span class="btn-text">Accueil</span>
            </button>
        </aside>

        <div id="content" class="content-area" aria-live="polite">
            <?php include 'home-content.php'; ?>
        </div>

        <!-- ✅ Indicateur de chargement -->
        <div id="loading" class="loading-spinner" style="display: none;">Chargement...</div>
    </div>

    <div class="footer">
        <p>© 2025 ErickRapport - Créé par SOMBINIAINA Nomenjanahary Ralahy Erick</p>
        <div class="social-container">
            <p>Suivez-moi : 
                <a href="https://www.facebook.com/ericksomb" class="social-icon">🌐 Facebook</a>
                <a href="https://github.com/Erickralahy" class="social-icon">💻 GitHub</a>
            </p>
        </div>
    </div>

    <!-- Le JavaScript est maintenant entièrement géré par js/common.js -->

</body>
</html>