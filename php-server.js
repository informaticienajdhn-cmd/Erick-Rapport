/**
 * Serveur PHP intégré pour ERICKRAPPORT
 * @author SOMBINIAINA Erick
 * @version 2.0.0
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs-extra');
const os = require('os');

class PortablePHPServer {
    constructor() {
        this.phpProcess = null;
        this.port = 8080;
        this.host = '127.0.0.1';
        this.appPath = null;
        this.phpPath = null;
    }

    /**
     * Initialise le serveur PHP portable
     */
    async initialize(appPath) {
        this.appPath = appPath;
        
        try {
            // Détecter PHP
            await this.detectPHP();
            
            // Créer le fichier de configuration PHP
            await this.createPHPConfig();
            
            // Démarrer le serveur
            await this.startServer();
            
            console.log(`✅ Serveur PHP démarré sur http://${this.host}:${this.port}`);
            return `http://${this.host}:${this.port}`;
            
        } catch (error) {
            console.error('❌ Erreur serveur PHP:', error.message);
            throw error;
        }
    }

    /**
     * Détecte l'installation PHP disponible
     */
    async detectPHP() {
        const possiblePaths = [
            // WAMP/XAMPP
            'C:\\wamp64\\bin\\php\\php8.2.13\\php.exe',
            'C:\\wamp64\\bin\\php\\php8.1.13\\php.exe',
            'C:\\wamp64\\bin\\php\\php8.0.30\\php.exe',
            'C:\\wamp\\bin\\php\\php8.2.13\\php.exe',
            'C:\\xampp\\php\\php.exe',
            
            // PHP portable
            path.join(this.appPath, 'php-portable', 'php', 'php.exe'),
            
            // PHP système
            'php.exe',
            'C:\\Program Files\\PHP\\php.exe'
        ];

        for (const phpPath of possiblePaths) {
            if (await this.testPHPPath(phpPath)) {
                this.phpPath = phpPath;
                console.log(`✅ PHP détecté: ${phpPath}`);
                return;
            }
        }

        throw new Error('Aucune installation PHP trouvée. Veuillez installer PHP ou WAMP/XAMPP.');
    }

    /**
     * Teste si un chemin PHP est valide
     */
    async testPHPPath(phpPath) {
        try {
            if (!fs.existsSync(phpPath)) {
                return false;
            }

            // Test rapide de PHP
            const result = await this.execCommand(phpPath, ['-v']);
            return result.includes('PHP');
        } catch {
            return false;
        }
    }

    /**
     * Crée la configuration PHP pour le serveur
     */
    async createPHPConfig() {
        const phpIniContent = `
; Configuration PHP pour ERICKRAPPORT Portable
; @author SOMBINIAINA Erick

[PHP]
; Configuration de base
engine = On
short_open_tag = On
precision = 14
output_buffering = 4096
zlib.output_compression = Off
implicit_flush = Off
unserialize_callback_func =
serialize_precision = -1
disable_functions =
disable_classes =
zend.enable_gc = On

; Uploads
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 20
post_max_size = 10M

; Timeouts
max_execution_time = 300
max_input_time = 300
memory_limit = 512M

; Extensions requises
extension=zip
extension=xml
extension=intl
extension=mbstring
extension=curl
extension=gd
extension=mysqli

; Sessions
session.save_handler = files
session.save_path = "${path.join(this.appPath, 'temp')}"
session.use_cookies = 1
session.cookie_httponly = 1

; Logs
log_errors = On
error_log = "${path.join(this.appPath, 'logs', 'php_errors.log')}"

; Sécurité
expose_php = Off
allow_url_fopen = On
allow_url_include = Off

; Autres
date.timezone = "Indian/Antananarivo"
default_charset = "UTF-8"
`;

        const phpIniPath = path.join(this.appPath, 'php.ini');
        await fs.writeFile(phpIniPath, phpIniContent);
        console.log(`✅ Configuration PHP créée: ${phpIniPath}`);
    }

    /**
     * Démarre le serveur PHP
     */
    async startServer() {
        return new Promise((resolve, reject) => {
            // Trouver un port libre
            this.findFreePort().then(port => {
                this.port = port;
                
                const args = [
                    '-S', `${this.host}:${this.port}`,
                    '-t', this.appPath,
                    '-c', path.join(this.appPath, 'php.ini')
                ];

                console.log(`🚀 Démarrage serveur PHP: ${this.phpPath} ${args.join(' ')}`);

                this.phpProcess = spawn(this.phpPath, args, {
                    cwd: this.appPath,
                    stdio: ['ignore', 'pipe', 'pipe']
                });

                this.phpProcess.stdout.on('data', (data) => {
                    console.log(`PHP Server: ${data.toString().trim()}`);
                });

                this.phpProcess.stderr.on('data', (data) => {
                    console.error(`PHP Error: ${data.toString().trim()}`);
                });

                this.phpProcess.on('error', (error) => {
                    console.error(`❌ Erreur serveur PHP: ${error.message}`);
                    reject(error);
                });

                // Attendre que le serveur démarre
                setTimeout(() => {
                    if (this.phpProcess && !this.phpProcess.killed) {
                        resolve();
                    } else {
                        reject(new Error('Le serveur PHP n\'a pas pu démarrer'));
                    }
                }, 2000);
            });
        });
    }

    /**
     * Trouve un port libre
     */
    async findFreePort(startPort = 8080) {
        const net = require('net');
        
        return new Promise((resolve, reject) => {
            const server = net.createServer();
            
            server.listen(startPort, () => {
                const port = server.address().port;
                server.close(() => resolve(port));
            });
            
            server.on('error', () => {
                // Port occupé, essayer le suivant
                this.findFreePort(startPort + 1).then(resolve).catch(reject);
            });
        });
    }

    /**
     * Exécute une commande et retourne le résultat
     */
    execCommand(command, args = []) {
        return new Promise((resolve, reject) => {
            const process = spawn(command, args, { stdio: 'pipe' });
            let output = '';
            let error = '';

            process.stdout.on('data', (data) => {
                output += data.toString();
            });

            process.stderr.on('data', (data) => {
                error += data.toString();
            });

            process.on('close', (code) => {
                if (code === 0) {
                    resolve(output);
                } else {
                    reject(new Error(error || `Commande échouée avec le code ${code}`));
                }
            });
        });
    }

    /**
     * Arrête le serveur PHP
     */
    stop() {
        if (this.phpProcess && !this.phpProcess.killed) {
            console.log('🛑 Arrêt du serveur PHP...');
            this.phpProcess.kill();
            this.phpProcess = null;
        }
    }

    /**
     * Vérifie si le serveur est en cours d'exécution
     */
    isRunning() {
        return this.phpProcess && !this.phpProcess.killed;
    }
}

module.exports = PortablePHPServer;
