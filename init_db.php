<?php
/**
 * Script d'initialisation de la base de données avec données de test
 */

require_once 'config.php';
require_once 'classes/Database.php';

echo "Initialisation de la base de données...\n";

$db = Database::getInstance();

// Données de test
$terrainsTest = ['ANDAKANA', 'VOHIBE', 'ANTSINANANA'];
$communesTest = ['ANDAKANA', 'MANAKARA', 'VOHIPENO'];
$regionsTest = ['ATSIMO ATSINANANA', 'VATOVAVY FITOVINANY', 'ANOSY'];
$districtsTest = ['VONDROZO', 'MANAKARA', 'VANGAINDRANO'];
$titresTest = ['TRANSFERT MONETAIRE FSP', 'TRANSFERT MONETAIRE CONDITIONNEL'];

echo "\n=== Ajout des terroirs ===\n";
foreach ($terrainsTest as $nom) {
    try {
        if (!$db->exists('terroirs', $nom)) {
            $db->add('terroirs', $nom);
            echo "✅ Terroir ajouté: $nom\n";
        } else {
            echo "⚠️ Terroir existe déjà: $nom\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Ajout des communes ===\n";
foreach ($communesTest as $nom) {
    try {
        if (!$db->exists('communes', $nom)) {
            $db->add('communes', $nom);
            echo "✅ Commune ajoutée: $nom\n";
        } else {
            echo "⚠️ Commune existe déjà: $nom\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Ajout des régions ===\n";
foreach ($regionsTest as $nom) {
    try {
        if (!$db->exists('regions', $nom)) {
            $db->add('regions', $nom);
            echo "✅ Région ajoutée: $nom\n";
        } else {
            echo "⚠️ Région existe déjà: $nom\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Ajout des districts ===\n";
foreach ($districtsTest as $nom) {
    try {
        if (!$db->exists('districts', $nom)) {
            $db->add('districts', $nom);
            echo "✅ District ajouté: $nom\n";
        } else {
            echo "⚠️ District existe déjà: $nom\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Ajout des titres ===\n";
foreach ($titresTest as $nom) {
    try {
        if (!$db->exists('titres_transfert', $nom)) {
            $db->add('titres_transfert', $nom);
            echo "✅ Titre ajouté: $nom\n";
        } else {
            echo "⚠️ Titre existe déjà: $nom\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Vérification des données ===\n";
echo "Terroirs: " . count($db->getAll('terroirs')) . "\n";
echo "Communes: " . count($db->getAll('communes')) . "\n";
echo "Régions: " . count($db->getAll('regions')) . "\n";
echo "Districts: " . count($db->getAll('districts')) . "\n";
echo "Titres: " . count($db->getAll('titres_transfert')) . "\n";

echo "\n✅ Initialisation terminée!\n";
