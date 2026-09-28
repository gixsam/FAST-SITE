<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>MySQL Diagnostic Test</title>";
echo "<style>body{background:#080911;color:#fff;font-family:sans-serif;padding:2rem;} .card{background:#111;padding:2rem;border-radius:12px;border:1px solid #333;max-width:800px;margin:0 auto;} pre{background:#222;padding:1rem;border-radius:8px;overflow-x:auto;}</style></head><body><div class='card'>";
echo "<h1>MySQL Connection Diagnostic Tool</h1><hr style='border-color: #333; margin: 1.5rem 0;'>";

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Attempt to read the .env file exactly how config.php does
$envFile = __DIR__ . '/../.env';
echo "<h3>1. Checking .env File</h3>";
if (file_exists($envFile)) {
    echo "<p style='color:#10b981;'>✅ .env file found!</p>";
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
} else {
    echo "<p style='color:red;'>❌ .env file NOT FOUND!</p>";
}

// Get the credentials
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'u422364295_db';
$user = $_ENV['DB_USER'] ?? 'u422364295_admin';
$pass = $_ENV['DB_PASS'] ?? '1590Sayamkhan@';
$port = $_ENV['DB_PORT'] ?? '3306';

echo "<h3>2. MySQL Credentials Being Used</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Database:</strong> $db</li>";
echo "<li><strong>User:</strong> $user</li>";
echo "<li><strong>Password:</strong> " . (strlen($pass) > 0 ? "******** (Loaded)" : "Empty") . "</li>";
echo "</ul>";

echo "<h3>3. Connection Test</h3>";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<h2 style='color:#10b981;'>✅ SUCCESS! MySQL is working perfectly!</h2>";
    echo "<p>If you see this, the database is healthy. But wait, if this works, why did the previous page show an SQLite error?</p>";
} catch (PDOException $e) {
    echo "<h2 style='color:#ef4444;'>❌ FAILED to connect to MySQL!</h2>";
    echo "<pre style='color:#ef4444;'>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Because MySQL is failing, your site is secretly falling back to the empty SQLite database. That is exactly why your products vanished!</p>";
}

echo "<br><a href='/' style='color: #fcb900; text-decoration: none; font-weight: bold;'>&larr; Go Back to Homepage</a>";
echo "</div></body></html>";
?>
