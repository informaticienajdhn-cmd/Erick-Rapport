# 🚀 ERICKRAPPORT v2.1.0 - Version Portable

## 📋 Description

Application complète de gestion de rapports Excel avec fusion automatique, totalement portable et autonome. Aucune installation de serveur web (WAMP/XAMPP) requise !

## ✨ Caractéristiques

- ✅ **100% Portable** - Fonctionne sans installation système
- ✅ **PHP Embarqué** - Inclut son propre environnement PHP
- ✅ **Base SQLite** - Pas de configuration MySQL
- ✅ **Auto-installeur** - Installation en 1 clic
- ✅ **Cross-platform Ready** - Windows 7/8/10/11 (64-bit)

## 📦 Deux méthodes d'utilisation

### Méthode 1 : Version Développement (Dossier actuel)

Si vous êtes dans le dossier de développement avec WAMP :

```cmd
start.bat
```

### Méthode 2 : Version Portable Complète

Pour créer une version distribuable avec PHP embarqué :

#### Étape 1 : Créer le package portable
```cmd
package-portable.bat
```

Cela crée un fichier `ERICKRAPPORT-Portable-v2.1.0.zip` contenant :
- Tous les fichiers de l'application
- Scripts d'installation et de démarrage
- Documentation complète
- Structure de dossiers prête

#### Étape 2 : Distribuer le ZIP

Envoyez le fichier ZIP créé à vos utilisateurs.

#### Étape 3 : Installation par l'utilisateur final

L'utilisateur doit :

1. **Extraire le ZIP** dans un dossier de son choix
   ```
   C:\ERICKRAPPORT\
   ```

2. **Exécuter INSTALLER.bat**
   - Télécharge automatiquement PHP 8.2 (30 MB)
   - Active les extensions nécessaires (zip, mbstring, sqlite)
   - Installe les dépendances Composer (PhpSpreadsheet)
   - Initialise la base de données SQLite
   - Crée un raccourci de démarrage

3. **Double-cliquer sur "Demarrer ERICKRAPPORT.lnk"**
   - Démarre le serveur PHP intégré
   - Ouvre automatiquement le navigateur
   - URL : http://127.0.0.1:8080

## 🛠️ Configuration Requise

### Pour l'Utilisateur Final :
- Windows 7/8/10/11 (64-bit)
- 100 MB d'espace disque
- Connexion Internet (installation uniquement)
- Navigateur moderne (Chrome, Firefox, Edge)

### Pour le Développeur :
- PHP 7.4+ avec extensions : zip, mbstring, pdo_sqlite
- Composer (pour créer le package)

## 📂 Structure du Package Portable

```
ERICKRAPPORT-Portable-v2.1.0/
│
├── INSTALLER.bat           # Script d'installation (télécharge PHP)
├── DEMARRER.bat           # Lance l'application
├── README.txt             # Instructions simples
├── composer.json          # Dépendances
├── composer.phar          # Composer embarqué
│
├── classes/               # Classes PHP
├── js/                    # Scripts JavaScript
├── vendor/                # Dépendances (PhpSpreadsheet)
├── uploads/               # Fichiers uploadés
├── logs/                  # Logs applicatifs
├── database/              # Base SQLite
└── temp/                  # Fichiers temporaires
```

Après installation, un dossier `php-portable/` sera créé avec PHP 8.2.

## 🚀 Utilisation

### Pour le Développeur :

#### Créer le package distributable :
```cmd
package-portable.bat
```

Cela génère `ERICKRAPPORT-Portable-v2.1.0.zip` (environ 5-10 MB sans PHP).

### Pour l'Utilisateur Final :

#### Installation :
1. Extraire le ZIP
2. Exécuter `INSTALLER.bat` (une seule fois)
3. Patienter pendant le téléchargement de PHP (~2 minutes)

#### Démarrage quotidien :
- Double-clic sur `Demarrer ERICKRAPPORT.lnk`
- OU exécuter `DEMARRER.bat`

