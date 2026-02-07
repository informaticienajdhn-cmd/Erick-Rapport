@echo off
title Packaging ERICKRAPPORT Portable
color 0E
setlocal enabledelayedexpansion

echo.
echo ========================================
echo   ERICKRAPPORT - CREATION DU PACKAGE
echo ========================================
echo.

:: Configuration
set "APP_DIR=%~dp0"
set "APP_NAME=ERICKRAPPORT-Portable-v2.1.0"
set "OUTPUT_ZIP=%APP_DIR%%APP_NAME%.zip"
set "TEMP_PKG=%APP_DIR%temp\package"

:: Nettoyer l'ancien package
if exist "%OUTPUT_ZIP%" (
    echo [INFO] Suppression de l'ancien package...
    del "%OUTPUT_ZIP%"
)

:: Créer le dossier temporaire
if exist "%TEMP_PKG%" rd /s /q "%TEMP_PKG%"
mkdir "%TEMP_PKG%"

echo [1/4] Copie des fichiers essentiels...

:: Copier les fichiers nécessaires (exclure les fichiers de développement)
xcopy /E /I /Y "%APP_DIR%classes" "%TEMP_PKG%\classes\" >nul
xcopy /E /I /Y "%APP_DIR%css" "%TEMP_PKG%\css\" >nul 2>nul
xcopy /E /I /Y "%APP_DIR%js" "%TEMP_PKG%\js\" >nul
xcopy /E /I /Y "%APP_DIR%logo" "%TEMP_PKG%\logo\" >nul 2>nul
xcopy /E /I /Y "%APP_DIR%vendor" "%TEMP_PKG%\vendor\" >nul

:: Copier les fichiers PHP principaux
for %%f in (*.php) do (
    if not "%%f"=="package-portable.bat" (
        copy /Y "%%f" "%TEMP_PKG%\" >nul 2>nul
    )
)

:: Copier les fichiers de configuration
copy /Y "%APP_DIR%composer.json" "%TEMP_PKG%\" >nul
copy /Y "%APP_DIR%composer.phar" "%TEMP_PKG%\" >nul 2>nul
copy /Y "%APP_DIR%*.css" "%TEMP_PKG%\" >nul 2>nul
copy /Y "%APP_DIR%*.js" "%TEMP_PKG%\" >nul 2>nul

:: Copier les scripts de démarrage
copy /Y "%APP_DIR%install-portable.bat" "%TEMP_PKG%\INSTALLER.bat" >nul
copy /Y "%APP_DIR%start-portable.bat" "%TEMP_PKG%\DEMARRER.bat" >nul

:: Créer les dossiers vides
mkdir "%TEMP_PKG%\uploads" >nul 2>nul
mkdir "%TEMP_PKG%\logs" >nul 2>nul
mkdir "%TEMP_PKG%\temp" >nul 2>nul
mkdir "%TEMP_PKG%\database" >nul 2>nul

:: Créer un fichier README
echo [2/4] Creation du fichier README...
(
echo ========================================
echo   ERICKRAPPORT v2.1.0 - PORTABLE
echo ========================================
echo.
echo APPLICATION DE GESTION DE RAPPORTS EXCEL
echo.
echo ----------------------------------------
echo INSTALLATION :
echo ----------------------------------------
echo.
echo 1. Executez "INSTALLER.bat"
echo    - Telecharge PHP portable ^(30 MB^)
echo    - Installe les dependances
echo    - Configure l'application
echo.
echo 2. Une fois l'installation terminee,
echo    double-cliquez sur "DEMARRER.bat"
echo.
echo 3. L'application s'ouvrira automatiquement
echo    dans votre navigateur sur :
echo    http://127.0.0.1:8080
echo.
echo ----------------------------------------
echo CONFIGURATION REQUISE :
echo ----------------------------------------
echo.
echo - Windows 7/8/10/11 ^(64-bit^)
echo - 100 MB d'espace disque
echo - Connexion Internet ^(pour installation uniquement^)
echo.
echo ----------------------------------------
echo CONTENU DU PACKAGE :
echo ----------------------------------------
echo.
echo - INSTALLER.bat : Script d'installation
echo - DEMARRER.bat  : Lanceur de l'application
echo - classes/      : Classes PHP
echo - js/           : Scripts JavaScript
echo - vendor/       : Dependances PHP
echo - uploads/      : Fichiers uploades
echo - database/     : Base de donnees SQLite
echo.
echo ----------------------------------------
echo UTILISATION :
echo ----------------------------------------
echo.
echo 1. PARAMETRES : Configurez vos terroirs,
echo    communes, activites
echo.
echo 2. CANEVAS : Uploadez vos pages de garde
echo    et conclusions
echo.
echo 3. FUSION : Fusionnez vos fichiers Excel
echo.
echo 4. RAPPORTS : Consultez et telechargez
echo    vos rapports enregistres
echo.
echo 5. SUIVI PAIEMENT : Gerez les paiements
echo.
echo ----------------------------------------
echo SUPPORT :
echo ----------------------------------------
echo.
echo Auteur : SOMBINIAINA Erick
echo Email  : esombiniaina@gmail.com
echo Version: 2.1.0
echo Date   : Fevrier 2026
echo.
echo ========================================
) > "%TEMP_PKG%\README.txt"

echo [OK] README.txt cree

:: Créer le fichier .gitignore pour la version portable
echo [3/4] Creation des fichiers de configuration...
(
echo # Fichiers generes
echo /uploads/*
echo /logs/*
echo /temp/*
echo !/uploads/.gitkeep
echo !/logs/.gitkeep
echo !/temp/.gitkeep
echo.
echo # PHP Portable
echo /php-portable/*
echo.
echo # Database
echo /database/*.db
echo /database/*.db-journal
echo.
echo # Composer
echo /composer.lock
) > "%TEMP_PKG%\.gitignore"

:: Créer des fichiers .gitkeep
echo. > "%TEMP_PKG%\uploads\.gitkeep"
echo. > "%TEMP_PKG%\logs\.gitkeep"
echo. > "%TEMP_PKG%\temp\.gitkeep"
echo. > "%TEMP_PKG%\database\.gitkeep"

echo [OK] Configuration creee

:: Créer le ZIP avec PowerShell
echo.
echo [4/4] Compression du package...
echo [INFO] Creation de %APP_NAME%.zip...

powershell -Command "& {Compress-Archive -Path '%TEMP_PKG%\*' -DestinationPath '%OUTPUT_ZIP%' -CompressionLevel Optimal -Force; if ($?) {Write-Host '[OK] Package cree avec succes !' -ForegroundColor Green} else {Write-Host '[ERREUR] Echec de la compression' -ForegroundColor Red; exit 1}}"

:: Nettoyer le dossier temporaire
rd /s /q "%TEMP_PKG%" >nul 2>nul

:: Afficher le résultat
echo.
echo ========================================
echo   PACKAGE CREE !
echo ========================================
echo.
echo Fichier : %OUTPUT_ZIP%

:: Obtenir la taille du fichier
for %%A in ("%OUTPUT_ZIP%") do (
    set size=%%~zA
    set /a sizeMB=!size! / 1048576
    echo Taille  : !sizeMB! MB
)

echo.
echo [INFO] Le package est pret a etre distribue !
echo.
echo ========================================
echo   INSTRUCTIONS POUR L'UTILISATEUR :
echo ========================================
echo.
echo 1. Extraire le ZIP dans un dossier
echo 2. Executer INSTALLER.bat
echo 3. Executer DEMARRER.bat
echo.
pause
