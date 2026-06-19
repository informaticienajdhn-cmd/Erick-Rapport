<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    
    <!-- Styles responsive pour les formulaires -->
    <style>
        @media (max-width: 899px) {
            .param-upload-form {
                grid-template-columns: 1fr !important;
            }

            .param-upload-form .form-field-action {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body class="body-import">
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
        <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3); max-width: 400px; text-align: center;">
            <h2 style="color: #ef4444; margin-bottom: 15px; font-size: 20px;">🗑️ Confirmer la suppression</h2>
            <p id="deleteMessage" style="color: #666; margin-bottom: 25px; font-size: 14px; line-height: 1.6;"></p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="cancelDelete()" style="flex: 1; padding: 12px; background: #e5e7eb; color: #333; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">❌ Annuler</button>
                <button onclick="confirmDelete()" style="flex: 1; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;">✅ Supprimer</button>
            </div>
        </div>
    </div>

    <div class="container">
<?php
/**
 * Page de gestion des paramètres (version embeddable)
 */

require_once 'config.php';
require_once 'classes/Database.php';

$db = Database::getInstance();

// Charger les données pour l'affichage
$terroirs = $db->getAll('terroirs');
$communes = $db->getAll('communes');
$regions = $db->getAll('regions');
$districts = $db->getAll('districts');
$titres = $db->getAll('titres_transfert');
$activites = $db->getAll('activites');
$terroirs_list = $db->getAll('terroirs');
?>

<style>
    /* 🗂️ Conteneur principal avec scroll */
    .params-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0;
        padding: 0;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    /* Onglets compacts sans scroll horizontal */
    .tabs-container {
        display: grid;
        grid-template-columns: repeat(8, minmax(0, 1fr));
        gap: 0;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 12px;
        background: #f9fafb;
        border-radius: 8px 8px 0 0;
        overflow: hidden;
        position: sticky;
        top: 0;
        z-index: 10;
        width: 100%;
    }

    .tab-button {
        padding: 7px 2px;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 10px;
        font-weight: 600;
        color: #6b7280;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
        white-space: normal;
        line-height: 1.15;
        text-align: center;
        min-width: 0;
    }

    .tab-button:hover {
        color: #1e40af;
        background: rgba(37, 99, 235, 0.05);
    }

    .tab-button.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
        background: white;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .param-card {
        background: linear-gradient(145deg, #ffffff, #f9fafb);
        border: 2px solid #e5e7eb;
        border-radius: 0;
        padding: 10px;
        box-shadow: none;
        transition: transform 0.2s, box-shadow 0.2s;
        border-bottom: 1px solid #e5e7eb;
    }

    .param-card:last-child {
        border-bottom: none;
        border-radius: 0 0 8px 8px;
    }
    
    .param-card:hover {
        transform: none;
        box-shadow: none;
    }
    
    .param-card h3 {
        color: #1e40af;
        margin: 0 0 8px 0;
        padding-bottom: 6px;
        border-bottom: 2px solid #2563eb;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .add-form {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }
    
    .add-form input {
        flex: 1;
        padding: 6px 10px;
        border: 2px solid #d1d5db;
        border-radius: 6px;
        font-size: 12px;
        transition: all 0.2s;
    }
    
    .add-form input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .add-form button {
        padding: 6px 12px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        font-size: 12px;
        white-space: nowrap;
        transition: background 0.2s;
    }
    
    .add-form button:hover {
        background: #1e40af;
    }
    
    .items-list {
        max-height: 180px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .items-list::-webkit-scrollbar {
        width: 8px;
    }
    
    .items-list::-webkit-scrollbar-track {
        background: #f3f4f6;
        border-radius: 4px;
    }
    
    .items-list::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 4px;
    }
    
    .items-list::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }
    
    .item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 8px;
        background: #f9fafb;
        border-radius: 6px;
        border-left: 3px solid #2563eb;
        transition: all 0.2s;
    }
    
    .item:hover {
        background: #f3f4f6;
        border-left-color: #1e40af;
    }
    
    .item-name {
        flex: 1;
        color: #374151;
        font-size: 12px;
        font-weight: 500;
    }
    
    .item-actions {
        display: flex;
        gap: 5px;
    }
    
    .item-actions button {
        padding: 4px 8px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
        transition: all 0.2s;
    }
    
    .btn-edit {
        background: #f59e0b;
        color: white;
    }
    
    .btn-edit:hover {
        background: #d97706;
    }
    
    .btn-delete {
        background: #ef4444;
        color: white;
    }
    
    .btn-delete:hover {
        background: #dc2626;
    }
    
    .message {
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
        font-size: 13px;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .message.success {
        background: #d1fae5;
        color: #065f46;
        border-left: 3px solid #10b981;
    }
    
    .message.error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 3px solid #ef4444;
    }
    
    .empty-state {
        text-align: center;
        padding: 30px;
        color: #9ca3af;
        font-size: 13px;
    }
    
    .params-header {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .params-header h2 {
        margin: 0;
        font-size: 24px;
    }
    
    .params-header p {
        margin: 5px 0 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .params-toolbar {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        align-items: center;
    }

    .params-search {
        flex: 1;
        padding: 8px 12px;
        border: 2px solid #d1d5db;
        border-radius: 6px;
        font-size: 13px;
        transition: all 0.2s;
    }

    .params-search:focus {
        outline: none;
        border-color: #2563eb;
    }

    .params-count {
        font-size: 12px;
        color: #6b7280;
        font-weight: 600;
        white-space: nowrap;
    }

    .item.hidden {
        display: none;
    }

    .highlight {
        background-color: #fbbf24;
        font-weight: 600;
        padding: 1px 3px;
        border-radius: 2px;
    }

    .item.is-hidden {
        display: none;
    }

    @media (max-width: 1100px) {
        .tabs-container {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .param-upload-form {
            grid-template-columns: 1fr 1fr !important;
        }

        .param-upload-form .form-field-action {
            grid-column: 1 / -1;
        }
    }

    .param-upload-form {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 10px;
        align-items: end;
    }

    .param-upload-form .form-field {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .param-upload-form .form-field label {
        display: block;
        margin: 0 0 5px 0;
        font-weight: 600;
        font-size: 12px;
        line-height: 1.25;
        min-height: 30px;
    }

    .param-upload-form .form-field select,
    .param-upload-form .form-field input[type="file"] {
        width: 100%;
        height: 38px;
        padding: 6px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 12px;
        box-sizing: border-box;
        margin: 0;
        background: #fff;
    }

    .param-upload-form .form-field input[type="file"] {
        padding: 4px 6px;
    }

    .param-upload-form .form-field-action {
        display: flex;
        align-items: flex-end;
    }

    .param-upload-form .form-submit-btn {
        height: 38px;
        padding: 0 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .param-upload-form .form-submit-btn--canevas {
        background: #2563eb;
        color: #fff;
    }

    .param-upload-form .form-submit-btn--conclusion {
        background: #8b5cf6;
        color: #fff;
    }
</style>

<div class="params-page-wrap">
    <div class="params-header">
        <h2>⚙️ Gestion des Paramètres</h2>
        <p>Gérez vos terroirs, communes, régions, districts et titres de transfert</p>
    </div>

    <!-- 🗂️ Onglets -->
    <div class="tabs-container">
        <button class="tab-button active" onclick="switchTab('terroirs')">📍 Terroirs</button>
        <button class="tab-button" onclick="switchTab('communes')">🏘️ Communes</button>
        <button class="tab-button" onclick="switchTab('regions')">🗺️ Régions</button>
        <button class="tab-button" onclick="switchTab('districts')">📌 Districts</button>
        <button class="tab-button" onclick="switchTab('titres')">📋 Titres</button>
        <button class="tab-button" onclick="switchTab('activites')">⚡ Activités</button>
        <button class="tab-button" onclick="switchTab('canevas')">📄 P. garde</button>
        <button class="tab-button" onclick="switchTab('conclusions')">📋 Conclusion</button>
    </div>

    <div class="params-grid">
        <!-- Onglet Terroirs -->
        <div id="tab-terroirs" class="tab-content active">
            <div class="param-card">
                <h3><span>📍</span> Terroirs</h3>
                <div id="message-terroirs"></div>
                <div class="add-form">
                    <input type="text" id="input-terroirs" placeholder="Nouveau terroir..." onkeypress="if(event.key==='Enter') addItem('terroirs')">
                    <button onclick="addItem('terroirs')">+ Ajouter</button>
                </div>
                <div class="items-list" id="list-terroirs">
                    <?php if (empty($terroirs)): ?>
                        <div class="empty-state">Aucun terroir enregistré</div>
                    <?php else: ?>
                        <?php foreach ($terroirs as $item): ?>
                            <div class="item" data-id="<?= $item['id'] ?>">
                                <span class="item-name"><?= htmlspecialchars($item['nom']) ?></span>
                                <div class="item-actions">
                                    <button class="btn-edit" onclick="editItem('terroirs', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                                    <button class="btn-delete" onclick="deleteItem('terroirs', <?= $item['id'] ?>)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onglet Communes -->
        <div id="tab-communes" class="tab-content">
            <div class="param-card">
                <h3><span>🏘️</span> Communes</h3>
                <div id="message-communes"></div>
                <div class="add-form">
                    <input type="text" id="input-communes" placeholder="Nouvelle commune..." onkeypress="if(event.key==='Enter') addItem('communes')">
                    <button onclick="addItem('communes')">+ Ajouter</button>
                </div>
                <div class="items-list" id="list-communes">
                    <?php if (empty($communes)): ?>
                        <div class="empty-state">Aucune commune enregistrée</div>
                    <?php else: ?>
                        <?php foreach ($communes as $item): ?>
                            <div class="item" data-id="<?= $item['id'] ?>">
                                <span class="item-name"><?= htmlspecialchars($item['nom']) ?></span>
                                <div class="item-actions">
                                    <button class="btn-edit" onclick="editItem('communes', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                                    <button class="btn-delete" onclick="deleteItem('communes', <?= $item['id'] ?>)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onglet Régions -->
        <div id="tab-regions" class="tab-content">
            <div class="param-card">
                <h3><span>🗺️</span> Régions</h3>
                <div id="message-regions"></div>
                <div class="add-form">
                    <input type="text" id="input-regions" placeholder="Nouvelle région..." onkeypress="if(event.key==='Enter') addItem('regions')">
                    <button onclick="addItem('regions')">+ Ajouter</button>
                </div>
                <div class="items-list" id="list-regions">
                    <?php if (empty($regions)): ?>
                        <div class="empty-state">Aucune région enregistrée</div>
                    <?php else: ?>
                        <?php foreach ($regions as $item): ?>
                            <div class="item" data-id="<?= $item['id'] ?>">
                                <span class="item-name"><?= htmlspecialchars($item['nom']) ?></span>
                                <div class="item-actions">
                                    <button class="btn-edit" onclick="editItem('regions', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                                    <button class="btn-delete" onclick="deleteItem('regions', <?= $item['id'] ?>)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onglet Districts -->
        <div id="tab-districts" class="tab-content">
            <div class="param-card">
                <h3><span>📌</span> Districts</h3>
                <div id="message-districts"></div>
                <div class="add-form">
                    <input type="text" id="input-districts" placeholder="Nouveau district..." onkeypress="if(event.key==='Enter') addItem('districts')">
                    <button onclick="addItem('districts')">+ Ajouter</button>
                </div>
                <div class="items-list" id="list-districts">
                    <?php if (empty($districts)): ?>
                        <div class="empty-state">Aucun district enregistré</div>
                    <?php else: ?>
                        <?php foreach ($districts as $item): ?>
                            <div class="item" data-id="<?= $item['id'] ?>">
                                <span class="item-name"><?= htmlspecialchars($item['nom']) ?></span>
                                <div class="item-actions">
                                    <button class="btn-edit" onclick="editItem('districts', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                                    <button class="btn-delete" onclick="deleteItem('districts', <?= $item['id'] ?>)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onglet Titres -->
        <div id="tab-titres" class="tab-content">
            <div class="param-card">
                <h3><span>📋</span> Titres de Transfert</h3>
                <div id="message-titres_transfert"></div>
                <div class="add-form">
                    <input type="text" id="input-titres_transfert" placeholder="Nouveau titre..." onkeypress="if(event.key==='Enter') addItem('titres_transfert')">
                    <button onclick="addItem('titres_transfert')">+ Ajouter</button>
                </div>

                <div class="items-list" id="list-titres_transfert">
                    <?php if (empty($titres)): ?>
                        <div class="empty-state">Aucun titre enregistré</div>
                    <?php else: ?>
                        <?php foreach ($titres as $item): ?>
                            <div class="item" data-id="<?= $item['id'] ?>">
                                <span class="item-name"><?= htmlspecialchars($item['nom']) ?></span>
                                <div class="item-actions">
                                    <button class="btn-edit" onclick="editItem('titres_transfert', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                                    <button class="btn-delete" onclick="deleteItem('titres_transfert', <?= $item['id'] ?>)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onglet Activités -->
        <div id="tab-activites" class="tab-content">
            <div class="param-card">
                <h3><span>⚡</span> Activités</h3>
                <div id="message-activites"></div>
                <div class="add-form">
                    <input type="text" id="input-activites" placeholder="Nouvelle activité..." onkeypress="if(event.key==='Enter') addItem('activites')">
                    <button onclick="addItem('activites')">+ Ajouter</button>
                </div>

                <div class="items-list" id="list-activites">
                    <?php if (empty($activites)): ?>
                        <div class="empty-state">Aucune activité enregistrée</div>
                    <?php else: ?>
                        <?php foreach ($activites as $item): ?>
                            <div class="item" data-id="<?= $item['id'] ?>">
                                <span class="item-name"><?= htmlspecialchars($item['nom']) ?></span>
                                <div class="item-actions">
                                    <button class="btn-edit" onclick="editItem('activites', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                                    <button class="btn-delete" onclick="deleteItem('activites', <?= $item['id'] ?>)">🗑️</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Onglet Canevas Excel -->
        <div id="tab-canevas" class="tab-content">
            <div class="param-card" style="max-height: calc(100vh - 300px); overflow-y: auto; overflow-x: hidden; display: flex; flex-direction: column;">
                <h3><span>📄</span> PAGE DE GARDE</h3>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Uploadez des fichiers Excel page de garde liés à une activité et une commune</p>
                <div id="message-canevas"></div>
                
                <form id="canevasForm" class="param-upload-form" enctype="multipart/form-data" onsubmit="return false;" style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-bottom: 20px; flex-shrink: 0;">
                        <div class="form-field">
                            <label for="canevas_activite">Activité :</label>
                            <select id="canevas_activite" name="activite_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($activites as $activite): ?>
                                    <option value="<?= $activite['id'] ?>"><?= htmlspecialchars($activite['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="canevas_commune">Commune :</label>
                            <select id="canevas_commune" name="commune_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($communes as $commune): ?>
                                    <option value="<?= $commune['id'] ?>"><?= htmlspecialchars($commune['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="canevas_file">Fichier Page de Garde :</label>
                            <input type="file" id="canevas_file" name="canevas_file" accept=".xls,.xlsx" required>
                        </div>
                        <div class="form-field form-field-action">
                            <button type="button" class="form-submit-btn form-submit-btn--canevas" onclick="submitCanevasForm()">💾 Enregistrer</button>
                        </div>
                </form>
            </div>
        </div>

        <!-- Onglet Conclusions -->
        <div id="tab-conclusions" class="tab-content">
            <div class="param-card" style="max-height: calc(100vh - 300px); overflow-y: auto; overflow-x: hidden; display: flex; flex-direction: column;">
                <h3><span>📋</span> CONCLUSION</h3>
                <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Uploadez des fichiers Excel conclusion liés à une activité et une commune</p>
                <div id="message-conclusion"></div>
                
                <form id="conclusionForm" class="param-upload-form" enctype="multipart/form-data" onsubmit="return false;" style="background: #f9fafb; padding: 15px; border-radius: 8px; margin-bottom: 20px; flex-shrink: 0;">
                        <div class="form-field">
                            <label for="conclusion_activite">Activité :</label>
                            <select id="conclusion_activite" name="activite_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($activites as $activite): ?>
                                    <option value="<?= $activite['id'] ?>"><?= htmlspecialchars($activite['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="conclusion_commune">Commune :</label>
                            <select id="conclusion_commune" name="commune_id" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($communes as $commune): ?>
                                    <option value="<?= $commune['id'] ?>"><?= htmlspecialchars($commune['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="conclusion_file">Fichier Conclusion :</label>
                            <input type="file" id="conclusion_file" name="conclusion_file" accept=".xls,.xlsx" required>
                        </div>
                        <div class="form-field form-field-action">
                            <button type="button" class="form-submit-btn form-submit-btn--conclusion" onclick="submitConclusionForm()">💾 Enregistrer</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialiser les compteurs au chargement
    document.addEventListener('DOMContentLoaded', function() {
        ['terroirs', 'communes', 'regions', 'districts', 'titres_transfert', 'activites'].forEach(table => {
            updateCount(table);
        });
    });
</script>

<!-- SCRIPT FORMULAIRE CANEVAS (SUBMIT LISTENER) -->
<script>
    // Soumettre le formulaire de canevas
    window.submitCanevasForm = function() {
        const canevasForm = document.getElementById('canevasForm');
        const messageDiv = document.getElementById('message-canevas');
        const activiteId = document.getElementById('canevas_activite').value;
        const communeId = document.getElementById('canevas_commune').value;
        const fileInput = document.getElementById('canevas_file');
        
        console.log('submitCanevasForm appelé');
        console.log('Activité:', activiteId);
        console.log('Commune:', communeId);
        console.log('Fichier:', fileInput.files.length > 0 ? fileInput.files[0].name : 'AUCUN');
        
        // Vérifier que les champs sont remplis
        if (!activiteId || !communeId || !fileInput.files.length) {
            console.warn('Champs manquants');
            messageDiv.innerHTML = '<div style="color: orange; padding: 12px; background: #fef3c7; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #f59e0b; font-weight: 600;">⚠️ Veuillez remplir tous les champs!</div>';
            setTimeout(() => { messageDiv.innerHTML = ''; }, 4000);
            return false;
        }
        
        messageDiv.innerHTML = '<div style="color: #1e40af; padding: 12px; background: #dbeafe; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #3b82f6; font-weight: 600;">⏳ Enregistrement en cours...</div>';
        
        const formData = new FormData(canevasForm);
        console.log('FormData créé avec', formData.entries().length, 'entrées');
        
        fetch('upload_canevas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Response text:', text);
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('📦 Response data:', data);
            
            if (data.success) {
                messageDiv.innerHTML = '<div style="color: #166534; padding: 12px; background: #dcfce7; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #22c55e; font-weight: 600;">✅ ' + data.message + '</div>';
                canevasForm.reset();
                
                setTimeout(() => {
                    if (typeof window.loadCanevasPage === 'function') {
                        window.loadCanevasPage();
                    }
                    messageDiv.innerHTML = '';
                }, 1500);
            } else {
                messageDiv.innerHTML = '<div style="color: #991b1b; padding: 12px; background: #fee2e2; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #ef4444; font-weight: 600;">❌ ' + (data.error || 'Erreur inconnue') + '</div>';
                
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
            }
            setTimeout(() => {
                if (messageDiv.innerHTML.includes('❌')) {
                    messageDiv.innerHTML = '';
                }
            }, 5000);
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            messageDiv.innerHTML = '<div style="color: #991b1b; padding: 12px; background: #fee2e2; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #ef4444; font-weight: 600;">❌ Erreur réseau: ' + error.message + '</div>';
        });
        
        return false;
    };
    
    // Ancien code - gardé pour compatibilité
    document.addEventListener('DOMContentLoaded', function() {
        const canevasForm = document.getElementById('canevasForm');
        if (canevasForm) {
            console.log('✅ Formulaire canevas détecté au DOMContentLoaded');
            canevasForm.addEventListener('submit', function(e) {
                console.log('Submit event capturé');
                e.preventDefault();
                e.stopPropagation();
                return window.submitCanevasForm();
            });
        }
    });
    
    // Soumettre le formulaire de conclusion
    window.submitConclusionForm = function() {
        const conclusionForm = document.getElementById('conclusionForm');
        const messageDiv = document.getElementById('message-conclusion');
        const activiteId = document.getElementById('conclusion_activite').value;
        const communeId = document.getElementById('conclusion_commune').value;
        const fileInput = document.getElementById('conclusion_file');
        
        console.log('submitConclusionForm appelé');
        console.log('Activité:', activiteId);
        console.log('Commune:', communeId);
        console.log('Fichier:', fileInput.files.length > 0 ? fileInput.files[0].name : 'AUCUN');
        
        // Vérifier que les champs sont remplis
        if (!activiteId || !communeId || !fileInput.files.length) {
            console.warn('Champs manquants');
            messageDiv.innerHTML = '<div style="color: orange; padding: 12px; background: #fef3c7; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #f59e0b; font-weight: 600;">⚠️ Veuillez remplir tous les champs!</div>';
            setTimeout(() => { messageDiv.innerHTML = ''; }, 4000);
            return false;
        }
        
        messageDiv.innerHTML = '<div style="color: #1e40af; padding: 12px; background: #dbeafe; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #3b82f6; font-weight: 600;">⏳ Enregistrement en cours...</div>';
        
        const formData = new FormData(conclusionForm);
        console.log('FormData créé');
        
        fetch('upload_conclusion.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Response status:', response.status, response.statusText);
            // Vérifier si la réponse est OK (status 200-299)
            if (!response.ok) {
                // Lire le texte de la réponse pour voir le détail de l'erreur
                return response.text().then(text => {
                    console.error('❌ Response error text:', text);
                    throw new Error(`Erreur HTTP ${response.status}: ${text.substring(0, 200)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('📦 Response data:', data);
            
            if (data.success) {
                messageDiv.innerHTML = '<div style="color: #166534; padding: 12px; background: #dcfce7; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #22c55e; font-weight: 600;">✅ ' + data.message + '</div>';
                conclusionForm.reset();
                
                setTimeout(() => {
                    if (typeof window.loadConclusionsPage === 'function') {
                        window.loadConclusionsPage();
                    }
                    if (typeof window.loadCanevasPage === 'function') {
                        window.loadCanevasPage();
                    }
                    messageDiv.innerHTML = '';
                }, 1500);
            } else {
                messageDiv.innerHTML = '<div style="color: #991b1b; padding: 12px; background: #fee2e2; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #ef4444; font-weight: 600;">❌ ' + (data.error || 'Erreur inconnue') + '</div>';
                
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
            }
            setTimeout(() => {
                if (messageDiv.innerHTML.includes('❌')) {
                    messageDiv.innerHTML = '';
                }
            }, 5000);
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            messageDiv.innerHTML = '<div style="color: #991b1b; padding: 12px; background: #fee2e2; border-radius: 4px; margin-bottom: 10px; border-left: 4px solid #ef4444; font-weight: 600;">❌ Erreur réseau: ' + error.message + '</div>';
        });
        
        return false;
    };
    
    // ===== MODAL DE SUPPRESSION =====
    let deleteState = {
        callback: null,
        id: null
    };
    
    window.showDeleteConfirm = function(message, callback) {
        deleteState.callback = callback;
        document.getElementById('deleteMessage').innerHTML = message;
        document.getElementById('deleteModal').style.display = 'flex';
    };
    
    window.cancelDelete = function() {
        document.getElementById('deleteModal').style.display = 'none';
        deleteState.callback = null;
        deleteState.id = null;
    };
    
    window.confirmDelete = function() {
        if (deleteState.callback) {
            deleteState.callback();
        }
        document.getElementById('deleteModal').style.display = 'none';
    };
</script>

</body>
</html>
