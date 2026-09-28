<?php
// Config/database.php - PDO Database Connection Configuration

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307'); // Defaulting to active MariaDB daemon
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sprint_gear');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        // Try port 3307 first, then fallback to 3306 if needed
        $ports = [DB_PORT, '3306'];
        $connected = false;

        foreach ($ports as $port) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                $connected = true;
                break;
            } catch (PDOException $e) {
                // If database missing, attempt redirect to setup_database.php
                if (strpos($e->getMessage(), "Unknown database") !== false) {
                    if (file_exists(__DIR__ . '/../setup_database.php')) {
                        header("Location: setup_database.php");
                        exit;
                    }
                }
            }
        }

        if (!$connected && $pdo === null) {
            die("<div style='font-family:sans-serif; padding:2rem; background:#fee2e2; border:1px solid #ef4444; border-radius:8px; max-width:600px; margin:2rem auto; color:#991b1b;'>
                <h2>Database Connection Error</h2>
                <p>Could not connect to database <strong>" . DB_NAME . "</strong>. Please make sure MySQL is running in XAMPP and run <a href='setup_database.php' style='color:#b91c1c; font-weight:bold;'>setup_database.php</a> to initialize the database.</p>
            </div>");
        }
    }
    return $pdo;
}
