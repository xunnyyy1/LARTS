<?php
// ─── Database Configuration ───────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'larts_db');

define('SITE_NAME', 'LARTS');
define('SITE_FULL', 'Livelihood Assistance & Resource Tracking System');

// ─── BASE URL ─────────────────────────────────────────────────
// Set this once to match YOUR server. No trailing slash.
//
//   Browser shows: localhost:8000/modules/households/index.php
//   → larts folder IS the server root → BASE_URL = ''
//
//   Browser shows: localhost/larts/modules/households/index.php
//   → larts is a subfolder → BASE_URL = '/larts'
//
define('BASE_URL', '/larts');  // <-- '' if server root, '/larts' if subfolder

// Create PDO connection
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:red;">
                <h2>Database Connection Failed</h2>
                <p>Please ensure XAMPP is running and the database <strong>larts_db</strong> has been imported.</p>
                <code>' . htmlspecialchars($e->getMessage()) . '</code>
                </div>');
        }
    }
    return $pdo;
}