/**
 * Fonctions JavaScript communes pour ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

class ErickRapportApp {
    constructor() {
        this.progressBar = null;
        this.progressText = null;
        this.progressContainer = null;
        this.currentUploadXhr = null;
        this.progressInterval = null;
        this.choiceShown = false;
        
        this.init();
    }

    /**
     * Initialisation de l'application
     */
    init() {
        document.addEventListener("DOMContentLoaded", () => {
            this.initializeElements();
            this.bindEvents();
            this.attachSaveReportForm();
            this.loadStoredData();
        });
    }

    /**
     * Initialise les éléments DOM
     */
    initializeElements() {
        this.progressBar = document.getElementById('progress-bar');
        this.progressText = document.getElementById('progress-text');
        this.progressContainer = document.querySelector('.progress-container');
        
        console.log('Éléments de progression initialisés:');
        console.log('- progressBar:', !!this.progressBar);
        console.log('- progressText:', !!this.progressText);
        console.log('- progressContainer:', !!this.progressContainer);
        
        // Vérifier l'état initial des boutons de fusion
        this.checkFusionButtonState();

        // Charger les paramètres si le formulaire est déjà présent
        this.loadParametresIfNeeded();
    }

    /**
     * Lie les événements
     */
    bindEvents() {
        console.log('bindEvents appelé');
        
        // Supprimer les anciens événements pour éviter les doublons
        this.removeExistingEvents();
        
        // Gestion des boutons de menu
        const menuButtons = document.querySelectorAll(".menu-btn");
        console.log('Boutons de menu trouvés:', menuButtons.length);
        menuButtons.forEach(button => {
            if (!button.hasAttribute('data-event-bound')) {
                button.setAttribute('data-event-bound', 'true');
                if (button.classList.contains('return-btn')) {
                    button.addEventListener("click", (e) => {
                        // Action spéciale pour retour à l'accueil
                        document.querySelectorAll(".menu-btn").forEach(btn => btn.classList.remove("active-btn"));
                        button.classList.add("active-btn");
                        this.loadContent('home');
                        localStorage.setItem("lastPage", "home");
                    });
                } else {
                    button.addEventListener("click", (e) => this.handleMenuClick(e));
                }
            }
        });

        // Gestion des formulaires d'upload
        const uploadForms = document.querySelectorAll('form[enctype="multipart/form-data"]');
        console.log('Formulaires d\'upload trouvés:', uploadForms.length);
        
        uploadForms.forEach((form, index) => {
            // Chercher les boutons de téléchargement par classe ou type
            const submitButton = form.querySelector('button.btn-telecharger, button[type="button"]');
            console.log(`Formulaire ${index}: bouton trouvé =`, !!submitButton);
            
            // Ignorer les formulaires qui ont déjà un gestionnaire onclick (comme canevasForm, conclusionForm)
            if (submitButton && submitButton.hasAttribute('onclick')) {
                console.log(`Formulaire ${index}: ignorer car bouton a déjà un onclick`);
                return;
            }
            
            if (submitButton && !submitButton.hasAttribute('data-event-bound')) {
                console.log(`Ajout d'événement sur bouton ${index}`);
                submitButton.setAttribute('data-event-bound', 'true');
                submitButton.addEventListener('click', (e) => {
                    console.log('Clic sur bouton d\'upload détecté');
                    e.preventDefault();
                    this.uploadFile(form);
                });
            }
        });

        // Gestion des formulaires de fusion
        let fusionForms = document.querySelectorAll('form[action*="fusionner"], form[action*="suivipaiement"]');
        // Si aucun formulaire trouvé, tente de trouver par id explicite (robustesse)
        if (fusionForms.length === 0) {
            const fusionFormById = document.getElementById('fusionForm');
            if (fusionFormById) {
                fusionForms = [fusionFormById];
            }
        }
        console.log('Formulaires de fusion trouvés:', fusionForms.length);
        fusionForms.forEach((form, index) => {
            console.log(`Formulaire de fusion ${index}:`, form.action || form.id);
            if (!form.hasAttribute('data-event-bound')) {
                form.setAttribute('data-event-bound', 'true');
                form.addEventListener('submit', (e) => {
                    console.log('Événement submit détecté sur formulaire de fusion');
                    this.startProgress(e);
                });
            }
        });
    }
    
    /**
     * Supprime les anciens événements pour éviter les doublons
     */
    removeExistingEvents() {
        // Supprimer les marqueurs d'événements dans la zone de contenu dynamique
        const contentArea = document.getElementById('content');
        if (contentArea) {
            const elementsWithEvents = contentArea.querySelectorAll('[data-event-bound]');
            elementsWithEvents.forEach(element => {
                element.removeAttribute('data-event-bound');
            });
        }
    }

    /**
     * Charge les données stockées
     */
    loadStoredData() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlPage = urlParams.get('page');
        if (urlPage) {
            this.loadContent(urlPage);
            // Ne pas stocker 'enregistrer-rapport' en localStorage pour éviter la redirection infinie
            if (urlPage !== 'enregistrer-rapport') {
                localStorage.setItem("lastPage", urlPage);
            }
            return;
        }
        const lastPage = localStorage.getItem("lastPage");
        const uploadMessage = localStorage.getItem("uploadMessage");

        if (uploadMessage) {
            this.displayAlert(uploadMessage);
        }

        if (lastPage && window.location.href.includes("index.php")) {
            this.loadContent(lastPage);
        } else if (window.location.href.includes("index.php")) {
            // Si pas de lastPage, charger la page accueil par défaut
            this.loadContent('home');
            localStorage.setItem("lastPage", "home");
        }
    }

    /**
     * Gère les clics sur les boutons de menu
     */
    handleMenuClick(event) {
        const button = event.target.closest('.menu-btn');
        if (!button) return;
        
        // Mise à jour de l'état actif
        document.querySelectorAll(".menu-btn").forEach(btn => btn.classList.remove("active-btn"));
        button.classList.add("active-btn");

        const page = button.getAttribute("data-page");
        if (page) {
            this.loadContent(page);
            localStorage.setItem("lastPage", page);
            // Réinitialisation après chargement dynamique
            setTimeout(() => {
                this.reinitializeAfterContentLoad();
            }, 300);
        }
    }

    /**
     * Upload de fichiers avec gestion d'erreurs améliorée
     */
    uploadFile(form) {
        console.log('uploadFile appelé avec:', form);
        
        try {
            if (this.currentUploadXhr) {
                this.displayAlert('⏳ Un upload est déjà en cours. Veuillez patienter.');
                return;
            }
            // Validation côté client
            const fileInput = form.querySelector('input[type="file"]');
            console.log('Input file trouvé:', fileInput);
            console.log('Nombre de fichiers sélectionnés:', fileInput ? fileInput.files.length : 0);
            
            if (!fileInput || !fileInput.files.length) {
                throw new Error('Veuillez sélectionner au moins un fichier.');
            }

            // Validation des types de fichiers
            const allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            const allowedExtensions = ['.xls', '.xlsx'];
            
            for (let file of fileInput.files) {
                const extension = '.' + file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(extension)) {
                    throw new Error(`Le fichier "${file.name}" n'est pas un fichier Excel valide.`);
                }
                
                // Vérification de la taille (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    throw new Error(`Le fichier "${file.name}" est trop volumineux (max 10MB).`);
                }
            }

            // Préparation de la requête
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            this.currentUploadXhr = xhr;

            // Détermination de l'URL d'upload
            const uploadUrl = this.getUploadUrl();
            console.log('URL d\'upload déterminée:', uploadUrl);

            xhr.open("POST", uploadUrl, true);

            // Gestion de la progression d'upload
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    this.updateUploadProgress(percentComplete);
                }
            });

            // Gestion de la réponse
            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    this.currentUploadXhr = null;
                    
                    if (xhr.status === 200) {
                        this.handleUploadSuccess(xhr.responseText);
                    } else {
                        this.handleUploadError(`Erreur HTTP ${xhr.status}: ${xhr.statusText}`);
                    }
                }
            };

            // Gestion des erreurs réseau
            xhr.onerror = () => {
                this.currentUploadXhr = null;
                this.handleUploadError("Erreur de connexion réseau.");
            };

            // Gestion du timeout
            xhr.timeout = 300000; // 5 minutes
            xhr.ontimeout = () => {
                this.currentUploadXhr = null;
                this.handleUploadError("Timeout: Le téléchargement a pris trop de temps.");
            };

            // Envoi de la requête
            console.log('Envoi de la requête vers:', uploadUrl);
            xhr.send(formData);

            // Affichage de l'indicateur de progression
            this.showUploadProgress();

        } catch (error) {
            this.handleUploadError(error.message);
        }
    }

    /**
     * Détermine l'URL d'upload selon la page courante
     */
    getUploadUrl() {
        const currentPage = window.location.pathname;
        const pageTitle = document.title.toLowerCase();
        const pageContent = document.body.innerHTML.toLowerCase();
        
        // Détection plus précise de la page
        if (currentPage.includes("fusion") || 
            pageTitle.includes("fusion") || 
            pageTitle.includes("importer fichiers pgp") ||
            pageContent.includes("mis en page des listes")) {
            return "upload_fusion.php";
        } else if (currentPage.includes("suivi") || 
                   pageTitle.includes("suivi") ||
                   pageContent.includes("gestion des suivis") ||
                   pageContent.includes("suivipaiement.php")) {
            return "upload_suivi_paiement.php";
        }
        
        // Détection par défaut basée sur le contenu de la page
        if (pageContent.includes("suivipaiement.php")) {
            return "upload_suivi_paiement.php";
        }
        
        return "upload_fusion.php"; // Par défaut
    }

    /**
     * Affiche la progression d'upload
     */
    showUploadProgress() {
        if (this.progressContainer) {
            this.progressContainer.style.opacity = '1';
            this.updateUploadProgress(0);
        }
    }

    /**
     * Met à jour la progression d'upload
     */
    updateUploadProgress(percentage) {
        if (this.progressBar) {
            this.progressBar.style.width = percentage + '%';
        }
        if (this.progressText) {
            this.progressText.textContent = Math.round(percentage) + '%';
        }
    }

    /**
     * Gère le succès de l'upload
     */
    handleUploadSuccess(responseText) {
        console.log('Réponse reçue:', responseText);
        try {
            const response = JSON.parse(responseText);
            console.log('Réponse parsée:', response);
            let message;
            let uploadOk = false;
            if (response.success) {
                message = response.messages.map(msg => msg.message).join("\n");
                uploadOk = true;
            } else if (Array.isArray(response)) {
                message = response.map(item => item.message).join("\n");
                uploadOk = response.some(item => item.status === 'success');
            } else if (response.message) {
                message = response.message;
                uploadOk = true;
            } else {
                message = "Upload terminé avec succès.";
                uploadOk = true;
            }
            localStorage.setItem("uploadMessage", message);
            this.displayAlert(message);
            this.refreshHomeStats();
            // Forcer la vérification de l'état du bouton de fusion après upload
            this.checkFusionButtonState();
            // Si upload réussi et page fusion, proposer la fusion
            if (uploadOk && (window.location.pathname.includes('fusion') || document.title.toLowerCase().includes('fusion'))) {
                setTimeout(() => {
                    window.showConfirm('Fichiers téléchargés avec succès !', () => {
                        // Affiche le bouton Fusionner si présent
                        const fusionForm = document.getElementById('fusionForm');
                        if (fusionForm) {
                            fusionForm.style.setProperty('display', 'block', 'important');
                            console.log('Formulaire de fusion rendu visible !');
                        }
                        // Masque complètement la barre de progression
                        const progressContainer = document.querySelector('.progress-container');
                        if (progressContainer) progressContainer.style.display = 'none';
                        // Réinitialise le champ fichier
                        const fileInput = document.getElementById('file');
                        if (fileInput) fileInput.value = '';
                    }, function(){});
                }, 100);
            }
        } catch (error) {
            this.handleUploadError("Réponse du serveur invalide: " + error.message);
        }
    }

    /**
     * Vérifie l'état initial des boutons de fusion
     */
    checkFusionButtonState() {
        // Vérifier s'il y a des fichiers via AJAX
        fetch('check_files.php')
            .then(response => response.json())
            .then(data => {
                if (data.hasFiles) {
                    this.enableFusionButton();
                } else {
                    this.disableFusionButton();
                }
            })
            .catch(error => {
                console.log('Erreur lors de la vérification des fichiers:', error);
                // En cas d'erreur, laisser l'état par défaut
            });
    }

    /**
     * Active le bouton de fusion après un upload réussi
     */
    enableFusionButton() {
        console.log('Activation du bouton de fusion');
        const noFilesMessage = document.getElementById('no-files-message');
        if (noFilesMessage) {
            noFilesMessage.style.display = 'none';
        }
        // Affiche le formulaire fusionForm s'il existe
        const fusionForm = document.getElementById('fusionForm');
        if (fusionForm) {
            fusionForm.style.setProperty('display', 'block', 'important');
            console.log('Formulaire de fusion rendu visible !');
        } else {
            console.error('Formulaire de fusion (fusionForm) introuvable dans le DOM !');
        }
        // Chercher tous les boutons de fusion
        const fusionButtons = document.querySelectorAll('.btn-fusionner');
        fusionButtons.forEach(button => {
            if (button.hasAttribute('data-ignore-fusion-state')) {
                return;
            }
            // Retirer l'attribut disabled
            button.removeAttribute('disabled');
            button.disabled = false;
            // Ajouter une classe pour indiquer qu'il est actif
            button.classList.add('fusion-enabled');
            console.log('Bouton de fusion activé:', button, fusionForm ? fusionForm.style.display : '');
        });
        // Réattacher les événements si nécessaire
        this.bindFusionEvents();
    }
    
    /**
     * Désactive le bouton de fusion
     */
    disableFusionButton() {
        console.log('Désactivation du bouton de fusion');
        
        const noFilesMessage = document.getElementById('no-files-message');
        if (noFilesMessage) {
            noFilesMessage.style.display = 'block';
        }
        const fusionButtons = document.querySelectorAll('.btn-fusionner');
        
        fusionButtons.forEach(button => {
            if (button.hasAttribute('data-ignore-fusion-state')) {
                return;
            }
            button.setAttribute('disabled', 'disabled');
            button.disabled = true;
            button.classList.remove('fusion-enabled');
        });
    }
    
    /**
     * Attache les événements aux boutons de fusion
     */
    bindFusionEvents() {
        const fusionForms = document.querySelectorAll('form[action*="fusionner"], form[action*="suivipaiement"]');
        
        fusionForms.forEach((form, index) => {
            if (!form.hasAttribute('data-event-bound')) {
                form.setAttribute('data-event-bound', 'true');
                form.addEventListener('submit', (e) => {
                    console.log('Événement submit détecté sur formulaire de fusion (réattaché)');
                    this.startProgress(e);
                });
            }
        });
    }

    /**
     * Gère les erreurs d'upload
     */
    handleUploadError(errorMessage) {
        const message = "❌ " + errorMessage;
        localStorage.setItem("uploadMessage", message);
        this.displayAlert(message);
        
        // Masquer la barre de progression
        if (this.progressContainer) {
            this.progressContainer.style.opacity = '0';
        }
    }

    /**
     * Démarre le processus de fusion avec barre de progression
     */
    startProgress(event) {
        event.preventDefault();
        console.log('startProgress appelé');
        this.redirectWaitAttempts = 0;
        this.choiceShown = false;
        
        // Réinitialiser les éléments de progression au cas où le contenu aurait été rechargé
        this.initializeElements();
        
        if (!this.progressBar || !this.progressContainer) {
            console.log('Éléments de progression manquants, soumission directe');
            // Fallback si les éléments de progression n'existent pas
            event.target.submit();
            return;
        }

        console.log('Affichage de la barre de progression');
        console.log('progressText trouvé:', !!this.progressText);
        
        // Affichage de la barre de progression
        this.progressContainer.style.opacity = '1';
        this.progressContainer.style.display = 'block';
        this.progressBar.style.width = "0%";
        
        // S'assurer que l'élément progressText existe
        if (!this.progressText) {
            this.progressText = document.getElementById('progress-text');
        }
        
        if (this.progressText) {
            this.progressText.textContent = "0% - Démarrage...";
            console.log('Texte de progression mis à jour:', this.progressText.textContent);
        } else {
            console.error('Élément progress-text non trouvé !');
        }

        // Réinitialiser l'UI de progression
        this.resetProgressUI();
        this.disableFusionActions(true);
        this.logProgressMessage('Fusion démarrée');

        // Démarrage du suivi de progression en temps réel
        this.trackProgress();

        // Soumission du formulaire
        const form = event.target;
        const formData = new FormData(form);

        // Envoi de la requête de fusion
        fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(response => {
            if (response.ok) {
                // Vérifier si c'est un téléchargement direct
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
                    // Téléchargement direct du fichier
                    return response.blob().then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = 'Liste_des_beneficiaires.xlsx';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        // Arrêter le suivi si lancé
                        this.stopProgressTracking();

                        // Mise à jour de la progression à 100%
                        this.progressBar.style.width = "100%";
                        if (this.progressText) {
                            this.progressText.textContent = "100% - Téléchargement terminé";
                        }
                        
                        setTimeout(() => {
                            this.progressContainer.style.opacity = '0';
                        }, 2000);
                    });
                } else {
                    // Le suivi est déjà lancé
                }
            } else {
                throw new Error(`Erreur HTTP ${response.status}`);
            }
        }).catch(error => {
            this.stopProgressTracking();
            this.disableFusionActions(false);
            this.displayAlert("❌ Erreur lors du démarrage de la fusion: " + error.message);
            this.progressContainer.style.opacity = '0';
        });
    }

    /**
     * Suit la progression de la fusion
     */
    trackProgress() {
        console.log('Démarrage du suivi de progression');
        if (this.progressInterval) {
            return;
        }
        let attempts = 0;
        const maxAttempts = 600; // 10 minutes maximum
        
        this.progressInterval = setInterval(() => {
            attempts++;
            console.log(`Tentative de suivi ${attempts}`);
            
            fetch('getProgress.php', {
                credentials: 'same-origin'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Données de progression reçues:', data);
                    const progress = Math.min(data.progress || 0, 100);
                    if (progress < 100) {
                        this.redirectWaitAttempts = 0;
                    }
                    
                    // Mise à jour de la barre de progression
                    if (this.progressBar) {
                        this.progressBar.style.width = progress + "%";
                    }
                    
                    // Mise à jour du texte de progression
                    if (!this.progressText) {
                        this.progressText = document.getElementById('progress-text');
                    }
                    
                    if (this.progressText) {
                        if (data.progress_message) {
                            this.progressText.textContent = progress + "% - " + data.progress_message;
                            this.logProgressMessage(data.progress_message);
                        } else {
                            this.progressText.textContent = progress + "%";
                        }
                        console.log('Texte mis à jour:', this.progressText.textContent);
                    } else {
                        console.error('Impossible de mettre à jour le texte de progression - élément non trouvé');
                    }

                    this.updateProgressSteps(progress, data.progress_message || '');

                    // Vérification de la fin du processus
                    if (progress >= 100) {
                        console.log('Progression terminée');
                        console.log('Données reçues:', data);
                        this.logProgressMessage('Fusion terminée');
                        this.refreshHomeStats();
                        
                        if (data.redirect) {
                            this.stopProgressTracking();
                            this.disableFusionActions(false);
                            console.log('Redirection détectée vers:', data.redirect);
                            // Afficher le choix via popup après fusion
                            this.logProgressMessage('Choix après fusion...');
                            setTimeout(() => {
                                this.showFusionChoicePopup();
                            }, 300);
                            return;
                        }
                        
                        if (data.download) {
                            this.stopProgressTracking();
                            this.disableFusionActions(false);
                            // Téléchargement disponible (ancien flux)
                            this.logProgressMessage('Fichier prêt au téléchargement');
                            setTimeout(() => {
                                window.location.href = data.download;
                            }, 1000);
                            return;
                        }

                        // Attendre le redirect (écriture du fichier peut arriver après progress=100)
                        this.redirectWaitAttempts = (this.redirectWaitAttempts || 0) + 1;
                        if (this.redirectWaitAttempts <= 6) {
                            this.logProgressMessage('Finalisation en cours...');
                            return;
                        }

                        // Fallback après plusieurs tentatives
                        this.stopProgressTracking();
                        this.disableFusionActions(false);
                        this.logProgressMessage('Choix après fusion...');
                        setTimeout(() => {
                            this.showFusionChoicePopup();
                        }, 300);
                    }
                })
                .catch(error => {
                    console.error("Erreur lors du suivi de progression:", error);
                    
                    // Arrêter après plusieurs échecs consécutifs
                    if (attempts > 5) {
                        this.stopProgressTracking();
                        this.disableFusionActions(false);
                        this.displayAlert("❌ Erreur lors du suivi de progression: " + error.message);
                        if (this.progressContainer) {
                            this.progressContainer.style.opacity = '0';
                        }
                    }
                });
                
            // Timeout de sécurité
            if (attempts >= maxAttempts) {
                this.stopProgressTracking();
                this.disableFusionActions(false);
                this.displayAlert("❌ Timeout: Le processus prend trop de temps.");
                if (this.progressContainer) {
                    this.progressContainer.style.opacity = '0';
                }
            }
        }, 800); // Vérification plus fluide
    }

    /**
     * Arrête le suivi de progression
     */
    stopProgressTracking() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    /**
     * Active/désactive les actions pendant la fusion
     */
    disableFusionActions(isDisabled) {
        const fusionButtons = document.querySelectorAll('.btn-fusionner');
        fusionButtons.forEach(button => {
            if (button.hasAttribute('data-ignore-fusion-state')) {
                return;
            }
            if (isDisabled) {
                button.setAttribute('disabled', 'disabled');
                button.disabled = true;
                button.classList.remove('fusion-enabled');
            } else {
                button.removeAttribute('disabled');
                button.disabled = false;
                button.classList.add('fusion-enabled');
            }
        });
    }

    /**
     * Réinitialise les éléments de suivi avancé
     */
    resetProgressUI() {
        const log = document.getElementById('progress-log');
        if (log) {
            log.innerHTML = '';
        }
        this.updateProgressSteps(0, '');
    }

    /**
     * Ajoute un message dans le journal de progression
     */
    logProgressMessage(message) {
        if (!message) return;
        const log = document.getElementById('progress-log');
        if (!log) return;
        const last = log.lastElementChild?.textContent || '';
        if (last === message) return;
        const entry = document.createElement('p');
        entry.textContent = message;
        log.appendChild(entry);
        log.scrollTop = log.scrollHeight;
    }

    /**
     * Met à jour les étapes visuelles de progression
     */
    updateProgressSteps(progress, message) {
        const steps = document.querySelectorAll('.progress-steps [data-step]');
        if (!steps.length) return;

        const stepState = {
            init: progress >= 5,
            read: progress >= 10,
            merge: progress >= 35,
            sheets: progress >= 75,
            final: progress >= 90
        };

        steps.forEach(step => {
            const key = step.getAttribute('data-step');
            step.classList.remove('active', 'done');
            if (stepState[key]) {
                step.classList.add('done');
            }
        });

        const activeStep = (() => {
            if (progress < 10) return 'init';
            if (progress < 35) return 'read';
            if (progress < 75) return 'merge';
            if (progress < 90) return 'sheets';
            return 'final';
        })();

        const current = document.querySelector(`.progress-steps [data-step="${activeStep}"]`);
        if (current) {
            current.classList.add('active');
        }

        if (message) {
            this.logProgressMessage(message);
        }
    }

    /**
     * Affiche une alerte personnalisée
     */
    displayAlert(message) {
        // Suppression de l'alerte existante
        const existingAlert = document.getElementById('custom-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Création de la nouvelle alerte
        const alertBox = document.createElement("div");
        alertBox.id = "custom-alert";
        alertBox.innerHTML = `
            <div class="alert-content">
                <div class="checkmark">&#x2714;</div>
                <p>${message}</p>
                <button onclick="window.erickApp.closeAlert()">OK</button>
            </div>
        `;
        
        document.body.appendChild(alertBox);

        // Auto-fermeture après 10 secondes pour les messages de succès
        if (message.includes('✅')) {
            setTimeout(() => {
                this.closeAlert();
            }, 10000);
        }
    }

    /**
     * Affiche un popup de choix après fusion (ajouter une fusion ou terminer)
     */
    showFusionChoicePopup() {
        if (this.choiceShown) return;
        this.choiceShown = true;

        const existing = document.getElementById('fusion-choice-popup');
        if (existing) existing.remove();

        const popup = document.createElement('div');
        popup.id = 'fusion-choice-popup';
        popup.innerHTML = `
            <div class="fusion-choice-overlay">
                <div class="fusion-choice-modal">
                    <h3>Fusion terminée</h3>
                    <p>Que souhaitez-vous faire maintenant ?</p>
                    <div class="fusion-choice-buttons">
                        <button id="fusion-choice-add" class="btn-choice btn-add">➕ Ajouter une nouvelle fusion</button>
                        <button id="fusion-choice-finish" class="btn-choice btn-finish">✓ Terminer et enregistrer</button>
                    </div>
                    <button id="fusion-choice-close" class="btn-choice btn-close">Fermer</button>
                </div>
            </div>
        `;

        document.body.appendChild(popup);

        const addBtn = document.getElementById('fusion-choice-add');
        const finishBtn = document.getElementById('fusion-choice-finish');
        const closeBtn = document.getElementById('fusion-choice-close');

        addBtn.addEventListener('click', async () => {
            addBtn.disabled = true;
            finishBtn.disabled = true;
            try {
                const response = await fetch('api_save_fusion_temp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.success) {
                    // Réinitialiser l'état pour une nouvelle fusion propre
                    await fetch('api_reset_fusion.php', {
                        method: 'POST',
                        credentials: 'same-origin'
                    });
                    popup.remove();
                    this.loadContent('fusion');
                } else {
                    throw new Error(data.error || 'Erreur lors de la sauvegarde');
                }
            } catch (error) {
                this.displayAlert('❌ ' + error.message);
                addBtn.disabled = false;
                finishBtn.disabled = false;
            }
        });

        finishBtn.addEventListener('click', () => {
            popup.remove();
            window.location.href = 'api_finaliser_fusions.php';
        });

        closeBtn.addEventListener('click', () => {
            popup.remove();
        });
    }

    /**
     * Ferme l'alerte
     */
    closeAlert() {
        const alert = document.getElementById('custom-alert');
        if (alert) {
            alert.remove();
        }
        localStorage.removeItem("uploadMessage");
        
        // Redirection vers l'index si on n'y est pas déjà
        if (!window.location.href.includes("index.php")) {
            window.location.href = "index.php";
        }
    }

    /**
     * Met à jour les cartes de synthèse de la page d'accueil
     */
    refreshHomeStats() {
        fetch(`api_home_stats.php?_=${Date.now()}`, { cache: 'no-store' })
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.stats) {
                    return;
                }

                const stats = data.stats;
                const uploadedEl = document.getElementById('stat-uploaded-files');
                const fusionsEl = document.getElementById('stat-total-fusions');
                const versionEl = document.getElementById('stat-version');
                const activityEl = document.getElementById('stat-last-activity');

                if (uploadedEl) {
                    uploadedEl.textContent = stats.uploadedFiles ?? 0;
                }
                if (fusionsEl) {
                    fusionsEl.textContent = stats.totalFusions ?? 0;
                }
                if (versionEl) {
                    versionEl.textContent = stats.version ?? '2.1';
                }
                if (activityEl) {
                    activityEl.textContent = stats.lastActivity ?? 'Aucune activité récente';
                }
            })
            .catch(error => {
                console.warn('Impossible de rafraîchir les statistiques:', error);
            });
    }

    /**
     * Charge le contenu d'une page
     */
    loadContent(page) {
        const content = document.getElementById('content');
        const loading = document.getElementById('loading');
        
        if (!content) return;

        const fileMap = {
            'fusion': 'acceuil_fusion.php',
            'suivi': 'acceuil_suivi_paiement.php',
            'canevas': 'acceuil_canevas.php',
            'page-de-garde': 'acceuil_page_de_garde.php',
            'parametres': 'acceuil_parametres.php',
            'rapports': 'acceuil_rapports.php',
            'enregistrer-rapport': 'acceuil_enregistrer_rapport.php',
            'home': 'home-content.php'
        };

        const file = fileMap[page];
        if (!file) {
            content.innerHTML = `<p style="color: red;">❌ Page non trouvée.</p>`;
            return;
        }

        // Affichage du loader
        if (loading) {
            loading.style.display = "block";
        }

        // Chargement du contenu
        fetch(`${file}?_=${Date.now()}`, { cache: 'no-store' })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                content.innerHTML = html;
                
                // Exécuter les scripts du contenu chargé
                const scripts = content.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    if (script.src) {
                        newScript.src = script.src;
                    } else {
                        newScript.textContent = script.textContent;
                    }
                    document.head.appendChild(newScript);
                    document.head.removeChild(newScript);
                });
                
                // Réinitialisation complète après chargement du contenu
                // N'utiliser que la réinitialisation, pas une nouvelle instance
                // Une nouvelle instance appellerait loadStoredData() qui rechercherait la dernière page
                if (window.erickApp) {
                    window.erickApp.initializeElements();
                    window.erickApp.bindEvents();
                    window.erickApp.reinitializeAfterContentLoad();
                } else {
                    window.erickApp = new ErickRapportApp();
                }
                // Charger les rapports et les activités si la page les affiche
                if (typeof window.chargerActivitesFiltre === 'function') {
                    window.chargerActivitesFiltre();
                }
                if (typeof window.chargerRapports === 'function') {
                    window.chargerRapports();
                }
                // Charger la page canevas si elle est affichée
                if (typeof window.loadCanevasPage === 'function') {
                    setTimeout(() => window.loadCanevasPage(), 100);
                }
                // Charger les conclusions si la page les affiche
                if (typeof window.loadConclusionsPage === 'function') {
                    setTimeout(() => window.loadConclusionsPage(), 100);
                }
                if (page === 'home') {
                    this.refreshHomeStats();
                }
            })
            .catch(error => {
                content.innerHTML = `<p style="color: red;">❌ Erreur lors du chargement: ${error.message}</p>`;
            })
            .finally(() => {
                if (loading) {
                    loading.style.display = "none";
                }
            });
    }

    /**
     * Réinitialise tous les éléments après chargement de contenu dynamique
     */
    reinitializeAfterContentLoad() {
        console.log('Réinitialisation après chargement de contenu');
        
        // Réinitialiser les éléments DOM
        this.initializeElements();
        
        // Réattacher tous les événements
        this.bindEvents();
        
        // Vérifier l'état des boutons de fusion
        this.checkFusionButtonState();
        
        // Charger les paramètres pour les selects si présents
        this.loadParametresIfNeeded();
        
        // Attacher le formulaire d'enregistrement de rapport si présent
        this.attachSaveReportForm();
        
        console.log('Réinitialisation terminée');
    }
    
    /**
     * Attache le gestionnaire d'événements au formulaire d'enregistrement de rapport
     */
    attachSaveReportForm() {
        const saveForm = document.getElementById('saveReportForm');
        if (!saveForm) return;
        
        console.log('[SAVE RAPPORT] Formulaire trouvé, attachement du gestionnaire');
        
        saveForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            console.log('[SAVE RAPPORT] Formulaire soumis');
            
            // Validation des champs
            const nom = document.getElementById('nom').value.trim();
            const activiteId = document.getElementById('activite_id').value;
            const messageContainer = document.getElementById('messageContainer');
            
            console.log('[SAVE RAPPORT] Nom:', nom);
            console.log('[SAVE RAPPORT] Activite ID:', activiteId);
            
            if (!nom) {
                console.error('[SAVE RAPPORT] Nom manquant');
                messageContainer.innerHTML = '<div class="error-message">✗ Veuillez entrer un nom pour le rapport</div>';
                document.getElementById('nom').focus();
                return;
            }
            
            if (!activiteId) {
                console.error('[SAVE RAPPORT] Activité non sélectionnée');
                messageContainer.innerHTML = '<div class="error-message">✗ Veuillez sélectionner une activité</div>';
                document.getElementById('activite_id').focus();
                return;
            }
            
            const formData = new FormData(saveForm);
            const progressContainer = document.getElementById('progressContainer');
            const submitButton = saveForm.querySelector('button[type="submit"]');
            
            console.log('[SAVE RAPPORT] Validation OK, envoi de la requête...');
            
            try {
                // Désactiver le bouton et afficher la progression
                submitButton.disabled = true;
                progressContainer.style.display = 'block';
                messageContainer.innerHTML = '';
                
                document.getElementById('progressMessage').textContent = 'Enregistrement du rapport...';
                
                console.log('[SAVE RAPPORT] Envoi POST vers api_enregistrer_rapport.php');
                
                const response = await fetch('api_enregistrer_rapport.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                console.log('[SAVE RAPPORT] Réponse reçue, status:', response.status);
                
                const result = await response.json();
                
                console.log('[SAVE RAPPORT] Résultat JSON:', result);
                
                if (result.success) {
                    messageContainer.innerHTML = '<div class="success-message">✓ ' + result.message + '</div>';
                    this.refreshHomeStats();
                    
                    // Nettoyer le lastPage du localStorage pour éviter la redirection
                    localStorage.removeItem('lastPage');
                    
                    // Rediriger vers la page des rapports après 2 secondes
                    setTimeout(() => {
                        this.loadContent('rapports');
                        localStorage.setItem('lastPage', 'rapports');
                    }, 2000);
                } else {
                    throw new Error(result.error || 'Erreur lors de l\'enregistrement');
                }
                
            } catch (error) {
                console.error('[SAVE RAPPORT] Erreur:', error);
                messageContainer.innerHTML = '<div class="error-message">✗ ' + error.message + '</div>';
                submitButton.disabled = false;
            } finally {
                progressContainer.style.display = 'none';
            }
        });
    }

    /**
     * Charge les paramètres dans les selects s'ils existent dans le DOM
     */
    loadParametresIfNeeded() {
        const terroir = document.getElementById('terroir');
        const commune = document.getElementById('commune');
        const transfertTitle = document.getElementById('transfertTitle');
        const region = document.getElementById('region');
        const district = document.getElementById('district');
        
        // Vérifier si au moins un select de paramètre existe
        if (terroir || commune || transfertTitle || region || district) {
            console.log('🔄 Selects de paramètres détectés, chargement des données...');
            this.loadParametres();
        }
    }

    /**
     * Charge les paramètres depuis l'API
     */
    loadParametres() {
        console.log('📡 Appel API pour charger les paramètres...');
        fetch('api_parametres.php?type=all')
            .then(response => {
                console.log('📥 Réponse API reçue, status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📦 Données reçues:', data);
                if (data.success) {
                    this.populateSelect('terroir', data.data.terroirs || []);
                    this.populateSelect('commune', data.data.communes || []);
                    this.populateSelect('transfertTitle', data.data.titres || []);
                    this.populateSelect('region', data.data.regions || []);
                    this.populateSelect('district', data.data.districts || []);
                    console.log('✅ Tous les selects remplis avec succès');
                } else {
                    console.error('❌ Erreur API:', data.error || data.message);
                    displayAlert('❌ Erreur lors du chargement des paramètres: ' + (data.error || data.message));
                }
            })
            .catch(error => {
                console.error('❌ Erreur de chargement:', error);
                displayAlert('❌ Erreur de connexion: ' + error.message);
            });
    }

    /**
     * Remplit un select avec les données
     */
    populateSelect(selectId, items) {
        const select = document.getElementById(selectId);
        if (!select) {
            console.warn(`⚠️ Select '${selectId}' non trouvé dans le DOM`);
            return;
        }
        
        // Garder la première option "Sélectionner..."
        const firstOption = select.options[0];
        select.innerHTML = '';
        if (firstOption) {
            select.appendChild(firstOption);
        }
        
        console.log(`📝 Remplissage de '${selectId}' avec ${items.length} éléments`);
        
        if (items && items.length > 0) {
            items.forEach(item => {
                const option = document.createElement('option');
                // Pour transfertTitle, utiliser le nom au lieu de l'ID
                // Pour les autres champs (terroir, commune, etc.), utiliser l'ID
                if (selectId === 'transfertTitle') {
                    option.value = item.nom; // ✅ Utiliser le nom pour les titres de transfert
                    option.dataset.id = item.id; // Stocker l'ID comme donnée
                } else {
                    option.value = item.id || item.nom; // Utiliser l'ID si disponible, sinon le nom
                }
                option.textContent = item.nom;
                select.appendChild(option);
            });
            console.log(`✅ ${selectId}: ${items.length} options ajoutées`);
        } else {
            console.warn(`⚠️ ${selectId}: Aucune donnée disponible`);
        }
    }

    /**
     * Annule l'upload en cours
     */
    cancelUpload() {
        if (this.currentUploadXhr) {
            this.currentUploadXhr.abort();
            this.currentUploadXhr = null;
            
            if (this.progressContainer) {
                this.progressContainer.style.opacity = '0';
            }
            
            this.displayAlert("❌ Upload annulé.");
        }
    }
}

