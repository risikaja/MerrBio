<?php
include 'backend/db.php';
session_start();

// Redirect if not logged in or not a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: ../login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get farmer info
$query = $pdo->prepare("SELECT * FROM farmers WHERE user_id = ?");
$query->execute([$user_id]);
$farmer = $query->fetch();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $unit = $_POST['unit'];
    $quantity = $_POST['quantity'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];
        $imagePath = "images/products/" . $imageName;

        // Move the uploaded file to the images/products folder
        move_uploaded_file($imageTmpName, $imagePath);
    } else {
        $imagePath = null;
    }

    // Insert the product into the database
    $sql = "INSERT INTO products (name, description, price, image_url, category, farmer_id, unit, quantity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $description, $price, $imagePath, $category, $farmer['id'], $unit, $quantity]);

    // Redirect to the farmer dashboard or product listing page
    header("Location: farmer_dashboard.php");
    exit;
}
?>
