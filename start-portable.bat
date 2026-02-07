@echo off
title ERICKRAPPORT v2.1.0 - Serveur Portable
color 0A
chcp 65001 >nul
setlocal enabledelayedexpansion

:: Définir les chemins
set "APP_DIR=%~dp0"
set "PHP_DIR=%APP_DIR%php-portable"
set "PHP_EXE=%PHP_DIR%\php.exe"
set "HOST=127.0.0.1"
set "PORT=8080"

echo.
echo ========================================
echo    ERICKRAPPORT v2.1.0 - PORTABLE
echo ========================================
echo.

:: Vérifier si PHP portable existe
if not exist "%PHP_EXE%" (
    echo [ERREUR] PHP portable non trouve !
    echo.
    echo [INFO] Vous devez d'abord executer "install-portable.bat"
    echo        pour installer PHP et les dependances.
    echo.
    pause
    exit /b 1
)

:: Afficher la version de PHP
for /f "tokens=*" %%i in ('"%PHP_EXE%" -v') do (
    echo [OK] %%i
    goto :php_ok
)
:php_ok

:: Vérifier les dépendances
echo.
echo [INFO] Verification des dependances...
if not exist "%APP_DIR%vendor\autoload.php" (
    echo [AVERTISSEMENT] Dependances Composer manquantes
    echo [INFO] Installation automatique...
    "%PHP_EXE%" "%APP_DIR%composer.phar" install --no-dev --optimize-autoloader --quiet
    if errorlevel 1 (
        echo [ERREUR] Impossible d'installer les dependances
        echo [INFO] Executez: install-portable.bat
        pause
        exit /b 1
    )
)
echo [OK] Dependances presentes

:: Vérifier la base de données
echo.
echo [INFO] Verification de la base de donnees...
if not exist "%APP_DIR%database\erickrapport.db" (
    echo [INFO] Initialisation de la base de donnees...
    if exist "%APP_DIR%init_db.php" (
        "%PHP_EXE%" "%APP_DIR%init_db.php"
        echo [OK] Base de donnees creee
    )
)

:: Créer les dossiers nécessaires
if not exist "%APP_DIR%uploads" mkdir "%APP_DIR%uploads"
if not exist "%APP_DIR%logs" mkdir "%APP_DIR%logs"
if not exist "%APP_DIR%temp" mkdir "%APP_DIR%temp"

:: Afficher les informations
echo.
echo ========================================
echo   SERVEUR DEMARRE !
echo ========================================
echo.
echo   URL : http://%HOST%:%PORT%
echo   Dossier : %APP_DIR%
echo   PHP : %PHP_EXE%
echo.
echo ========================================
echo.
echo [INFO] Ouverture automatique du navigateur...
echo [!] Appuyez sur Ctrl+C pour arreter le serveur
echo.

:: Ouvrir le navigateur après 2 secondes
start "" /B timeout /t 2 /nobreak >nul && start http://%HOST%:%PORT%

:: Démarrer le serveur PHP intégré
cd /d "%APP_DIR%"
"%PHP_EXE%" -S %HOST%:%PORT% -t "%APP_DIR%" "%APP_DIR%\router.php"

:: Si le serveur s'arrête
echo.
echo [INFO] Serveur arrete
pause
