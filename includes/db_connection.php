<?php
// Load config
$config = require __DIR__ . '/../config.php';

// Database settings
$db = $config['db'] ?? [];
$DB_HOST = $db['host'] ?? 'localhost';
$DB_USER = $db['user'] ?? 'root';
$DB_PASS = $db['pass'] ?? '';
$DB_NAME = $db['name'] ?? 'portfolio_db';
$DB_PORT = $db['port'] ?? 3306;
$DB_CHARSET = $db['charset'] ?? 'utf8mb4';

// Create connection
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

    if ($conn->connect_error) {
        // Log full error for the developer but do not expose credentials to users
        error_log('Database connection error: ' . $conn->connect_error);
        // Friendly message shown to the user (used by login.php)
        die('Database connection failed: Please make sure your MySQL server is running and the credentials are correct.');
    }

    $conn->set_charset($DB_CHARSET);
} catch (mysqli_sql_exception $e) {
    die("<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;'>
            Database connection failed: " . htmlspecialchars($e->getMessage()) . 
            "<br>Please make sure your MySQL server is running and the credentials are correct.
         </div>");
}
?>
