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

// Get farmer products
$productQuery = $pdo->prepare("SELECT * FROM products WHERE farmer_id = ?");
$productQuery->execute([$farmer['id']]);
$products = $productQuery->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            margin-top: 30px;
            color: #4CAF50;
        }
        h2 {
            color: #333;
            font-size: 24px;
            margin-top: 20px;
            text-align: center;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-top: 30px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            display: flex;
            justify-content: space-between;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
            font-size: 16px;
        }
        a:hover {
            text-decoration: underline;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($farmer['name']) ?></h1>
    <div class="container">
        <h2>Your Products</h2>
        <ul>
            <?php foreach ($products as $product): ?>
                <li>
                    <span><?= htmlspecialchars($product['name']) ?> - <?= $product['price'] ?>€</span>
                    <span>(<?= $product['quantity'] ?> <?= $product['unit'] ?>)</span>
                </li>
            <?php endforeach; ?>
        </ul>

        <a href="add_product.php">Add New Product</a> |
        <a href="./backend/logout.php">Logout</a>
    </div>

    <div class="footer">
        &copy; <?= date("Y") ?> MerBio | All Rights Reserved
    </div>
</body>
</html>
