/**
 * Formulaire adaptatif page de garde — rendu selon le schéma du modèle Excel actif.
 */
(function() {
    const form = document.getElementById('pgForm');
    if (!form) return;

    const messageEl = document.getElementById('pg-message');
    const listEl = document.getElementById('pg-config-list');
    const configIdEl = document.getElementById('pg_config_id');
    const templateIdEl = document.getElementById('pg_template_id');
    const templateSelect = document.getElementById('pgTemplateSelect');
    const templateInfo = document.getElementById('pgTemplateInfo');
    const tabsEl = document.getElementById('pg-dynamic-tabs');
    const panelsEl = document.getElementById('pg-dynamic-panels');

    const liaisonFields = ['activite_id', 'commune_id', 'terroir_id', 'region_id', 'district_id'];
    const knownColumnFields = [
        'direction_regionale', 'financement', 'contrat_numero', 'objet', 'lot', 'type_rapport',
        'libelle_activite', 'code_activite_1', 'code_activite_2', 'code_activite_3', 'code_activite_4',
        'periode_label', 'date_os', 'date_notification', 'date_signature', 'delai_prestation',
        'date_fin_contrat', 'transfert_label', 'en_tete_ong', 'intro_texte',
        'recap_direction', 'recap_titre', 'recap_sous_titre', 'recap_region', 'recap_district',
        'recap_problemes', 'recap_solutions'
    ];

    let currentSchema = null;
    let currentTemplateId = null;

    const sheetIcons = { cover: '📄', intro: '📝', recap: '📊', other: '📋' };

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function showMessage(text, type) {
        messageEl.innerHTML = '<div class="pg-alert pg-alert--' + type + '">' + text + '</div>';
        if (type === 'success') setTimeout(() => { messageEl.innerHTML = ''; }, 5000);
    }

    function renderDynamicForm(schema, values) {
        values = values || {};
        currentSchema = schema;
        if (!schema || !schema.sheets || !schema.sheets.length) {
            tabsEl.innerHTML = '';
            panelsEl.innerHTML = '<p class="helper-text">Importez un modèle Excel pour afficher le formulaire adapté.</p>';
            return;
        }

        tabsEl.innerHTML = schema.sheets.map((sheet, i) =>
            '<button type="button" class="pg-sheet-tab' + (i === 0 ? ' active' : '') + '" data-sheet="' + esc(sheet.id) + '">' +
            (sheetIcons[sheet.type] || '📋') + ' ' + esc(sheet.title) + '</button>'
        ).join('');

        panelsEl.innerHTML = schema.sheets.map((sheet, i) =>
            '<div id="pg-panel-' + esc(sheet.id) + '" class="pg-sheet-panel' + (i === 0 ? ' active' : '') + '">' +
            renderSheetFields(sheet, values) + '</div>'
        ).join('');

        tabsEl.querySelectorAll('.pg-sheet-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-sheet');
                tabsEl.querySelectorAll('.pg-sheet-tab').forEach(b => b.classList.toggle('active', b === btn));
                panelsEl.querySelectorAll('.pg-sheet-panel').forEach(p => {
                    p.classList.toggle('active', p.id === 'pg-panel-' + id);
                });
            });
        });

        bindRecapDateSync();
    }

    function renderSheetFields(sheet, values) {
        let html = '<div class="form-section"><h3>Feuille « ' + esc(sheet.title) + ' »</h3>';
        html += '<div class="form-grid form-grid--two-cols">';

        (sheet.fields || []).forEach(field => {
            html += renderField(field, values[field.key]);
        });

        html += '</div></div>';
        return html;
    }

    function renderField(field, value) {
        const key = field.key;
        const label = field.label || key;
        const type = field.type || 'text';
        const val = value != null ? value : '';

        if (type === 'recap_table') {
            let rows = Array.isArray(val) ? val : [];
            let body = (field.rows || []).map((rowDef, i) => {
                const rv = rows[i] || {};
                return '<tr>' +
                    '<td>' + esc(rowDef.label) + '</td>' +
                    '<td><input type="text" data-recap-key="' + esc(key) + '" data-recap-row="' + i + '" data-recap-col="prevue" placeholder="JJ/MM/AAAA" value="' + esc(rv.prevue || '') + '"></td>' +
                    '<td><input type="text" data-recap-key="' + esc(key) + '" data-recap-row="' + i + '" data-recap-col="effective" placeholder="JJ/MM/AAAA" value="' + esc(rv.effective || '') + '"></td>' +
                    '<td><input type="text" data-recap-key="' + esc(key) + '" data-recap-row="' + i + '" data-recap-col="obs" placeholder="Optionnel" value="' + esc(rv.obs || '') + '"></td>' +
                    '</tr>';
            }).join('');
            return '<div class="form-group form-group--full" data-field-type="recap_table" data-field-key="' + esc(key) + '">' +
                '<h4 class="pg-subheading">' + esc(label) + '</h4>' +
                '<p class="helper-text">Date effective = date prévue automatiquement.</p>' +
                '<div class="pg-recap-table-wrap"><table class="pg-recap-table"><thead><tr><th>Étape</th><th>Prévue</th><th>Effective</th><th>Observation</th></tr></thead><tbody>' +
                body + '</tbody></table></div></div>';
        }

        if (type === 'textarea' || type === 'textarea_multicell') {
            return '<div class="form-group form-group--full"><label>' + esc(label) +
                (field.cell ? ' <span class="helper-text">(' + esc(field.cell) + ')</span>' : '') +
                '</label><textarea name="' + esc(key) + '" rows="' + (type === 'textarea_multicell' ? 14 : 4) + '" class="' +
                (type === 'textarea_multicell' ? 'pg-textarea-large' : '') + '">' + esc(val) + '</textarea></div>';
        }

        const full = (type === 'text' && String(label).length > 30) ? ' form-group--full' : '';
        return '<div class="form-group' + full + '"><label>' + esc(label) +
            (field.cell ? ' <span class="helper-text">(' + esc(field.cell) + ')</span>' : '') +
            '</label><input type="text" name="' + esc(key) + '" value="' + esc(val) + '"></div>';
    }

    function bindRecapDateSync() {
        panelsEl.querySelectorAll('[data-recap-col="prevue"]').forEach(prevue => {
            prevue.addEventListener('input', () => {
                const row = prevue.getAttribute('data-recap-row');
                const key = prevue.getAttribute('data-recap-key');
                const effective = panelsEl.querySelector('[data-recap-key="' + key + '"][data-recap-row="' + row + '"][data-recap-col="effective"]');
                if (effective) effective.value = prevue.value;
            });
        });
    }

    function getFormData() {
        const data = { dynamic_payload: {} };
        liaisonFields.forEach(name => {
            const el = form.querySelector('[name="' + name + '"]');
            data[name] = el ? el.value : '';
        });
        data.template_id = templateIdEl.value || currentTemplateId || '';

        panelsEl.querySelectorAll('[name]').forEach(el => {
            data.dynamic_payload[el.name] = el.value;
        });

        panelsEl.querySelectorAll('[data-field-type="recap_table"]').forEach(block => {
            const key = block.getAttribute('data-field-key');
            const rows = [];
            block.querySelectorAll('[data-recap-row]').forEach(el => {
                const i = parseInt(el.getAttribute('data-recap-row'), 10);
                const col = el.getAttribute('data-recap-col');
                if (!rows[i]) rows[i] = {};
                rows[i][col] = el.value;
            });
            data.dynamic_payload[key] = rows.filter(Boolean);
        });

        Object.assign(data, data.dynamic_payload);
        knownColumnFields.forEach(k => {
            if (data.dynamic_payload[k] !== undefined) data[k] = data.dynamic_payload[k];
        });

        return data;
    }

    function fillFormValues(values, configMeta) {
        if (configMeta && configMeta.id) configIdEl.value = configMeta.id;
        if (configMeta && configMeta.template_id) {
            templateIdEl.value = configMeta.template_id;
            currentTemplateId = configMeta.template_id;
        }

        liaisonFields.forEach(name => {
            const el = form.querySelector('[name="' + name + '"]');
            if (el && values[name] != null && values[name] !== '') el.value = values[name];
        });

        if (!currentSchema) return;

        renderDynamicForm(currentSchema, values);
    }

    function loadTemplateList() {
        return fetch('api_canevas_templates.php?action=list&_=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error);
                const tpls = data.templates || [];
                templateSelect.innerHTML = tpls.length
                    ? tpls.map(t => '<option value="' + t.id + '"' + (t.is_active == 1 ? ' selected' : '') + '>' + esc(t.nom || t.nom_fichier) + '</option>').join('')
                    : '<option value="">Aucun modèle — importez un fichier</option>';
                return tpls;
            });
    }

    function loadActiveTemplate(templateId) {
        const url = templateId
            ? 'api_canevas_templates.php?action=get&id=' + templateId
            : 'api_canevas_templates.php?action=active&_=' + Date.now();

        return fetch(url).then(r => r.json()).then(data => {
            if (!data.success) throw new Error(data.error);
            const tpl = data.template;
            if (!tpl) {
                templateInfo.textContent = 'Importez un fichier Excel pour créer votre formulaire personnalisé.';
                renderDynamicForm(null);
                return null;
            }
            currentTemplateId = tpl.id;
            templateIdEl.value = tpl.id;
            templateSelect.value = tpl.id;
            const fieldCount = (tpl.schema.sheets || []).reduce((n, s) => n + (s.fields || []).length, 0);
            templateInfo.textContent = tpl.nom_fichier + ' — ' + fieldCount + ' zone(s) détectée(s)';
            renderDynamicForm(tpl.schema);
            return tpl;
        });
    }

    templateSelect.addEventListener('change', () => {
        const id = templateSelect.value;
        if (!id) return;
        fetch('api_canevas_templates.php?action=activate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10) })
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.error);
                loadActiveTemplate(id);
                showMessage('Modèle activé : le formulaire s\'adapte à cette structure.', 'success');
            })
            .catch(err => showMessage(err.message, 'error'));
    });

    function showImportResult(analysis) {
        const box = document.getElementById('pg-import-result');
        const matched = analysis.matched_entities || {};
        const sheets = analysis.sheets_found || {};
        const matchedList = Object.keys(matched).length
            ? Object.entries(matched).map(([k, v]) => '<li><strong>' + k + '</strong> : ' + v + '</li>').join('')
            : '<li>Vérifiez activité et commune manuellement si besoin.</li>';
        box.hidden = false;
        box.innerHTML = '<div class="pg-import-summary"><strong>Fichier :</strong> ' + esc(analysis.filename) +
            '<br><strong>Feuilles :</strong> ' + esc(sheets.cover || '—') + ' · ' + esc(sheets.introduction || '—') + ' · ' + esc(sheets.recap || '—') +
            '<br><strong>Formulaire adapté</strong> (' + (analysis.filled_count || 0) + ' valeur(s) importée(s))' +
            '<ul class="pg-import-matched">' + matchedList + '</ul></div>';
    }

    const importFileInput = document.getElementById('pgImportFile');
    const importFileNameEl = document.getElementById('pgImportFileName');
    const importAnalyzeBtn = document.getElementById('pgBtnAnalyze');

    importFileInput.addEventListener('change', () => {
        const file = importFileInput.files && importFileInput.files[0];
        importFileNameEl.textContent = file ? file.name : 'Choisir un fichier Excel (.xlsx)';
        importAnalyzeBtn.disabled = !file;
    });

    importAnalyzeBtn.addEventListener('click', () => {
        const file = importFileInput.files && importFileInput.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('canevas_file', file);
        showMessage('Analyse et création du formulaire adapté...', 'info');
        importAnalyzeBtn.disabled = true;
        fetch('api_analyze_canevas.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.error);
                configIdEl.value = '';
                currentTemplateId = res.analysis.template_id;
                templateIdEl.value = res.analysis.template_id;
                currentSchema = res.analysis.schema;
                loadTemplateList().then(() => loadActiveTemplate(res.analysis.template_id));
                fillFormValues(res.analysis.fields || {});
                showImportResult(res.analysis);
                showMessage(res.message, 'success');
            })
            .catch(err => showMessage(err.message, 'error'))
            .finally(() => {
                importAnalyzeBtn.disabled = !(importFileInput.files && importFileInput.files[0]);
            });
    });

    function resetForm() {
        configIdEl.value = '';
        liaisonFields.forEach(name => {
            const el = form.querySelector('[name="' + name + '"]');
            if (el) el.value = '';
        });
        renderDynamicForm(currentSchema, {});
    }

    window.loadPgConfigList = function() {
        fetch('api_canevas_config.php?action=list&_=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.configs.length) {
                    listEl.innerHTML = '<p class="helper-text">Aucune configuration enregistrée.</p>';
                    return;
                }
                listEl.innerHTML = data.configs.map(c =>
                    '<div class="pg-config-item"><div><strong>' + esc(c.commune_nom) + ' — ' + esc(c.activite_nom) +
                    '</strong><div class="helper-text">Màj: ' + esc(c.updated_at || c.created_at) + '</div></div>' +
                    '<div class="pg-config-actions">' +
                    '<button type="button" class="btn-telecharger-compact" data-load-id="' + c.id + '">Modifier</button>' +
                    '<button type="button" class="btn-fusionner" data-gen-id="' + c.id + '">Générer</button>' +
                    '<button type="button" class="btn-delete-rapport" data-del-id="' + c.id + '">Supprimer</button></div></div>'
                ).join('');
                listEl.querySelectorAll('[data-load-id]').forEach(btn => btn.addEventListener('click', () => loadConfig(btn.getAttribute('data-load-id'))));
                listEl.querySelectorAll('[data-gen-id]').forEach(btn => btn.addEventListener('click', () => confirmGenerate(btn.getAttribute('data-gen-id'))));
                listEl.querySelectorAll('[data-del-id]').forEach(btn => btn.addEventListener('click', () => deleteConfig(btn.getAttribute('data-del-id'))));
            });
    };

    function loadConfig(id) {
        fetch('api_canevas_config.php?action=get&id=' + id)
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error);
                const cfg = data.config;
                const loadTpl = cfg.template_id
                    ? loadActiveTemplate(cfg.template_id)
                    : loadActiveTemplate();
                Promise.resolve(loadTpl).then(() => fillFormValues(cfg, cfg));
                showMessage('Configuration chargée.', 'info');
            })
            .catch(err => showMessage(err.message, 'error'));
    }

    function saveConfig(callback) {
        const data = getFormData();
        if (!data.activite_id || !data.commune_id) {
            showMessage('Activité et commune sont obligatoires.', 'error');
            return;
        }
        fetch('api_canevas_config.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.error);
                configIdEl.value = res.config_id;
                showMessage('Configuration enregistrée.', 'success');
                loadPgConfigList();
                if (callback) callback(res.config_id);
            })
            .catch(err => showMessage(err.message, 'error'));
    }

    function confirmGenerate(id) {
        const message = 'Générer la page de garde et l\'enregistrer dans le menu Canevas ?';
        const run = () => generateConfig(id);
        if (typeof window.showConfirm === 'function') {
            window.showConfirm(message, run);
        } else if (confirm(message)) {
            run();
        }
    }

    function generateConfig(id) {
        const payload = id ? { config_id: parseInt(id, 10) } : getFormData();
        showMessage('Génération en cours...', 'info');
        fetch('api_canevas_config.php?action=generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.error);
                showMessage('Généré : ' + res.result.nom_fichier + '. Voir menu Canevas.', 'success');
                loadPgConfigList();
                if (typeof window.loadCanevasPage === 'function') window.loadCanevasPage();
            })
            .catch(err => showMessage(err.message, 'error'));
    }

    function deleteConfig(id) {
        if (!confirm('Supprimer cette configuration ?')) return;
        fetch('api_canevas_config.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10) })
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error(res.error);
                showMessage('Configuration supprimée.', 'success');
                loadPgConfigList();
            });
    }

    document.getElementById('pgBtnSave').addEventListener('click', () => saveConfig());
    document.getElementById('pgBtnGenerate').addEventListener('click', () => {
        saveConfig(id => confirmGenerate(id));
    });
    document.getElementById('pgBtnReset').addEventListener('click', resetForm);

    const logosGridEl = document.getElementById('pg-logos-grid');
    window.loadPgLogos = function() {
        if (!logosGridEl) return;
        fetch('api_canevas_logos.php?action=list&_=' + Date.now())
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error);
                logosGridEl.innerHTML = data.logos.map(logo => {
                    const preview = logo.uploaded
                        ? '<img src="api_canevas_logos.php?action=preview&slot=' + encodeURIComponent(logo.slot) + '&_=' + Date.now() + '" alt="" class="pg-logo-preview">'
                        : '<div class="pg-logo-placeholder">Aucun logo</div>';
                    const deleteBtn = logo.uploaded ? '<button type="button" class="pg-logo-delete" data-logo-slot="' + logo.slot + '">Supprimer</button>' : '';
                    return '<div class="pg-logo-card"><div class="pg-logo-card__header"><strong>' + esc(logo.label) + '</strong><span class="helper-text">' + esc(logo.hint) + '</span></div><div class="pg-logo-card__preview">' + preview + '</div><div class="pg-logo-card__actions"><label class="pg-logo-upload-label">' + (logo.uploaded ? 'Remplacer' : 'Choisir') + '<input type="file" accept="image/jpeg,image/png,image/gif" data-logo-slot="' + logo.slot + '" hidden></label>' + deleteBtn + '</div></div>';
                }).join('');
                logosGridEl.querySelectorAll('input[data-logo-slot]').forEach(input => {
                    input.addEventListener('change', () => {
                        const fd = new FormData();
                        fd.append('slot', input.getAttribute('data-logo-slot'));
                        fd.append('logo_file', input.files[0]);
                        fetch('api_canevas_logos.php?action=upload', { method: 'POST', body: fd })
                            .then(r => r.json()).then(res => { if (!res.success) throw new Error(res.error); loadPgLogos(); });
                    });
                });
                logosGridEl.querySelectorAll('.pg-logo-delete').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (!confirm('Supprimer ce logo ?')) return;
                        fetch('api_canevas_logos.php?action=delete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ slot: btn.getAttribute('data-logo-slot') }) })
                            .then(r => r.json()).then(res => { if (!res.success) throw new Error(res.error); loadPgLogos(); });
                    });
                });
            })
            .catch(err => { logosGridEl.innerHTML = '<p class="helper-text" style="color:#b91c1c;">' + esc(err.message) + '</p>'; });
    };

    loadTemplateList()
        .then(() => loadActiveTemplate())
        .catch(err => showMessage(err.message, 'error'));

    loadPgLogos();
    loadPgConfigList();
})();
