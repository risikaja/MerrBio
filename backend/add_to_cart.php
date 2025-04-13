<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(["success" => false, "message" => "Not logged in as client"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$product_id = $data['product_id'];
$user_id = $_SESSION['user_id'];

// Get client ID from user_id
$stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
$stmt->execute([$user_id]);
$client = $stmt->fetch();

if (!$client) {
    echo json_encode(["success" => false, "message" => "Client not found"]);
    exit;
}

// Check if product already in cart
$check = $pdo->prepare("SELECT * FROM cart WHERE client_id = ? AND product_id = ?");
$check->execute([$client['id'], $product_id]);
$existing = $check->fetch();

if ($existing) {
    // Update quantity
    $update = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
    $update->execute([$existing['id']]);
} else {
    // Insert new row
    $insert = $pdo->prepare("INSERT INTO cart (client_id, product_id, quantity) VALUES (?, ?, 1)");
    $insert->execute([$client['id'], $product_id]);
}

echo json_encode(["success" => true]);
?>
