<?php
/**
 * Configuration de la base de données
 * Plateforme E-Gouvernance - État Civil Municipal
 *
 * Modifier les paramètres ci-dessous selon votre environnement XAMPP
 */

// ============================================================
// PARAMÈTRES DE CONNEXION — À MODIFIER SELON VOTRE CONFIG
// ============================================================
define('DB_HOST',     'localhost');
define('DB_NAME',     'e_gouvernance_etat_civil');
define('DB_USER',     'root');          // Utilisateur MySQL XAMPP
define('DB_PASS',     '');              // Mot de passe MySQL (vide par défaut sur XAMPP)
define('DB_CHARSET',  'utf8mb4');
define('DB_PORT',     3306);

// ============================================================
// PARAMÈTRES DE L'APPLICATION
// ============================================================
define('APP_NAME',    'E-État Civil');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/E-GOUVERNANCE_ETAT_CIVIL');
define('APP_ROOT',    dirname(__DIR__));

// Chemins
define('UPLOAD_PATH',    APP_ROOT . '/uploads/');
define('UPLOAD_URL',     APP_URL . '/uploads/');
define('MAX_FILE_SIZE',  5 * 1024 * 1024); // 5 Mo
define('ALLOWED_TYPES',  ['pdf', 'jpg', 'jpeg', 'png']);

// Sécurité
define('SESSION_TIMEOUT', 3600); // 1 heure en secondes
define('CSRF_TOKEN_EXPIRY', 900); // 15 minutes

// ============================================================
// CLASSE Database (Singleton PDO)
// ============================================================
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    /**
     * Constructeur privé — pattern Singleton
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production, logger l'erreur sans l'exposer
            error_log('[E-Gouvernance DB Error] ' . $e->getMessage());
            die($this->renderDbError());
        }
    }

    /**
     * Retourne l'instance unique
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retourne la connexion PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Exécute une requête préparée
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Retourne le dernier ID inséré
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Commence une transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Message d'erreur de connexion
     */
    private function renderDbError(): string
    {
        return '<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>Erreur de connexion</title>
<style>body{font-family:Arial,sans-serif;background:#f8f9fa;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;}
.card{background:#fff;border-radius:10px;padding:40px;box-shadow:0 4px 20px rgba(0,0,0,.1);max-width:500px;text-align:center;}
h1{color:#dc3545;}p{color:#6c757d;}code{background:#f8f9fa;padding:5px 10px;border-radius:4px;display:block;margin:10px 0;}</style>
</head><body><div class="card">
<h1>⚠️ Connexion impossible</h1>
<p>Impossible de se connecter à la base de données MySQL.</p>
<p>Vérifiez que :</p>
<ul style="text-align:left">
<li>XAMPP est démarré (Apache + MySQL)</li>
<li>La base <code>e_gouvernance_etat_civil</code> a été importée</li>
<li>Les paramètres dans <code>config/database.php</code> sont corrects</li>
</ul>
</div></body></html>';
    }

    // Empêcher le clonage et la désérialisation
    private function __clone() {}
    public function __wakeup() { throw new Exception("Ne pas désérialiser un singleton."); }
}

// ============================================================
// HELPER : Accès rapide à la connexion PDO
// ============================================================
function db(): PDO
{
    return Database::getInstance()->getConnection();
}

function dbQuery(string $sql, array $params = []): PDOStatement
{
    return Database::getInstance()->query($sql, $params);
}
