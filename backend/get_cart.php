<?php
session_start();
require_once 'db_connection.php'; // your DB connection

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["error" => "Not logged in"]);
  exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT p.id, p.name, p.price, p.image, c.quantity 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>
