# Système de Fusion Multiple

## Vue d'ensemble

Le système de fusion multiple permet aux utilisateurs d'effectuer plusieurs fusions successives avant l'enregistrement final. Toutes les fusions sont combinées dans l'ordre pour créer un rapport unique.

## Flux de travail

### 1. Première Fusion
- L'utilisateur effectue une fusion normale via `acceuil_fusion.php`
- Au lieu d'aller directement à l'enregistrement, il est redirigé vers `acceuil_choix_fusion.php`

### 2. Page de Choix
Sur la page de choix, l'utilisateur peut :
- **Ajouter une nouvelle fusion** : Sauvegarde la fusion actuelle et retourne à la page de fusion
- **Terminer et enregistrer** : Combine toutes les fusions et va à la page d'enregistrement

### 3. Ajout de Fusions Supplémentaires
Si "Ajouter une nouvelle fusion" est sélectionné :
1. La fusion actuelle est sauvegardée dans `fusions_temporaires`
2. Les fichiers temporaires actuels sont nettoyés
3. L'utilisateur retourne à `acceuil_fusion.php` pour une nouvelle fusion
4. Le processus se répète

### 4. Finalisation
Quand "Terminer et enregistrer" est cliqué :
1. Toutes les fusions temporaires sont récupérées de la BDD
2. La fusion actuelle est ajoutée (si elle existe)
3. Toutes les fusions sont combinées dans l'ordre
4. L'utilisateur est redirigé vers `acceuil_enregistrer_rapport.php`

### 5. Enregistrement Final
Lors de l'enregistrement du rapport :
- Le rapport fusionné est sauvegardé
- Toutes les fusions temporaires de la session sont supprimées de la BDD

## Architecture

### Base de données

#### Table `fusions_temporaires`
```sql
CREATE TABLE fusions_temporaires (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    ordre INTEGER NOT NULL,
    fichier BLOB NOT NULL,
    nom_fichier TEXT NOT NULL,
    params TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_session_id ON fusions_temporaires(session_id);
```

**Colonnes:**
- `id` : Identifiant unique auto-incrémenté
- `session_id` : ID de session PHP pour lier les fusions
- `ordre` : Numéro d'ordre de la fusion (1, 2, 3...)
- `fichier` : Contenu binaire du fichier Excel fusionné
- `nom_fichier` : Nom du fichier fusionné
- `params` : Paramètres JSON de la fusion (terroir, commune, etc.)
- `date_creation` : Timestamp de création

### Fichiers Créés

#### 1. `acceuil_choix_fusion.php`
Page d'interface avec deux boutons principaux :
- Affiche les informations de la fusion actuelle
- Compte et affiche les fusions précédentes
- Gère la navigation vers nouvelle fusion ou finalisation

#### 2. `api_save_fusion_temp.php`
API pour sauvegarder une fusion temporaire :
- Lit les fichiers temporaires de fusion
- Détermine le numéro d'ordre suivant
- Insère le fichier Excel dans la table `fusions_temporaires`
- Nettoie les fichiers temporaires actuels

#### 3. `api_finaliser_fusions.php`
API pour combiner toutes les fusions :
- Récupère toutes les fusions temporaires de la session
- Extrait les fichiers Excel des BLOB
- Utilise `ExcelProcessor` pour fusionner tous les fichiers
- Crée un résultat final unique
- Redirige vers la page d'enregistrement

#### 4. `cleanup_fusions_temp.php`
Script de maintenance :
- Supprime les fusions temporaires de plus de 24 heures
- À exécuter périodiquement (cron)

#### 5. `test_fusion_multiple.php`
Suite de tests :
- Vérifie la structure de la base de données
- Teste les opérations CRUD
- Valide l'ordre des fusions
- Confirme l'existence des fichiers

### Modifications de Fichiers Existants

#### `fusionner.php` (Ligne ~150)
**Avant:**
```php
'redirect' => 'acceuil_enregistrer_rapport.php',
'message' => 'Fusion terminée. Veuillez nommer et enregistrer le rapport.'
```

**Après:**
```php
'redirect' => 'acceuil_choix_fusion.php',
'message' => 'Fusion terminée. Choisissez votre action.'
```

