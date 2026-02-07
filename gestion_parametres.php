<?php
/**
 * Page de gestion des paramètres (Terroirs, Communes, Régions, Districts, Titres)
 */

require_once 'config.php';
require_once 'classes/Database.php';

session_start();

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
        exit;
    } else {
        die('Erreur de connexion à la base de données: ' . $e->getMessage());
    }
}

// Gestion des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $table = $_POST['table'] ?? '';
    $nom = sanitize_input($_POST['nom'] ?? '');
    $id = $_POST['id'] ?? null;

    try {
        switch ($action) {
            case 'add':
                if ($db->exists($table, $nom)) {
                    echo json_encode(['success' => false, 'error' => 'Cette entrée existe déjà.']);
                } else {
                    $db->add($table, $nom);
                    echo json_encode(['success' => true, 'message' => 'Ajouté avec succès.']);
                }
                break;

            case 'update':
                if ($db->exists($table, $nom, $id)) {
                    echo json_encode(['success' => false, 'error' => 'Cette entrée existe déjà.']);
                } else {
                    $db->update($table, $id, $nom);
                    echo json_encode(['success' => true, 'message' => 'Modifié avec succès.']);
                }
                break;

            case 'delete':
                $db->delete($table, $id);
                echo json_encode(['success' => true, 'message' => 'Supprimé avec succès.']);
                break;

            case 'getAll':
                $data = $db->getAll($table);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Action non reconnue.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Charger les données pour l'affichage
$terroirs = $db->getAll('terroirs');
$communes = $db->getAll('communes');
$regions = $db->getAll('regions');
$districts = $db->getAll('districts');
$titres = $db->getAll('titres_transfert');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Paramètres</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <style>
        .params-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .param-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .param-section h2 {
            color: #1e40af;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .add-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .add-form input {
            flex: 1;
            padding: 10px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
        }
        .add-form button {
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .add-form button:hover {
            background: #1e40af;
        }
        .items-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 6px;
            border-left: 3px solid #2563eb;
        }
        .item-actions button {
            padding: 5px 10px;
            margin-left: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-edit {
            background: #f59e0b;
            color: white;
        }
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <header>
        <h1>Gestion des Paramètres</h1>
        <a href="index.php" style="color: white; text-decoration: none;">← Retour</a>
    </header>

    <div class="params-container">
        <!-- Terroirs -->
        <div class="param-section">
            <h2>📍 Terroirs</h2>
            <div id="message-terroirs"></div>
            <div class="add-form">
                <input type="text" id="input-terroirs" placeholder="Nouveau terroir...">
                <button onclick="addItem('terroirs')">Ajouter</button>
            </div>
            <div class="items-list" id="list-terroirs">
                <?php foreach ($terroirs as $item): ?>
                    <div class="item" data-id="<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['nom']) ?></span>
                        <div class="item-actions">
                            <button class="btn-edit" onclick="editItem('terroirs', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                            <button class="btn-delete" onclick="deleteItem('terroirs', <?= $item['id'] ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Communes -->
        <div class="param-section">
            <h2>🏘️ Communes</h2>
            <div id="message-communes"></div>
            <div class="add-form">
                <input type="text" id="input-communes" placeholder="Nouvelle commune...">
                <button onclick="addItem('communes')">Ajouter</button>
            </div>
            <div class="items-list" id="list-communes">
                <?php foreach ($communes as $item): ?>
                    <div class="item" data-id="<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['nom']) ?></span>
                        <div class="item-actions">
                            <button class="btn-edit" onclick="editItem('communes', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                            <button class="btn-delete" onclick="deleteItem('communes', <?= $item['id'] ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Régions -->
        <div class="param-section">
            <h2>🗺️ Régions</h2>
            <div id="message-regions"></div>
            <div class="add-form">
                <input type="text" id="input-regions" placeholder="Nouvelle région...">
                <button onclick="addItem('regions')">Ajouter</button>
            </div>
            <div class="items-list" id="list-regions">
                <?php foreach ($regions as $item): ?>
                    <div class="item" data-id="<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['nom']) ?></span>
                        <div class="item-actions">
                            <button class="btn-edit" onclick="editItem('regions', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                            <button class="btn-delete" onclick="deleteItem('regions', <?= $item['id'] ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Districts -->
        <div class="param-section">
            <h2>📌 Districts</h2>
            <div id="message-districts"></div>
            <div class="add-form">
                <input type="text" id="input-districts" placeholder="Nouveau district...">
                <button onclick="addItem('districts')">Ajouter</button>
            </div>
            <div class="items-list" id="list-districts">
                <?php foreach ($districts as $item): ?>
                    <div class="item" data-id="<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['nom']) ?></span>
                        <div class="item-actions">
                            <button class="btn-edit" onclick="editItem('districts', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                            <button class="btn-delete" onclick="deleteItem('districts', <?= $item['id'] ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Titres de transfert -->
        <div class="param-section">
            <h2>📝 Titres de Transfert</h2>
            <div id="message-titres_transfert"></div>
            <div class="add-form">
                <input type="text" id="input-titres_transfert" placeholder="Nouveau titre...">
                <button onclick="addItem('titres_transfert')">Ajouter</button>
            </div>
            <div class="items-list" id="list-titres_transfert">
                <?php foreach ($titres as $item): ?>
                    <div class="item" data-id="<?= $item['id'] ?>">
                        <span><?= htmlspecialchars($item['nom']) ?></span>
                        <div class="item-actions">
                            <button class="btn-edit" onclick="editItem('titres_transfert', <?= $item['id'] ?>, '<?= htmlspecialchars($item['nom'], ENT_QUOTES) ?>')">✏️</button>
                            <button class="btn-delete" onclick="deleteItem('titres_transfert', <?= $item['id'] ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function showMessage(table, type, message) {
            const msgDiv = document.getElementById(`message-${table}`);
            msgDiv.className = `message ${type}`;
            msgDiv.textContent = message;
            setTimeout(() => msgDiv.textContent = '', 3000);
        }

        function addItem(table) {
            const input = document.getElementById(`input-${table}`);
            const nom = input.value.trim();
            
            if (!nom) {
                showMessage(table, 'error', 'Veuillez entrer un nom.');
                return;
            }

            fetch('gestion_parametres.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=add&table=${table}&nom=${encodeURIComponent(nom)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage(table, 'success', data.message);
                    input.value = '';
                    location.reload();
                } else {
                    showMessage(table, 'error', data.error);
                }
            });
        }

        function editItem(table, id, oldNom) {
            const newNom = prompt('Modifier:', oldNom);
            if (!newNom || newNom === oldNom) return;

            fetch('gestion_parametres.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update&table=${table}&id=${id}&nom=${encodeURIComponent(newNom)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage(table, 'success', data.message);
                    location.reload();
                } else {
                    showMessage(table, 'error', data.error);
                }
            });
        }

        function deleteItem(table, id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) return;

            fetch('gestion_parametres.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&table=${table}&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showMessage(table, 'success', data.message);
                    location.reload();
                } else {
                    showMessage(table, 'error', data.error);
                }
            });
        }
    </script>
</body>
</html>
