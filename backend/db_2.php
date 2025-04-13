<?php
// Database configuration
$servername = "localhost"; // Change if your database is on a different server
$username = "root"; // Change to your database username
$password = ""; // Change to your database password (if any)
$dbname = "merbio"; // Change to your database name

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Lidhja dështoi: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper display of Albanian characters
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    echo "Gabim në lidhjen me bazën e të dhënave: " . $e->getMessage();
    exit;
}
?>