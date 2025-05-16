<?php
// Ensure settings.php is in the project root, adjust path if it's elsewhere
$configPath = __DIR__ . '/../settings.php';
if (!file_exists($configPath)) {
    die("Configuration file not found. Please ensure settings.php exists in the project root.");
}
$config = include($configPath);

$servername = $config['db']['host'];
$username = $config['db']['user'];
$password = $config['db']['password'];
$dbname = $config['db']['dbname'];

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        // In a real app, log this error and show a user-friendly message
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Catch connection errors if DB doesn't exist or credentials are wrong
    die("Database connection error: " . $e->getMessage() . " (Check your database server and credentials in settings.php)");
}

// Optional: Set mysqli error reporting mode after successful connection
// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>