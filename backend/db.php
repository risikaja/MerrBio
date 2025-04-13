<?php
$host = 'localhost';
$db   = 'merbio'; // Replace with your actual database name
$user = 'root';      // Default XAMPP user
$pass = '';          // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
