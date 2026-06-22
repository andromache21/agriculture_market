<?php
$host = 'sql202.infinityfree.com';
$db   = 'if0_42244635_agriculture_market'; 
$user = 'if0_42244635';
$pass = 'iu7TBtF9Tv9'; // The unique hosting password we saved earlier
$charset = 'utf8mb4';

// Data Source Name configuration
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Turns on error reporting
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetches data as clean associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disables emulation for better security
];

try {
    // Create the connection
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Un-comment the line below if you want to visually verify it works, then delete it later:
    // echo "Database connection successful!"; 
} catch (\PDOException $e) {
    // If something goes wrong, this catches it and tells you why
    die("Database connection failed: " . $e->getMessage());
}
?>