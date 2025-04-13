<?php
require_once 'db2.php'; // Make sure this file sets up $conn correctly

// This version hardcodes client_id = 2 (for testing or demo purposes)
$client_id = 4;

$sql = "SELECT 
            c.id AS cart_id,
            c.quantity,
            c.added_at,
            c.status,
            p.id AS product_id,
            p.name AS product_name,
            p.price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.client_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

header('Content-Type: application/json');
echo json_encode($items);
?>
