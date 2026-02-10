<style>
    .canevas-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .canevas-header {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .canevas-header h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }

    .canevas-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }

    /* Styles pour les onglets */
    .canevas-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .canevas-tab-button {
        padding: 12px 24px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        margin-bottom: -2px;
    }

    .canevas-tab-button:hover {
        color: #2563eb;
    }

    .canevas-tab-button.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }

    .canevas-tab-button.conclusions {
        color: #666;
    }

    .canevas-tab-button.conclusions.active {
        color: #8b5cf6;
        border-bottom-color: #8b5cf6;
    }

    .canevas-tab-content {
        display: none;
    }

    .canevas-tab-content.active {
        display: block;
    }


    .canevas-table-wrapper {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
        max-height: 700px;
        height: auto;
        overflow-y: scroll;
        overflow-x: hidden;
    }

    .canevas-table {
        width: 100%;
        min-width: auto;
        border-collapse: collapse;
        background: white;
        table-layout: fixed;
    }

    .canevas-table th,
    .canevas-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .canevas-table th:nth-child(1) { width: 30%; }
    .canevas-table th:nth-child(2) { width: 20%; }
    .canevas-table th:nth-child(3) { width: 18%; }
    .canevas-table th:nth-child(4) { width: 12%; }
    .canevas-table th:nth-child(5) { width: 20%; }

    .canevas-table thead {
        background: #f3f4f6;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .canevas-table th {
        padding: 6px 8px;
        text-align: left;
        font-size: 10px;
        font-weight: 700;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }

    .canevas-table td {
        padding: 6px 8px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11px;
    }

    .canevas-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .canevas-table tbody tr:last-child td {
        border-bottom: none;
    }

    .canevas-filename {
        font-weight: 600;
        color: #1f2937;
        line-height: 1.3;
    }

    .canevas-version {
        font-size: 10px;
        color: #999;
        font-weight: normal;
    }

    .canevas-activite {
        color: #666;
        font-size: 12px;
    }

    .canevas-date {
        color: #999;
        white-space: nowrap;
        font-size: 12px;
    }

    .canevas-actions {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
        white-space: nowrap;
    }

    .canevas-btn {
        padding: 4px 7px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 9px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .canevas-btn-download {
        background: #2563eb;
        color: white;
    }

    .canevas-btn-download:hover {
        background: #1e40af;
    }

    .canevas-btn-delete {
        background: #ef4444;
        color: white;
    }

    .canevas-btn-delete:hover {
        background: #dc2626;
    }

    .canevas-empty {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .canevas-empty-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }

    .canevas-empty-text {
        font-size: 16px;
        margin-bottom: 20px;
    }

    .canevas-loading {
        text-align: center;
        padding: 40px;
        color: #3b82f6;
        font-size: 14px;
    }

    /* Scrollbar personnalisée */
    .canevas-table-wrapper::-webkit-scrollbar {
        width: 10px;
    }

    .canevas-table-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .canevas-table-wrapper::-webkit-scrollbar-thumb {
        background: #2563eb;
        border-radius: 4px;
        min-height: 40px;
    }

    .canevas-table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #1e40af;
    }

    /* Scrollbar horizontale */
    .canevas-table-wrapper::-webkit-scrollbar-track-piece {
        background: #f1f1f1;
    }

    /* ========================================
       📱 RESPONSIVE - MOBILE & TABLET
       ======================================== */

    /* Tablettes et petits écrans */
    @media (max-width: 899px) {
        .canevas-container {
            padding: 10px;
        }

        .canevas-header {
            padding: 15px;
            margin-bottom: 15px;
        }

        .canevas-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .canevas-header p {
            font-size: 12px;
        }

        .canevas-tabs {
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 15px;
        }

        .canevas-tab-button {
            flex: 1;
            min-width: 120px;
            padding: 8px 10px;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .canevas-table-wrapper {
            max-height: 50vh;
        }

        .canevas-table {
            font-size: 10px;
        }

        .canevas-table th {
            padding: 5px 4px;
            font-size: 9px;
        }

        .canevas-table td {
            padding: 5px 4px;
            font-size: 10px;
        }

        /* Cache les colonnes moins importantes */
        .canevas-table th:nth-child(4),
        .canevas-table td:nth-child(4) {
            display: none;
        }

        .canevas-btn {
            padding: 3px 5px;
            font-size: 8px;
        }

        .canevas-filename {
            font-size: 10px;
        }

        .canevas-version {
            font-size: 9px;
        }
    }

    /* Mobiles petits */
    @media (max-width: 599px) {
        .canevas-container {
            padding: 8px;
        }

        .canevas-header {
            padding: 12px;
            margin-bottom: 10px;
        }

        .canevas-header h2 {
            font-size: 16px;
            margin-bottom: 3px;
        }

        .canevas-header p {
            font-size: 11px;
        }

        .canevas-tabs {
            gap: 4px;
            margin-bottom: 10px;
        }

        .canevas-tab-button {
            min-width: 100px;
            padding: 6px 8px;
            font-size: 10px;
        }

        .canevas-table-wrapper {
            max-height: 40vh;
        }

        .canevas-table {
            font-size: 9px;
        }

        .canevas-table th {
            padding: 4px 3px;
            font-size: 8px;
        }

        .canevas-table td {
            padding: 4px 3px;
            font-size: 9px;
        }

        /* Affiche seulement les colonnes essentielles */
        .canevas-table th:nth-child(2),
        .canevas-table td:nth-child(2),
        .canevas-table th:nth-child(4),
        .canevas-table td:nth-child(4) {
            display: none;
        }

        .canevas-filename {
            font-size: 9px;
        }

        .canevas-btn {
            padding: 2px 4px;
            font-size: 7px;
        }

        .canevas-actions {
            gap: 3px;
        }
    }

    /* Orientation paysage mobile */
    @media (max-height: 500px) and (orientation: landscape) {
        .canevas-container {
            padding: 5px;
        }

        .canevas-header {
            padding: 8px;
            margin-bottom: 8px;
        }

        .canevas-header h2 {
            font-size: 14px;
        }

        .canevas-table-wrapper {
            max-height: 50vh;
        }
    }

    /* Écrans très larges */
    @media (min-width: 1400px) {
        .canevas-container {
            padding: 30px;
        }

        .canevas-header {
            padding: 40px;
        }

        .canevas-header h2 {
            font-size: 28px;
        }

        .canevas-table {
            font-size: 13px;
        }

        .canevas-table th {
            padding: 8px;
            font-size: 11px;
        }

        .canevas-table td {
            padding: 8px;
            font-size: 12px;
        }

        .canevas-btn {
            padding: 5px 10px;
            font-size: 10px;
        }
    }
</style>

<div class="canevas-container">
    <div class="canevas-header">
        <h2>📄 Mes Fichiers Canevas</h2>
        <p>Liste des Pages de garde et des Conclusions</p>
    </div>

    <!-- Système d'onglets -->
    <div class="canevas-tabs">
        <button class="canevas-tab-button active" data-tab="canevas-tab">📄 PAGES DE GARDE</button>
        <button class="canevas-tab-button conclusions" data-tab="conclusions-tab">📋 CONCLUSIONS</button>
    </div>

    <!-- Onglet Pages de Garde -->
    <div id="canevas-tab" class="canevas-tab-content active">
        <div id="canevas-content">
            <div class="canevas-loading">🔄 Chargement des pages de garde...</div>
        </div>
    </div>

    <!-- Onglet Conclusions -->
    <div id="conclusions-tab" class="canevas-tab-content">
        <div id="conclusions-content">
            <div class="canevas-loading">🔄 Chargement des conclusions...</div>
        </div>
    </div>
</div>

<script>
    console.log('Script canevas.php exécuté');

    // Charger et afficher les PAGES DE GARDE
    window.loadCanevasPage = function() {
        console.log('loadCanevasPage appelé');
        const contentDiv = document.getElementById('canevas-content');

        if (!contentDiv) {
            console.error('Élément canevas-content non trouvé, réessai dans 100ms...');
            setTimeout(() => window.loadCanevasPage(), 100);
            return;
        }

        contentDiv.innerHTML = '<div class="canevas-loading">🔄 Chargement des pages de garde...</div>';

        fetch('api_list_canevas.php')
            .then(response => {
                console.log('Réponse API:', response.status);
                if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);

                if (data.success && data.canevas && data.canevas.length > 0) {
                    console.log('Affichage de', data.canevas.length, 'pages de garde');
                    contentDiv.innerHTML = `
                        <div class="canevas-table-wrapper">
                            <table class="canevas-table">
                                <thead>
                                    <tr>
                                        <th>Fichier</th>
                                        <th>Activité</th>
                                        <th>Commune</th>
                                        <th>Date</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.canevas.map(c => `
                                        <tr>
                                            <td>
                                                <div class="canevas-filename">
                                                    📄 ${c.nom_fichier}
                                                    ${c.version ? `<span class="canevas-version"> (v${c.version})</span>` : ''}
                                                </div>
                                            </td>
                                            <td class="canevas-activite">${c.activite_nom || 'Activité #' + c.activite_id}</td>
                                            <td>${c.commune_nom || 'Commune #' + c.commune_id}</td>
                                            <td class="canevas-date">${c.created_at ? new Date(c.created_at).toLocaleDateString('fr-FR') : 'Date inconnue'}</td>
                                            <td>
                                                <div class="canevas-actions">
                                                    <button class="canevas-btn canevas-btn-download" onclick="window.downloadCanevas(${c.id}, '${c.nom_fichier}')">📥 Télécharger</button>
                                                    <button class="canevas-btn canevas-btn-delete" onclick="window.deleteCanevasPage(${c.id})">🗑️ Supprimer</button>
                                                </div>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    console.log('Aucune page de garde trouvée');
                    contentDiv.innerHTML = `
                        <div class="canevas-empty">
                            <div class="canevas-empty-icon">📭</div>
                            <div class="canevas-empty-text">Aucune page de garde enregistrée</div>
                            <p style="font-size: 12px; color: #ccc;">
                                Allez dans Paramètres → PAGE DE GARDE pour enregistrer vos premiers fichiers
                            </p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement:', error);
                contentDiv.innerHTML = `
                    <div style="text-align: center; color: red; padding: 40px;">
                        ❌ Erreur lors du chargement: ${error.message}
                    </div>
                `;
            });
    };

    // Charger et afficher les CONCLUSIONS
    window.loadConclusionsPage = function() {
        console.log('loadConclusionsPage appelé');
        const contentDiv = document.getElementById('conclusions-content');

        if (!contentDiv) {
            console.error('Élément conclusions-content non trouvé, réessai dans 100ms...');
            setTimeout(() => window.loadConclusionsPage(), 100);
            return;
        }

        contentDiv.innerHTML = '<div class="canevas-loading">🔄 Chargement des conclusions...</div>';

        fetch('api_list_conclusions.php')
            .then(response => {
                console.log('Réponse API:', response.status);
                if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);

                if (data.success && data.conclusions && data.conclusions.length > 0) {
                    console.log('Affichage de', data.conclusions.length, 'conclusions');
                    contentDiv.innerHTML = `
                        <div class="canevas-table-wrapper">
                            <table class="canevas-table">
                                <thead>
                                    <tr>
                                        <th>Fichier</th>
                                        <th>Activité</th>
                                        <th>Commune</th>
                                        <th>Date</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.conclusions.map(c => `
                                        <tr>
                                            <td>
                                                <div class="canevas-filename">
                                                    📋 ${c.nom_fichier}
                                                    ${c.version ? `<span class="canevas-version"> (v${c.version})</span>` : ''}
                                                </div>
                                            </td>
                                            <td class="canevas-activite">${c.activite_nom || 'Activité #' + c.activite_id}</td>
                                            <td>${c.commune_nom || 'Commune #' + c.commune_id}</td>
                                            <td class="canevas-date">${c.created_at ? new Date(c.created_at).toLocaleDateString('fr-FR') : 'Date inconnue'}</td>
                                            <td>
                                                <div class="canevas-actions">
                                                    <button class="canevas-btn canevas-btn-download" onclick="window.downloadConclusion(${c.id}, '${c.nom_fichier}')" style="background: #8b5cf6;">📥 Télécharger</button>
                                                    <button class="canevas-btn canevas-btn-delete" onclick="window.deleteConclusion(${c.id})">🗑️ Supprimer</button>
                                                </div>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    console.log('Aucune conclusion trouvée');
                    contentDiv.innerHTML = `
                        <div class="canevas-empty">
                            <div class="canevas-empty-icon">📭</div>
                            <div class="canevas-empty-text">Aucune conclusion enregistrée</div>
                            <p style="font-size: 12px; color: #ccc;">
                                Allez dans Paramètres → CONCLUSION pour enregistrer vos premiers fichiers
                            </p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement:', error);
                contentDiv.innerHTML = `
                    <div style="text-align: center; color: red; padding: 40px;">
                        ❌ Erreur lors du chargement: ${error.message}
                    </div>
                `;
            });
    };

    // Télécharger une page de garde
    window.downloadCanevas = function(id, filename) {
        console.log('Téléchargement du canevas', id);
        const link = document.createElement('a');
        link.href = 'api_download_canevas.php?id=' + id;
        link.download = filename;
        link.click();
    };

    // Télécharger une conclusion
    window.downloadConclusion = function(id, filename) {
        console.log('Téléchargement de la conclusion', id);
        const link = document.createElement('a');
        link.href = 'api_download_conclusion.php?id=' + id;
        link.download = filename;
        link.click();
    };

    // Supprimer une page de garde
    window.deleteCanevasPage = function(id) {
        window.showConfirm('Supprimer cette page de garde ?', function() {
            fetch('api_delete_canevas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(response => response.json())
            .then(data => {
                console.log('Réponse suppression:', data);
                if (data.success) {
                    window.loadCanevasPage();
                } else {
                    window.showAlert('❌ Erreur: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur suppression:', error);
                window.showAlert('❌ Erreur lors de la suppression: ' + error.message);
            });
        }, function(){});
    };

    // Supprimer une conclusion
    window.deleteConclusion = function(id) {
        window.showConfirm('Supprimer cette conclusion ?', function() {
            fetch('api_delete_conclusion.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(response => response.json())
.then(data => {
                console.log('Réponse suppression:', data);
                if (data.success) {
                    window.loadConclusionsPage();
                } else {
                    window.showAlert('❌ Erreur: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur suppression:', error);
                window.showAlert('❌ Erreur lors de la suppression: ' + error.message);
            });
        }, function(){});
    };

    // Charger les deux sections au démarrage - avec un délai pour s'assurer que le DOM est prêt
    setTimeout(() => {
        console.log('Appel de loadCanevasPage et loadConclusionsPage après délai');
        window.loadCanevasPage();
        window.loadConclusionsPage();
    }, 100);

    // Gestion des onglets
    document.querySelectorAll('.canevas-tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            // Désactiver tous les onglets
            document.querySelectorAll('.canevas-tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.canevas-tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Activer l'onglet cliqué
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
</script>
