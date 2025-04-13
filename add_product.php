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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            margin-top: 30px;
            color: #4CAF50;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
            color: #333;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select,
        input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .form-group input[type="number"], .form-group select {
            max-width: 300px;
        }
        .form-group input[type="file"] {
            max-width: 500px;
        }
    </style>
</head>
<body>
    <h1>Add a New Product</h1>
    <div class="container">
        <form action="submit_product.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price (€):</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="vegetable">Vegetable</option>
                    <option value="fruit">Fruit</option>
                    <option value="dairy">Dairy</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="unit">Unit:</label>
                <select id="unit" name="unit" required>
                    <option value="kg">kg</option>
                    <option value="piece">Piece</option>
                    <option value="liter">Liter</option>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" required>
            </div>
            <div class="form-group">
                <label for="image">Product Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>
            <button type="submit">Add Product</button>
        </form>
    </div>
</body>
</html>