// Initialisation globale de l'application
if (window.erickApp && typeof window.erickApp.cancelUpload === 'function') {
    window.erickApp.cancelUpload();
}
window.erickApp = new ErickRapportApp();

// Fonction utilitaire à appeler après chaque chargement dynamique de contenu
window.reinitErickAppUI = function() {
    if (window.erickApp) {
        window.erickApp.initializeElements();
        window.erickApp.bindEvents();
    }
};

// Initialisation de l'interface utilisateur
setTimeout(() => {
    window.reinitErickAppUI();
}, 50);

// --- Global modal helpers (modern styled popups) ---
(function(){
    function createModal(html) {
        const wrapper = document.createElement('div');
        wrapper.className = 'global-modal-wrapper';
        wrapper.innerHTML = html;
        document.body.appendChild(wrapper);
        return wrapper;
    }

    function removeModal(modal) {
        if (!modal) return;
        modal.classList.add('fade-out');
        setTimeout(() => modal.remove(), 220);
    }

    // Simple styles (scoped) if not already injected
    if (!document.getElementById('global-modal-styles')) {
        const style = document.createElement('style');
        style.id = 'global-modal-styles';
        style.textContent = `
            .global-modal-wrapper {position:fixed;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:20000}
            .global-modal {background:#0f1724;color:#e6eef8;border-radius:12px;padding:22px;min-width:320px;max-width:720px;box-shadow:0 8px 30px rgba(0,0,0,0.6);font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
            .global-modal h3{margin:0 0 8px;font-size:18px}
            .global-modal .gm-content{margin:8px 0 14px;font-size:14px}
            .gm-actions{display:flex;gap:10px;justify-content:flex-end}
            .gm-btn{padding:8px 12px;border-radius:8px;border:none;cursor:pointer;font-weight:600}
            .gm-btn.secondary{background:#1f2937;color:#fff}
            .gm-btn.primary{background:#06b6d4;color:#04202a}
            .global-modal input[type="text"]{width:100%;padding:8px;border-radius:8px;border:1px solid #334155;background:#0b1220;color:#e6eef8}
            .fade-out{opacity:0;transition:opacity .18s ease}
        `;
        document.head.appendChild(style);
    }

    window.showAlert = function(message, opts = {}) {
        const modal = createModal(`
            <div class="global-modal" role="dialog" aria-modal="true">
                <div class="gm-content">${message}</div>
                <div class="gm-actions">
                    <button class="gm-btn primary gm-ok">OK</button>
                </div>
            </div>
        `);
        modal.querySelector('.gm-ok').addEventListener('click', () => removeModal(modal));
        if (opts.autoClose && typeof opts.autoClose === 'number') {
            setTimeout(() => removeModal(modal), opts.autoClose);
        }
        return modal;
    };

    window.showConfirm = function(message, onYes, onNo, opts = {}) {
        const modal = createModal(`
            <div class="global-modal" role="dialog" aria-modal="true">
                <div class="gm-content">${message}</div>
                <div class="gm-actions">
                    <button class="gm-btn secondary gm-cancel">Annuler</button>
                    <button class="gm-btn primary gm-yes">Confirmer</button>
                </div>
            </div>
        `);
        modal.querySelector('.gm-yes').addEventListener('click', () => { removeModal(modal); if (typeof onYes === 'function') onYes(); });
        modal.querySelector('.gm-cancel').addEventListener('click', () => { removeModal(modal); if (typeof onNo === 'function') onNo(); });
        return modal;
    };

    window.showPrompt = function(message, defaultValue, callback) {
        const modal = createModal(`
            <div class="global-modal" role="dialog" aria-modal="true">
                <h3>${message}</h3>
                <div class="gm-content"><input type="text" class="gm-input" value="${(defaultValue||'').replace(/"/g,'&quot;')}"></div>
                <div class="gm-actions">
                    <button class="gm-btn secondary gm-cancel">Annuler</button>
                    <button class="gm-btn primary gm-ok">OK</button>
                </div>
            </div>
        `);
        const input = modal.querySelector('.gm-input');
        input.focus();
        modal.querySelector('.gm-ok').addEventListener('click', () => {
            const val = input.value.trim();
            removeModal(modal);
            if (typeof callback === 'function') callback(val);
        });
        modal.querySelector('.gm-cancel').addEventListener('click', () => {
            removeModal(modal);
            if (typeof callback === 'function') callback(null);
        });
        return modal;
    };
})();

