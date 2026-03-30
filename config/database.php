<?php
/**
 * UsafiKonect - Database Configuration
 * PDO connection with utf8mb4 charset and error handling
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'usafikonect');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection (singleton pattern)
 */
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
            PDO::ATTR_PERSISTENT         => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (defined('APP_DEBUG') && APP_DEBUG) {
                die("Database connection failed: " . $e->getMessage());
            }
            die("A database error occurred. Please try again later.");
        }
    }
    
    return $pdo;
}

// Application constants
define('APP_NAME', 'UsafiKonect');
define('APP_URL', 'http://localhost/usafikonect');
define('APP_DEBUG', true); // Set to false in production
define('APP_VERSION', '1.0.0');

// Base path for file includes
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', BASE_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('LOG_PATH', BASE_PATH . 'logs' . DIRECTORY_SEPARATOR);

// Create required directories if they don't exist
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}
