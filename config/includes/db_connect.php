<?php
require_once __DIR__ . '/../bootstrap.php';

$servername = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$dbname = env('DB_DATABASE', 'p4u_db');
$port = (int) env('DB_PORT', 3306);

$conn = @new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    error_log('[DB] Connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('Database connection error.');
}
?>