// Fonctions globales pour compatibilité avec l'ancien code
function uploadFile() {
    const form = document.getElementById('uploadForm');
    if (form) {
        window.erickApp.uploadFile(form);
    }
}

function startProgress(event) {
    window.erickApp.startProgress(event);
}

function displayAlert(message) {
    window.erickApp.displayAlert(message);
}

function closeAlert() {
    window.erickApp.closeAlert();
}

function loadContent(page) {
    window.erickApp.loadContent(page);
}

// =====================================================
// 📁 RAPPORTS - Chargement dynamique
// =====================================================

// Charger la liste des activités et remplir le sélecteur
window.chargerActivitesFiltre = async function() {
    try {
        // Attendre que l'élément soit disponible
        let selectDiv = document.getElementById('filtre-activite');
        let attempts = 0;
        while (!selectDiv && attempts < 50) {
            await new Promise(resolve => setTimeout(resolve, 10));
            selectDiv = document.getElementById('filtre-activite');
            attempts++;
        }
        
        if (!selectDiv) {
            console.warn('Élément #filtre-activite non trouvé après 500ms');
            return;
        }
        
        console.log('Chargement des activités...');
        
        const response = await fetch('api_rapports.php?action=activites');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const activites = await response.json();
        
        console.log('Activités reçues:', activites);
        
        // Garder l'option par défaut
        let html = '<option value="">-- Tous les rapports --</option>';
        
        activites.forEach(activite => {
            html += `<option value="${activite.id}">${activite.nom}</option>`;
        });
        
        selectDiv.innerHTML = html;
        console.log('Filtre d\'activités rempli avec', activites.length, 'activités');
        
        // Ajouter l'écouteur d'événement pour le changement de filtre
        selectDiv.addEventListener('change', function() {
            console.log('Filtrage par activité:', this.value);
            window.chargerRapports(this.value);
        });
    } catch (error) {
        console.error('Erreur lors du chargement des activités:', error);
    }
};

