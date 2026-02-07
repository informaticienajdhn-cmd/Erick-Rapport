@echo off
title Installation ERICKRAPPORT Portable
color 0B
setlocal enabledelayedexpansion

echo.
echo ========================================
echo   ERICKRAPPORT v2.1.0 - INSTALLATION
echo ========================================
echo.

:: Définir les chemins
set "APP_DIR=%~dp0"
set "PHP_DIR=%APP_DIR%php-portable"
set "PHP_VERSION=8.2.15"
set "PHP_URL=https://windows.php.net/downloads/releases/php-8.2.15-Win32-vs16-x64.zip"
set "PHP_ZIP=%APP_DIR%temp\php.zip"

:: Créer les dossiers nécessaires
echo [1/6] Creation des dossiers...
if not exist "%APP_DIR%uploads" mkdir "%APP_DIR%uploads"
if not exist "%APP_DIR%logs" mkdir "%APP_DIR%logs"
if not exist "%APP_DIR%temp" mkdir "%APP_DIR%temp"
if not exist "%APP_DIR%database" mkdir "%APP_DIR%database"
echo [OK] Dossiers crees

:: Vérifier si PHP portable existe déjà
echo.
echo [2/6] Verification de PHP portable...
if exist "%PHP_DIR%\php.exe" (
    echo [OK] PHP portable deja installe
    goto :install_composer
)

echo [INFO] PHP portable non trouve. Telechargement...
echo [URL] %PHP_URL%

:: Télécharger PHP avec PowerShell
echo [INFO] Telechargement en cours (environ 30 MB)...
powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; Invoke-WebRequest -Uri '%PHP_URL%' -OutFile '%PHP_ZIP%'; if ($?) {Write-Host '[OK] Telechargement termine' -ForegroundColor Green} else {Write-Host '[ERREUR] Echec du telechargement' -ForegroundColor Red; exit 1}}"

if not exist "%PHP_ZIP%" (
    echo [ERREUR] Echec du telechargement de PHP
    echo [INFO] Veuillez telecharger manuellement PHP depuis:
    echo         https://windows.php.net/download/
    pause
    exit /b 1
)

:: Décompresser PHP
echo.
echo [3/6] Decompression de PHP...
powershell -Command "& {Expand-Archive -Path '%PHP_ZIP%' -DestinationPath '%PHP_DIR%' -Force; if ($?) {Write-Host '[OK] PHP decompresse' -ForegroundColor Green} else {Write-Host '[ERREUR] Echec decompression' -ForegroundColor Red; exit 1}}"

if not exist "%PHP_DIR%\php.exe" (
    echo [ERREUR] Impossible de decompresser PHP
    pause
    exit /b 1
)

:: Configurer PHP
echo.
echo [4/6] Configuration de PHP...
if exist "%PHP_DIR%\php.ini-development" (
    copy /Y "%PHP_DIR%\php.ini-development" "%PHP_DIR%\php.ini" >nul
)

:: Activer les extensions nécessaires
echo [INFO] Activation des extensions...
powershell -Command "& {$ini = Get-Content '%PHP_DIR%\php.ini'; $ini = $ini -replace ';extension=zip', 'extension=zip'; $ini = $ini -replace ';extension=mbstring', 'extension=mbstring'; $ini = $ini -replace ';extension=curl', 'extension=curl'; $ini = $ini -replace ';extension=openssl', 'extension=openssl'; $ini = $ini -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite'; $ini = $ini -replace ';extension=sqlite3', 'extension=sqlite3'; $ini | Set-Content '%PHP_DIR%\php.ini'; Write-Host '[OK] Extensions activees' -ForegroundColor Green}"

:: Nettoyer le ZIP
del "%PHP_ZIP%" >nul 2>&1

:install_composer
echo.
echo [5/6] Installation des dependances Composer...

:: Télécharger Composer si nécessaire
if not exist "%APP_DIR%composer.phar" (
    echo [INFO] Telechargement de Composer...
    "%PHP_DIR%\php.exe" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "%PHP_DIR%\php.exe" composer-setup.php --quiet
    del composer-setup.php >nul 2>&1
)

:: Installer les dépendances
if exist "%APP_DIR%composer.json" (
    echo [INFO] Installation de PhpSpreadsheet...
    "%PHP_DIR%\php.exe" composer.phar install --no-dev --optimize-autoloader --quiet
    if errorlevel 1 (
        echo [AVERTISSEMENT] Erreur lors de l'installation des dependances
        echo [INFO] Vous devrez peut-etre executer: php composer.phar install
    ) else (
        echo [OK] Dependances installees
    )
)

:: Initialiser la base de données
echo.
echo [6/6] Initialisation de la base de donnees...
if exist "%APP_DIR%init_db.php" (
    "%PHP_DIR%\php.exe" "%APP_DIR%init_db.php"
    echo [OK] Base de donnees initialisee
)

:: Créer un raccourci de démarrage
echo.
echo [INFO] Creation du raccourci de demarrage...
powershell -Command "& {$WshShell = New-Object -ComObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%APP_DIR%Demarrer ERICKRAPPORT.lnk'); $Shortcut.TargetPath = '%APP_DIR%start.bat'; $Shortcut.WorkingDirectory = '%APP_DIR%'; $Shortcut.IconLocation = '%PHP_DIR%\php.exe,0'; $Shortcut.Description = 'Lancer ERICKRAPPORT v2.1.0'; $Shortcut.Save(); Write-Host '[OK] Raccourci cree' -ForegroundColor Green}"

:: Afficher le résumé
echo.
echo ========================================
echo   INSTALLATION TERMINEE !
echo ========================================
echo.
echo [INFO] PHP Portable : %PHP_DIR%
echo [INFO] Version PHP  : %PHP_VERSION%
echo [INFO] Database     : %APP_DIR%database\erickrapport.db
echo.
echo ========================================
echo   POUR DEMARRER L'APPLICATION :
echo ========================================
echo.
echo   1. Double-cliquez sur "Demarrer ERICKRAPPORT.lnk"
echo   2. OU executez "start.bat"
echo   3. L'application s'ouvrira sur http://127.0.0.1:8080
echo.
echo [!] Conservez ce dossier complet pour que l'app fonctionne
echo.
pause