#### `api_enregistrer_rapport.php` (Ligne ~190)
**Ajout:**
```php
// Nettoyer les fusions temporaires de la base de données
$sessionId = session_id();
try {
    $db = Database::getInstance();
    $stmt = $db->getConnection()->prepare("DELETE FROM fusions_temporaires WHERE session_id = ?");
    $stmt->execute([$sessionId]);
} catch (Exception $cleanupError) {
    error_log('Erreur nettoyage fusions temporaires: ' . $cleanupError->getMessage());
}
```

## Flux de Données

```
1. Fusion Initiale
   acceuil_fusion.php → fusionner.php → temp/result_{session}.xlsx
                                       → temp/fusion_data_{session}.json

2. Page de Choix
   fusionner.php → acceuil_choix_fusion.php
   
3a. Ajouter Fusion
    acceuil_choix_fusion.php → api_save_fusion_temp.php
                             → INSERT INTO fusions_temporaires
                             → DELETE temp/result_* et fusion_data_*
                             → acceuil_fusion.php (nouvelle fusion)

3b. Terminer
    acceuil_choix_fusion.php → api_finaliser_fusions.php
                             → SELECT * FROM fusions_temporaires
                             → ExcelProcessor->mergeExcelFiles()
                             → temp/result_{session}.xlsx
                             → acceuil_enregistrer_rapport.php

4. Enregistrement
   acceuil_enregistrer_rapport.php → api_enregistrer_rapport.php
                                   → INSERT INTO rapports_enregistres
                                   → DELETE FROM fusions_temporaires
```

## Gestion de Session

- **Session ID**: Utilisé pour lier toutes les fusions d'un utilisateur
- **Isolation**: Chaque session a ses propres fusions temporaires
- **Nettoyage**: Les fusions sont supprimées après enregistrement ou après 24h

## Exemple d'Utilisation

### Scénario: Fusion de 3 lots de données

1. **Premier lot** (Terroir A, Commune 1)
   - Fusionner 10 fichiers Excel
   - Cliquer "Ajouter une nouvelle fusion"
   - → Sauvegardé comme fusion #1

2. **Deuxième lot** (Terroir B, Commune 2)
   - Fusionner 15 fichiers Excel
   - Cliquer "Ajouter une nouvelle fusion"
   - → Sauvegardé comme fusion #2

3. **Troisième lot** (Terroir C, Commune 3)
   - Fusionner 8 fichiers Excel
   - Cliquer "Terminer et enregistrer"
   - → Les 3 fusions sont combinées en 1 fichier
   - → Redirection vers enregistrement

4. **Enregistrement final**
   - Nommer le rapport
   - Ajouter canevas/conclusion (optionnel)
   - Enregistrer
   - → Fusions temporaires supprimées

## Maintenance

### Nettoyage Automatique
Exécuter périodiquement :
```bash
php cleanup_fusions_temp.php
```

### Vérification du Système
```bash
php test_fusion_multiple.php
```

## Avantages

1. **Flexibilité**: Permet de traiter plusieurs lots séparément
2. **Organisation**: Chaque fusion peut avoir ses propres paramètres
3. **Préservation**: Les logos et images sont préservés à chaque étape
4. **Sécurité**: Isolation par session, nettoyage automatique
5. **UX**: Interface claire avec compteur de fusions

## Limitations

- Les fusions sont limitées à 24 heures de durée de vie
- Stockage en BDD peut grossir avec de nombreux fichiers volumineux
- Une seule session active par utilisateur

## Dépannage

### Fusions temporaires non supprimées
```sql
DELETE FROM fusions_temporaires WHERE session_id = 'YOUR_SESSION_ID';
```

### Vérifier les fusions en attente
```sql
SELECT session_id, COUNT(*) as count 
FROM fusions_temporaires 
GROUP BY session_id;
```

### Voir toutes les fusions d'une session
```sql
SELECT id, ordre, nom_fichier, date_creation 
FROM fusions_temporaires 
WHERE session_id = 'YOUR_SESSION_ID' 
ORDER BY ordre;
```
