<?php
$servername = "localhost";
$username = "root"; // your DB username
$password = ""; // your DB password
$dbname = "merbio"; // your actual DB name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
