
<?php
session_start();
include 'db.php'; // include your DB connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        echo "Missing login credentials.";
        exit;
    }

    // Get user with matching email and role
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        // Redirect based on role
        if ($role === 'farmer') {
            header("Location: ../farmer_dashboard.php");
        } else {
            header("Location: ../index.html");
        }
        exit;
    } else {
    echo "<script type='text/javascript'>
                alert('Invalid email, password, or role.');
                window.location.href = '../login.html';
              </script>";
        	
        exit;
    }
} else {
    echo "Invalid request method.";
}
?>
