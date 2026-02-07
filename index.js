/**
 * Application Electron pour ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

const fs = require('fs-extra');
const path = require('path');
const os = require('os');
const { app, BrowserWindow, dialog, shell } = require('electron');
const { execSync, spawn } = require('child_process');

// Configuration de l'application
const APP_CONFIG = {
    name: 'ERICKRAPPORT',
    version: '2.0.0',
    author: 'SOMBINIAINA Erick',
    defaultPort: 80,
    fallbackPort: 8080,
    wampPaths: [
        'C:\\wamp64\\www',
        'C:\\wamp\\www',
        'C:\\xampp\\htdocs',
        path.join(os.homedir(), 'www')
    ]
};

class ErickRapportElectron {
    constructor() {
        this.mainWindow = null;
        this.serverUrl = null;
        this.wampPath = null;
        this.destinationPath = null;
    }

    /**
     * Initialise l'application
     */
    async initialize() {
        try {
            console.log(`🚀 Démarrage de ${APP_CONFIG.name} v${APP_CONFIG.version}`);
            
            // Vérification des dépendances
            await this.checkDependencies();
            
            // Détection du serveur web
            await this.detectWebServer();
            
            // Installation des fichiers
            await this.installFiles();
            
            // Création de la fenêtre principale
            await this.createMainWindow();
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'initialisation:', error.message);
            await this.showErrorDialog('Erreur d\'initialisation', error.message);
            app.quit();
        }
    }

    /**
     * Vérifie les dépendances requises
     */
    async checkDependencies() {
        try {
            // Vérification de fs-extra
            require('fs-extra');
            console.log('✅ Dépendance fs-extra trouvée');
        } catch (error) {
            throw new Error('Le module fs-extra est requis. Exécutez: npm install fs-extra');
        }
    }

    /**
     * Détecte et configure le serveur web
     */
    async detectWebServer() {
        console.log('🔍 Détection du serveur web...');
        
        // Recherche du chemin WAMP/XAMPP
        for (const wampPath of APP_CONFIG.wampPaths) {
            if (fs.existsSync(wampPath)) {
                this.wampPath = wampPath;
                this.destinationPath = path.join(wampPath, APP_CONFIG.name);
                console.log(`✅ Serveur web détecté: ${wampPath}`);
                break;
            }
        }

        if (!this.wampPath) {
            // Création d'un dossier www par défaut
            this.wampPath = path.join(os.homedir(), 'www');
            this.destinationPath = path.join(this.wampPath, APP_CONFIG.name);
            
            if (!fs.existsSync(this.wampPath)) {
                fs.mkdirSync(this.wampPath, { recursive: true });
            }
            
            console.log(`⚠️ Aucun serveur web détecté, utilisation de: ${this.wampPath}`);
        }

        // Vérification du serveur Apache/Nginx
        const isServerRunning = await this.checkWebServerStatus();
        if (isServerRunning) {
            console.log('✅ Serveur web actif');
} else {
            console.log('⚠️ Serveur web non détecté, l\'application fonctionnera en mode local');
        }

        // Configuration de l'URL du serveur
        this.serverUrl = isServerRunning ? 
            `http://localhost/${APP_CONFIG.name}/` : 
            `file://${this.destinationPath}/index.html`;
    }

    /**
     * Vérifie le statut du serveur web
     */
    async checkWebServerStatus() {
        try {
            // Vérification du port 80
            if (process.platform === 'win32') {
                execSync('netstat -ano | findstr :80', { stdio: 'pipe' });
            } else {
                execSync('lsof -i :80', { stdio: 'pipe' });
            }
            return true;
} catch (error) {
            // Vérification du port alternatif
            try {
                if (process.platform === 'win32') {
                    execSync(`netstat -ano | findstr :${APP_CONFIG.fallbackPort}`, { stdio: 'pipe' });
                } else {
                    execSync(`lsof -i :${APP_CONFIG.fallbackPort}`, { stdio: 'pipe' });
                }
                this.serverUrl = `http://localhost:${APP_CONFIG.fallbackPort}/${APP_CONFIG.name}/`;
                return true;
            } catch (fallbackError) {
                return false;
            }
        }
    }

    /**
     * Installe les fichiers de l'application
     */
    async installFiles() {
        try {
            const sourcePath = this.getSourcePath();
            
            console.log(`📂 Installation des fichiers...`);
            console.log(`   Source: ${sourcePath}`);
            console.log(`   Destination: ${this.destinationPath}`);

            // Vérification de l'existence du dossier source
            if (!fs.existsSync(sourcePath)) {
                throw new Error(`Dossier source introuvable: ${sourcePath}`);
            }

            // Installation ou mise à jour
            if (fs.existsSync(this.destinationPath)) {
                console.log('🔄 Mise à jour des fichiers existants...');
                await this.updateFiles(sourcePath, this.destinationPath);
            } else {
                console.log('📥 Installation initiale...');
                await fs.copy(sourcePath, this.destinationPath);
            }

            // Création des dossiers nécessaires
            await this.createRequiredDirectories();
            
            console.log('✅ Installation terminée');

} catch (error) {
            throw new Error(`Erreur lors de l'installation: ${error.message}`);
        }
    }

    /**
     * Obtient le chemin source des fichiers
     */
    getSourcePath() {
        // En mode développement
        if (process.env.NODE_ENV === 'development') {
            return __dirname;
        }
        
        // En mode production (application empaquetée)
        if (process.resourcesPath) {
            return path.join(process.resourcesPath, APP_CONFIG.name);
        }
        
        // Fallback
        return __dirname;
    }

    /**
     * Met à jour les fichiers existants
     */
    async updateFiles(source, destination) {
        const filesToUpdate = [
            'index.php', 'acceuil_fusion.php', 'acceuil_suivi_paiement.php',
            'fusionner.php', 'upload_fusion.php', 'upload_suivi_paiement.php',
            'getProgress.php', 'styles.css', 'config.php'
        ];

        for (const file of filesToUpdate) {
            const srcFile = path.join(source, file);
            const destFile = path.join(destination, file);
            
            if (fs.existsSync(srcFile)) {
                await fs.copy(srcFile, destFile);
                console.log(`   ✅ ${file} mis à jour`);
            }
        }

        // Mise à jour des dossiers
        const foldersToUpdate = ['classes', 'js'];
        for (const folder of foldersToUpdate) {
            const srcFolder = path.join(source, folder);
            const destFolder = path.join(destination, folder);
            
            if (fs.existsSync(srcFolder)) {
                await fs.copy(srcFolder, destFolder);
                console.log(`   ✅ Dossier ${folder} mis à jour`);
            }
        }
    }

    /**
     * Crée les dossiers requis
     */
    async createRequiredDirectories() {
        const requiredDirs = ['uploads', 'logs', 'temp'];
        
        for (const dir of requiredDirs) {
            const dirPath = path.join(this.destinationPath, dir);
            if (!fs.existsSync(dirPath)) {
                await fs.mkdir(dirPath, { recursive: true });
                console.log(`   📁 Dossier créé: ${dir}`);
            }
        }
    }

    /**
     * Crée la fenêtre principale
     */
    async createMainWindow() {
        this.mainWindow = new BrowserWindow({
            width: 1200,
            height: 800,
            minWidth: 800,
            minHeight: 600,
            webPreferences: {
                nodeIntegration: false,
                contextIsolation: true,
                enableRemoteModule: false,
                webSecurity: true
            },
            icon: this.getAppIcon(),
            title: `${APP_CONFIG.name} v${APP_CONFIG.version}`,
            show: false // Ne pas afficher immédiatement
        });

        // Chargement de l'application
        console.log(`🌐 Chargement de l'application: ${this.serverUrl}`);
        await this.mainWindow.loadURL(this.serverUrl);

        // Affichage de la fenêtre une fois chargée
        this.mainWindow.once('ready-to-show', () => {
            this.mainWindow.show();
            console.log('✅ Application prête');
        });

        // Gestion des liens externes
        this.mainWindow.webContents.setWindowOpenHandler(({ url }) => {
            shell.openExternal(url);
            return { action: 'deny' };
        });

        // Gestion de la fermeture
        this.mainWindow.on('closed', () => {
            this.mainWindow = null;
        });
    }

    /**
     * Obtient l'icône de l'application
     */
    getAppIcon() {
        const iconPath = path.join(__dirname, 'resources', 'icon.ico');
        return fs.existsSync(iconPath) ? iconPath : null;
    }

    /**
     * Affiche une boîte de dialogue d'erreur
     */
    async showErrorDialog(title, message) {
        if (app.isReady()) {
            await dialog.showErrorBox(title, message);
        } else {
            console.error(`${title}: ${message}`);
        }
    }
}

// Instance de l'application
const erickApp = new ErickRapportElectron();

// Gestion des événements Electron
app.whenReady().then(async () => {
    try {
        await erickApp.initialize();
    } catch (error) {
        console.error('❌ Erreur fatale:', error);
        app.quit();
    }
});

// Gestion de la fermeture de l'application
app.on('window-all-closed', () => {
    // Sur macOS, les applications restent actives même quand toutes les fenêtres sont fermées
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

// Gestion de la réactivation sur macOS
app.on('activate', async () => {
    // Sur macOS, recréer une fenêtre quand l'icône du dock est cliquée
    if (BrowserWindow.getAllWindows().length === 0) {
        await erickApp.createMainWindow();
    }
});

// Gestion de la fermeture propre
app.on('before-quit', (event) => {
    console.log('🔄 Fermeture de l\'application...');
});

// Gestion des erreurs non capturées
process.on('uncaughtException', (error) => {
    console.error('❌ Erreur non capturée:', error);
    erickApp.showErrorDialog('Erreur critique', error.message);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Promesse rejetée non gérée:', reason);
});