#### Arrêt :
- Appuyer sur `Ctrl+C` dans la fenêtre console
- OU fermer la fenêtre

## 🔧 Fonctionnalités de l'Application

1. **PARAMÈTRES**
   - Gestion des terroirs
   - Gestion des communes
   - Gestion des activités
   - Upload de canevas (pages de garde)
   - Upload de conclusions

2. **FUSION**
   - Fusion automatique de fichiers Excel
   - Sélection des paramètres (terroir, commune, région, district)
   - Génération de rapports complets

3. **RAPPORTS**
   - Liste des rapports enregistrés
   - Téléchargement
   - Suppression avec confirmation

4. **SUIVI PAIEMENT**
   - Upload de fichiers de suivi
   - Gestion des paiements

## 🆘 Dépannage

### "PHP portable non trouvé"
➡️ Exécutez d'abord `INSTALLER.bat`

### "Erreur lors du téléchargement de PHP"
➡️ Vérifiez votre connexion Internet ou téléchargez manuellement depuis :
   https://windows.php.net/downloads/releases/php-8.2.15-Win32-vs16-x64.zip

### "Port 8080 déjà utilisé"
➡️ Modifiez le port dans `DEMARRER.bat` :
```bat
set "PORT=8081"
```

### "Dépendances Composer manquantes"
➡️ Réexécutez `INSTALLER.bat` ou manuellement :
```cmd
php-portable\php.exe composer.phar install
```

## 📊 Base de Données

L'application utilise **SQLite** (fichier `database/erickrapport.db`).

Tables créées automatiquement :
- `terroirs`
- `communes`
- `regions`
- `districts`
- `titres_transfert`
- `activites`
- `canevas_suivi`
- `conclusions_suivi`
- `rapports_enregistres`

## 🔐 Sécurité

- Pas d'accès réseau externe (127.0.0.1 uniquement)
- Validation des fichiers uploadés
- Protection contre les injections SQL (PDO)
- Sessions PHP sécurisées

## 📝 Logs

Les logs sont enregistrés dans `logs/` :
- `error_YYYY-MM-DD.log` - Erreurs PHP
- `debug_fusion.txt` - Logs de fusion

## 🎯 Avantages de la Version Portable

| Critère | Version WAMP | Version Portable |
|---------|--------------|------------------|
| Installation | Complexe (WAMP 300MB+) | Simple (1 clic) |
| Configuration | MySQL, Apache, PHP | Automatique |
| Portabilité | Non | ✅ Oui (USB, réseau) |
| Mises à jour | Manuelles | Incluses |
| Conflits | Possibles (ports) | Isolée |
| Taille | 300MB+ | ~40MB total |

## 👨‍💻 Développement

### Modifier le code :
Les fichiers sources sont dans le package. Après modifications :

```cmd
package-portable.bat
```

Pour recréer le ZIP distributable.

### Changer la version de PHP :
Modifier dans `install-portable.bat` :
```bat
set "PHP_VERSION=8.3.0"
set "PHP_URL=https://windows.php.net/downloads/releases/php-8.3.0-Win32-vs16-x64.zip"
```

## 📞 Support

**Auteur :** SOMBINIAINA Erick  
**Email :** esombiniaina@gmail.com  
**Version :** 2.1.0  
**Date :** Février 2026

## 📜 Licence

MIT License - Libre d'utilisation et de distribution.

## 🎉 Changelog

### v2.1.0 (Février 2026)
- ✅ Version portable complète
- ✅ Auto-installeur avec téléchargement PHP
- ✅ Interface responsive (mobile/tablet/desktop)
- ✅ Modals de confirmation pour suppressions
- ✅ Optimisation CSS et JavaScript
- ✅ Correction bug débordement formulaire fusion

### v2.0.0 (Janvier 2026)
- ✅ Refonte complète de l'interface
- ✅ Migration vers SQLite
- ✅ Ajout système de versioning

---

**🚀 Prêt à distribuer !** Créez votre package avec `package-portable.bat`
