<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Get request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['item_id']) || !isset($data['quantity']) || $data['quantity'] < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input'
    ]);
    exit;
}

$item_id = $data['item_id'];
$quantity = $data['quantity'];
$user_id = $_SESSION['user_id'];

try {
    // Verify that the cart item belongs to the user
    $stmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $item_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cart item not found or does not belong to user'
        ]);
        exit;
    }
    
    // Update the quantity
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $quantity, $item_id, $user_id);
    $stmt->execute();
    
    // Get updated cart items
    $stmt = $conn->prepare("
        SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.img_url, p.category, p.unit
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated successfully',
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating cart: ' . $e->getMessage()
    ]);
}

$conn->close();
?>