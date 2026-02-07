#!/bin/bash

# ERICKRAPPORT v2.0.0 - Script de démarrage portable
# @author SOMBINIAINA Erick

echo ""
echo "========================================"
echo "   ERICKRAPPORT v2.0.0 - PORTABLE"
echo "========================================"
echo ""

# Vérification de PHP
echo "[INFO] Vérification de PHP..."
if ! command -v php &> /dev/null; then
    echo "[ERREUR] PHP n'est pas installé ou pas dans le PATH"
    echo "[INFO] Veuillez installer PHP"
    exit 1
fi

echo "[OK] PHP détecté"
echo ""

# Vérification des dépendances
echo "[INFO] Vérification des dépendances..."
if [ ! -f "vendor/autoload.php" ]; then
    echo "[INFO] Installation des dépendances Composer..."
    composer install --no-dev --optimize-autoloader
    if [ $? -ne 0 ]; then
        echo "[ERREUR] Échec de l'installation des dépendances"
        exit 1
    fi
fi

echo "[OK] Dépendances installées"
echo ""

# Création des dossiers nécessaires
echo "[INFO] Création des dossiers nécessaires..."
mkdir -p uploads logs temp

echo "[OK] Dossiers créés"
echo ""

# Démarrage du serveur
echo "[INFO] Démarrage du serveur..."
echo ""
echo "========================================"
echo "  SERVEUR DÉMARRÉ SUR http://127.0.0.1:8080"
echo "========================================"
echo ""
echo "[INFO] Ouvrez votre navigateur à l'adresse ci-dessus"
echo "[INFO] Appuyez sur Ctrl+C pour arrêter le serveur"
echo ""

php launcher.php