window.chargerRapports = async function(activiteId = '') {
    try {
        let url = 'api_rapports.php?action=list';
        if (activiteId) {
            url += '&activite_id=' + activiteId;
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const rapports = await response.json();

        const listDiv = document.getElementById('rapports-list');
        if (!listDiv) return;

        if (!Array.isArray(rapports) || rapports.length === 0) {
            listDiv.innerHTML = '<div class="empty-state">Aucun rapport enregistré</div>';
            return;
        }

        let html = '';
        rapports.forEach(rapport => {
            const date = new Date(rapport.created_at).toLocaleDateString('fr-FR');
            html += `
                <div class="rapport-item">
                    <div class="rapport-info">
                        <div class="rapport-nom">${rapport.nom}</div>
                        <div class="rapport-details">
                            Commune: <strong>${rapport.commune || 'N/A'}</strong> | 
                            Activité: <strong>${rapport.activite || 'N/A'}</strong> | 
                            Créé: <strong>${date}</strong>
                        </div>
                    </div>
                    <div class="rapport-actions">
                        <button class="btn-download" onclick="telechargerRapport(${rapport.id})">⬇️ Télécharger</button>
                        <button class="btn-rename" onclick="renommerRapport(${rapport.id}, '${rapport.nom}')">✏️ Renommer</button>
                        <button class="btn-delete-rapport" onclick="supprimerRapport(${rapport.id})">🗑️ Supprimer</button>
                    </div>
                </div>
            `;
        });

        listDiv.innerHTML = html;
    } catch (error) {
        const listDiv = document.getElementById('rapports-list');
        if (listDiv) {
            listDiv.innerHTML = '<div class="empty-state" style="color: red;">Erreur: ' + error.message + '</div>';
        }
    }
};

window.telechargerRapport = function(id) {
    window.location.href = 'api_rapports.php?action=download&id=' + id;
};

window.renommerRapport = function(id, nomActuel) {
    window.showPrompt('Nouveau nom pour le rapport:', nomActuel, function(nouveauNom) {
        if (!nouveauNom || nouveauNom === nomActuel) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('nouveau_nom', nouveauNom);

        fetch('api_rapports.php?action=rename', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAlert('✅ Rapport renommé avec succès!');
                // Recharger avec le filtre actuel
                const selectDiv = document.getElementById('filtre-activite');
                const activiteId = selectDiv ? selectDiv.value : '';
                window.chargerRapports(activiteId);
            } else {
                displayAlert('❌ Erreur: ' + data.error);
            }
        })
        .catch(error => displayAlert('❌ Erreur: ' + error.message));
    });
};

