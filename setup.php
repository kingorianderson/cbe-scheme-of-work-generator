<?php
// One-time setup script — run once, then delete or restrict access.
error_reporting(E_ALL);
ini_set('display_errors', '1');

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without selecting a DB first
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p>Connected to MySQL OK.</p>";

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    // Split on semicolons to run each statement separately
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
    echo "<p style='color:green;font-weight:bold'>Database and table created successfully!</p>";
    echo "<p><a href='seed.php'>Seed Grade 9 Pre-Technical Studies data &rarr;</a></p>";
    echo "<p><a href='index.php'>Go to Scheme of Work (empty)</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Troubleshooting</h3><ul>";
    echo "<li>Make sure XAMPP MySQL is running (green in XAMPP Control Panel).</li>";
    echo "<li>If your MySQL root password is not empty, edit <code>config.php</code> and this file.</li>";
    echo "<li>Check that port 3306 is not blocked.</li>";
    echo "</ul>";
}
