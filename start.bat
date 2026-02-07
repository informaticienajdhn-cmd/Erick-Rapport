@echo off
title ERICKRAPPORT v2.0.0 - Serveur Portable
color 0A

echo.
echo ========================================
echo    ERICKRAPPORT v2.0.0 - PORTABLE
echo ========================================
echo.
echo [INFO] Verification de PHP...
php --version >nul 2>&1
if errorlevel 1 (
    echo [ERREUR] PHP n'est pas installe ou pas dans le PATH
    echo [INFO] Telechargement de PHP portable...
    echo [INFO] Veuillez installer PHP ou ajouter PHP au PATH
    pause
    exit /b 1
)

echo [OK] PHP detecte
echo.
echo [INFO] Verification des dependances...
if not exist "vendor\autoload.php" (
    echo [INFO] Installation des dependances Composer...
    composer install --no-dev --optimize-autoloader
    if errorlevel 1 (
        echo [ERREUR] Echec de l'installation des dependances
        pause
        exit /b 1
    )
)

echo [OK] Dependances installees
echo.
echo [INFO] Creation des dossiers necessaires...
if not exist "uploads" mkdir uploads
if not exist "logs" mkdir logs
if not exist "temp" mkdir temp

echo [OK] Dossiers crees
echo.
echo [INFO] Demarrage du serveur...
echo.
echo ========================================
echo   SERVEUR DEMARRE SUR http://127.0.0.1:8080
echo ========================================
echo.
echo [INFO] Ouverture automatique du navigateur...
echo [INFO] Appuyez sur Ctrl+C pour arreter le serveur
echo.

php launcher.php

echo.
echo [INFO] Serveur arrete
pause
