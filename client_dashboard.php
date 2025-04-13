<?php
include 'backend/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

$query = $pdo->prepare("SELECT * FROM clients WHERE user_id = ?");
$query->execute([$user_id]);
$client = $query->fetch();

// Get all products
$productQuery = $pdo->query("SELECT p.*, f.name AS farmer_name FROM products p JOIN farmers f ON p.farmer_id = f.id");
$products = $productQuery->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Dashboard</title>
	  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">

</head>
<body>
   <nav class="navbar navbar-expand-lg bg-light shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.html">
      <img src="./images/MerrBio-logo.png" alt="MerrBio Logo" width="40" height="30" class="d-inline-block align-text-top">
      MerrBio
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item mx-2">
          <a class="nav-link" href="index.html">Home</a>
        </li>
        <li class="nav-item mx-2">
          <a class="nav-link active" href="products.html">Produkte</a>
        </li>
        <li class="nav-item mx-2">
          <a class="nav-link" href="#">Rreth nesh</a>
        </li>
        <li class="nav-item mx-2">
          <a class="nav-link" href="kontakt.html">Kontakt</a>
        </li>
      </ul>

      <!-- Right Side: Login/Logout & Cart -->
      <div class="d-flex align-items-center gap-2">
        <a id="cart-btn" href="cart.html" class="btn btn-outline-secondary d-none">
          <i class="fas fa-shopping-cart"></i> Shporta
        </a>
        <button id="login-btn" class="btn btn-outline-success" type="button">Log In</button>
      </div>
    </div>
  </div>
</nav>


    <a href="view_cart.php">View Cart</a> |
    <a href="logout.php">Logout</a>
	
	
	
	<script>
document.addEventListener("DOMContentLoaded", () => {
  fetch('./backend/get_session_status.php')
    .then(res => res.json())
    .then(session => {
      const loginBtn = document.getElementById("login-btn");
      const cartBtn = document.getElementById("cart-btn");

      if (session.logged_in) {
        loginBtn.classList.remove("btn-outline-success");
        loginBtn.classList.add("btn-danger");
        loginBtn.textContent = "Log Out";
        cartBtn.classList.remove("d-none");

        loginBtn.addEventListener("click", () => {
          fetch('./backend/logout.php')
            .then(() => window.location.href = "index.html");
        });
      } else {
        loginBtn.addEventListener("click", () => {
          window.location.href = "login.html";
        });
      }
    });
});
</script>

	
	
	
	
	
	
	
	
	
	
</body>
</html>
