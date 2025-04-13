<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['location'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($phone) || empty($location) || empty($email) || empty($password)) {
        echo "All fields are required.";
        exit;
    }

    // Check if user exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        echo "Email already registered.";
        exit;
    }

    // Insert into users table
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $insertUser = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'farmer')");
    $insertUser->execute([$email, $hashedPassword]);
    $user_id = $pdo->lastInsertId();

    // Insert into farmers table
    $insertFarmer = $pdo->prepare("INSERT INTO farmers (user_id, name, phone, location) VALUES (?, ?, ?, ?)");
    $insertFarmer->execute([$user_id, $name, $phone, $location]);

    echo "Farmer registered successfully!";
    header("Location: ../login.html");
    exit;
}
?>
