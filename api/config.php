<?php
define('DB_HOST', 'localhost:3306'); // Specify port 3307 with the host
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'app');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

?>