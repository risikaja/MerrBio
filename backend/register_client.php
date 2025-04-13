<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($phone) || empty($address) || empty($email) || empty($password)) {
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
    $insertUser = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'client')");
    $insertUser->execute([$email, $hashedPassword]);
    $user_id = $pdo->lastInsertId();

    // Insert into clients table
    $insertClient = $pdo->prepare("INSERT INTO clients (user_id, name, phone, address) VALUES (?, ?, ?, ?)");
    $insertClient->execute([$user_id, $name, $phone, $address]);

    echo "Client registered successfully!";
    header("Location: ../login.html");
    exit;
}
?>
