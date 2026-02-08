<?php
/**
 * Gestionnaire de base de données SQLite
 * @author SOMBINIAINA Erick
 */

class Database
{
    private static $instance = null;
    private $db;
    private $dbPath;
    
    /**
     * Valide un nom de table : permet uniquement alphanumérique et underscore
     * et vérifie que la table existe dans sqlite_master.
     */
    private function validateTableName($table)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception("Nom de table invalide");
        }

        $stmt = $this->db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :table");
        $stmt->execute(['table' => $table]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$res) {
            throw new Exception("Table inconnue: " . $table);
        }
    }

    private function __construct()
    {
        try {
            $this->dbPath = __DIR__ . '/../database/erickrapport.db';
            
            // Créer le dossier database s'il n'existe pas
            $dbDir = dirname($this->dbPath);
            if (!is_dir($dbDir)) {
                if (!mkdir($dbDir, 0755, true)) {
                    throw new Exception("Impossible de créer le dossier database");
                }
            }

            // Vérifier les permissions d'écriture
            if (!is_writable($dbDir)) {
                throw new Exception("Le dossier database n'est pas accessible en écriture");
            }

            // Connexion à la base de données SQLite
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Initialiser les tables
            $this->initTables();
        } catch (PDOException $e) {
            error_log("Erreur PDO Database: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Erreur Database: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initTables()
    {
        // Table des terroirs
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS terroirs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Table des communes
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS communes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Table des régions
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS regions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Table des districts
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS districts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Table des titres de transfert
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS titres_transfert (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Table des activités
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS activites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL UNIQUE,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Table des rapports enregistrés
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS rapports_enregistres (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom TEXT NOT NULL,
                commune_id INTEGER NOT NULL,
                activite_id INTEGER NOT NULL,
                fichier BLOB NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (commune_id) REFERENCES communes(id),
                FOREIGN KEY (activite_id) REFERENCES activites(id)
            )
        ");
    }

    public function getAll($table)
    {
        $this->validateTableName($table);
        $stmt = $this->db->query("SELECT * FROM {$table} ORDER BY nom ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($table, $nom)
    {
        $this->validateTableName($table);
        $stmt = $this->db->prepare("INSERT INTO {$table} (nom) VALUES (:nom)");
        return $stmt->execute(['nom' => $nom]);
    }

    public function update($table, $id, $nom)
    {
        $this->validateTableName($table);
        $stmt = $this->db->prepare("UPDATE {$table} SET nom = :nom WHERE id = :id");
        return $stmt->execute(['nom' => $nom, 'id' => $id]);
    }

    public function delete($table, $id)
    {
        $this->validateTableName($table);
        $stmt = $this->db->prepare("DELETE FROM {$table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function exists($table, $nom, $excludeId = null)
    {
        $this->validateTableName($table);
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE nom = :nom AND id != :id");
            $stmt->execute(['nom' => $nom, 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE nom = :nom");
            $stmt->execute(['nom' => $nom]);
        }
        return $stmt->fetchColumn() > 0;
    }

    public function getConnection()
    {
        return $this->db;
    }
}