// ===== GESTION DES SUPPRESSIONS DE RAPPORT =====
// Variables globales pour gérer le modal
let deleteRapportCallback = null;

// Créer le modal une seule fois au démarrage
function initDeleteRapportModal() {
    if (document.getElementById('deleteRapportModal')) return;
    
    const modalHTML = `
        <div id="deleteRapportModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center;">
            <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3); max-width: 400px; text-align: center; animation: slideIn 0.3s ease;">
                <h2 style="color: #ef4444; margin-bottom: 15px; font-size: 20px;">🗑️ Confirmer la suppression</h2>
                <p id="deleteRapportMessage" style="color: #666; margin-bottom: 25px; font-size: 14px; line-height: 1.6;"></p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button onclick="cancelDeleteRapport()" style="flex: 1; padding: 12px; background: #e5e7eb; color: #333; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background 0.2s;">❌ Annuler</button>
                    <button onclick="confirmDeleteRapport()" style="flex: 1; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background 0.2s;">✅ Supprimer</button>
                </div>
            </div>
        </div>
        <style>
            @keyframes slideIn {
                from { transform: scale(0.9); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
        </style>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

window.showDeleteRapportConfirm = function(message, callback) {
    initDeleteRapportModal();
    deleteRapportCallback = callback;
    document.getElementById('deleteRapportMessage').innerHTML = message;
    document.getElementById('deleteRapportModal').style.display = 'flex';
};

window.cancelDeleteRapport = function() {
    document.getElementById('deleteRapportModal').style.display = 'none';
    deleteRapportCallback = null;
};

window.confirmDeleteRapport = function() {
    if (deleteRapportCallback) {
        deleteRapportCallback();
    }
    document.getElementById('deleteRapportModal').style.display = 'none';
};

window.supprimerRapport = function(id) {
    window.showDeleteRapportConfirm('Êtes-vous sûr de vouloir supprimer ce rapport ?<br><strong>Cette action est irréversible.</strong>', function() {
        const formData = new FormData();
        formData.append('id', id);

        fetch('api_rapports.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAlert('✅ Rapport supprimé avec succès!');
                // Recharger avec le filtre actuel
                const selectDiv = document.getElementById('filtre-activite');
                const activiteId = selectDiv ? selectDiv.value : '';
                window.chargerRapports(activiteId);
            } else {
                displayAlert('❌ Erreur: ' + data.error);
            }
        })
        .catch(error => displayAlert('❌ Erreur: ' + error.message));
    });
};

// =====================================================
// 🗂️ GESTION DES ONGLETS PARAMÈTRES
// =====================================================

function switchTab(tabName) {
    // Masquer tous les onglets
    const allTabs = document.querySelectorAll('.tab-content');
    allTabs.forEach(tab => tab.classList.remove('active'));
    
    // Enlever la classe active de tous les boutons
    const allButtons = document.querySelectorAll('.tab-button');
    allButtons.forEach(btn => btn.classList.remove('active'));
    
    // Afficher l'onglet sélectionné
    const selectedTab = document.getElementById(`tab-${tabName}`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Ajouter la classe active au bouton cliqué
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    // Mettre à jour le count
    updateCount(tabName);
}

function showMessage(table, type, message) {
    const msgDiv = document.getElementById(`message-${table}`);
    if (!msgDiv) return;
    msgDiv.className = `message ${type}`;
    msgDiv.textContent = message;
    setTimeout(() => msgDiv.textContent = '', 3000);
}

function addItem(table) {
    const input = document.getElementById(`input-${table}`);
    if (!input) return;
    const nom = input.value.trim();
    
    if (!nom) {
        showMessage(table, 'error', 'Veuillez entrer un nom.');
        return;
    }

    fetch('gestion_parametres.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&table=${table}&nom=${encodeURIComponent(nom)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showMessage(table, 'success', data.message);
            input.value = '';
            reloadSection(table);
        } else {
            showMessage(table, 'error', data.error);
        }
    });
}

function editItem(table, id, oldNom) {
    window.showPrompt('Modifier:', oldNom, function(newNom) {
        if (!newNom || newNom === oldNom) return;
        fetch('gestion_parametres.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&table=${table}&id=${id}&nom=${encodeURIComponent(newNom)}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showMessage(table, 'success', data.message);
                reloadSection(table);
            } else {
                showMessage(table, 'error', data.error);
            }
        });
    });
}

function deleteItem(table, id) {
    window.showConfirm('Êtes-vous sûr de vouloir supprimer cet élément ?', function() {
        fetch('gestion_parametres.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete&table=${table}&id=${id}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showMessage(table, 'success', data.message);
                reloadSection(table);
            } else {
                showMessage(table, 'error', data.error);
            }
        });
    }, function(){});
}

function reloadSection(table) {
    fetch(`gestion_parametres.php?action=getAll&table=${table}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=getAll&table=${table}`
    })
    .then(r => r.json())
    .then(response => {
        if (response.success) {
            const list = document.getElementById(`list-${table}`);
            if (response.data.length === 0) {
                list.innerHTML = '<div class="empty-state">Aucun élément enregistré</div>';
            } else {
                const sorted = response.data.sort((a, b) => a.nom.localeCompare(b.nom, 'fr', { sensitivity: 'base' }));
                list.innerHTML = sorted.map(item => `
                    <div class="item" data-id="${item.id}">
                        <span class="item-name">${escapeHtml(item.nom)}</span>
                        <div class="item-actions">
                            <button class="btn-edit" onclick="editItem('${table}', ${item.id}, '${escapeHtml(item.nom)}')">✏️</button>
                            <button class="btn-delete" onclick="deleteItem('${table}', ${item.id})">🗑️</button>
                        </div>
                    </div>
                `).join('');
            }
            updateCount(table);
            const search = document.getElementById(`search-${table}`);
            if (search) {
                filterList(table, search.value || '');
            }
        }
    });
}

function updateCount(table) {
    const list = document.getElementById(`list-${table}`);
    const count = document.getElementById(`count-${table}`);
    if (!list || !count) return;
    const items = list.querySelectorAll('.item:not(.is-hidden)');
    count.textContent = items.length;
}

function filterList(table, query) {
    const list = document.getElementById(`list-${table}`);
    if (!list) return;
    const normalized = query.trim().toLowerCase();
    const items = list.querySelectorAll('.item');
    items.forEach(item => {
        const nameSpan = item.querySelector('.item-name');
        const rawName = nameSpan ? nameSpan.textContent : '';
        const name = rawName.toLowerCase();
        const shouldShow = !normalized || name.includes(normalized);
        item.classList.toggle('is-hidden', !shouldShow);
        if (nameSpan) {
            nameSpan.innerHTML = highlightText(escapeHtml(rawName), normalized);
        }
    });
    updateCount(table);
}

function highlightText(text, query) {
    if (!query) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp(escaped, 'gi'), match => `<span class="highlight">${match}</span>`);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}