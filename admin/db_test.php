<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1>MySQL Connection Test</h1>";

// Read .env if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'u422364295_db';
$user = $_ENV['DB_USER'] ?? 'u422364295_admin';
$pass = $_ENV['DB_PASS'] ?? '1590Sayamkhan@';
$port = $_ENV['DB_PORT'] ?? '3306';

echo "<p>Attempting to connect to MySQL: Host=$host, DB=$db, User=$user</p>";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<h2 style='color:green;'>✅ SUCCESS! MySQL is working perfectly!</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>❌ FAILED to connect to MySQL:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<p>Because MySQL is failing, the site is secretly falling back to the empty SQLite database! That is why your products vanished!</p>";
}
?>
