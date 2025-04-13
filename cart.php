<?php
require_once 'backend/init.php';
use App\Auth\SessionGuard;
use App\Services\CartService;
use App\Repositories\ProductRepository;
use App\Services\PaymentService;

// Initialize session
session_start();

// Get user information if logged in
$user_id = SessionGuard::getUserId() ?? null;
$is_logged_in = isset($_SESSION['user_id']);

// Initialize repositories and services
$productRepository = new ProductRepository($db);
$paymentService = new PaymentService();

// If user is not logged in, redirect to login
if (!$is_logged_in) {
    // You might want to store cart items in session for non-logged in users
    // For now, we'll just use an empty array for cart items if not logged in
    $cart_items = [];
} else {
    // Get cart items from database
    // Join with products and farmers tables to get all the necessary information
    $query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.unit, p.image_url, 
              p.organic, f.id as farmer_id, f.name as farmer_name
              FROM cart c
              JOIN products p ON c.product_id = p.id
              JOIN farmers f ON p.farmer_id = f.id
              WHERE c.user_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$cart_count = count($cart_items);

// Calculate totals
$subtotal = 0;
$shipping = 0;
$tax_rate = 0.21; // 21% VAT
$tax = 0;
$total = 0;

// Process cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $is_logged_in) {
        switch ($_POST['action']) {
            case 'update':
                if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
                    foreach ($_POST['quantity'] as $item_id => $quantity) {
                        // Update cart item quantity in database
                        $updateQuery = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->execute([(int)$quantity, $item_id, $user_id]);
                    }
                    SessionGuard::setFlash('success', 'Cart updated successfully');
                }
                break;
                
            case 'empty':
                // Empty cart in database
                $emptyQuery = "DELETE FROM cart WHERE user_id = ?";
                $emptyStmt = $db->prepare($emptyQuery);
                $emptyStmt->execute([$user_id]);
                
                SessionGuard::setFlash('success', 'Cart emptied successfully');
                break;
                
            case 'remove':
                if (isset($_POST['item_id'])) {
                    // Remove item from cart in database
                    $removeQuery = "DELETE FROM cart WHERE id = ? AND user_id = ?";
                    $removeStmt = $db->prepare($removeQuery);
                    $removeStmt->execute([$_POST['item_id'], $user_id]);
                    
                    SessionGuard::setFlash('success', 'Item removed from cart');
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: cart.php');
        exit;
    }
}

// Calculate cart totals
if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
    }
    
    // Calculate shipping costs (simplified version)
    $shipping = ($subtotal < 50) ? 4.95 : 0;
    
    // Calculate tax
    $tax = $subtotal * $tax_rate;
    
    // Calculate total
    $total = $subtotal + $shipping + $tax;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Local Farm Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --accent: #FF9800;
            --text-dark: #212121;
            --text-light: #757575;
            --bg-light: #F5F5F5;
            --border-radius: 12px;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .page-header {
            background-color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .cart-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .cart-header {
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .cart-item {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: background-color 0.2s;
        }
        
        .cart-item:hover {
            background-color: rgba(0, 0, 0, 0.01);
        }
        
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .cart-item-name {
            font-weight: 600;
            color: var(--text-dark);
            text-decoration: none;
        }
        
        .cart-item-name:hover {
            color: var(--primary);
        }
        
        .cart-item-price {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .cart-item-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            background-color: var(--primary-light);
            color: white;
            border-radius: 20px;
            margin-left: 0.5rem;
        }
        
        .cart-item-seller {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }
        
        .cart-item-options {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 0.375rem;
        }
        
        .btn-remove {
            color: #dc3545;
            background: transparent;
            border: none;
            padding: 0;
            font-size: 0.875rem;
            margin-left: 1rem;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-remove:hover {
            text-decoration: underline;
        }
        
        .cart-summary {
            background-color: rgba(0, 0, 0, 0.02);
            padding: 1.5rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .summary-total {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-dark);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 0.75rem;
            margin-top: 0.75rem;
        }
        
        .empty-cart {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            color: var(--text-light);
            opacity: 0.3;
            margin-bottom: 1.5rem;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .alert-free-shipping {
            background-color: rgba(76, 175, 80, 0.1);
            border-color: var(--primary-light);
            color: var(--primary-dark);
        }
        
        .alert-free-shipping i {
            color: var(--primary);
        }
        
        .alert-shipping-threshold {
            background-color: rgba(255, 152, 0, 0.1);
            border-color: var(--accent);
            color: #E65100;
        }
        
        .progress {
            height: 8px;
            margin-top: 8px;
        }
        
        .progress-bar {
            background-color: var(--primary);
        }
        
        .recommendations-container {
            margin-top: 3rem;
        }
        
        .recommendations-heading {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
        }
        
        .product-card {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        
        .product-card-body {
            padding: 1rem;
        }
        
        .product-card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .product-card-price {
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 1.125rem;
        }
        
        .product-card-seller {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-leaf me-2 text-primary"></i>Farm Fresh Market
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="marketplace.php">Marketplace</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="farmers.php">Farmers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cart_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>Account
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <li><a class="dropdown-item" href="profile.php">Profile Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm mt-1" href="register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0">Your Shopping Cart</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active">Cart</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Flash messages -->
        <?php if (SessionGuard::hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= SessionGuard::getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (SessionGuard::hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= SessionGuard::getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Cart Container -->
                <div class="cart-container mb-4">
                    <div class="cart-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>Shopping Cart (<?= $cart_count ?> items)
                        </h5>
                        <form method="POST" id="emptyCartForm">
                            <input type="hidden" name="action" value="empty">
                            <?php if ($cart_count > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-light" onclick="confirmEmptyCart()">
                                    <i class="fas fa-trash-alt me-1"></i>Empty Cart
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <?php if (empty($cart_items)): ?>
                        <!-- Empty cart message -->
                        <div class="empty-cart">
                            <div class="empty-cart-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h4>Your cart is empty</h4>
                            <p class="text-muted mb-4">Browse our marketplace to find fresh, locally grown produce.</p>
                            <a href="marketplace.php" class="btn btn-primary">Continue Shopping</a>
                        </div>
                    <?php else: ?>
                        <!-- Cart items -->
                        <form method="POST" id="cartForm">
                            <input type="hidden" name="action" value="update">
                            
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 col-3">
                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img">
                                        </div>
                                        <div class="col-md-5 col-9">
                                            <a href="product.php?id=<?= $item['product_id'] ?>" class="cart-item-name">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </a>
                                            <?php if (isset($item['organic']) && $item['organic']): ?>
                                                <span class="cart-item-badge">Organic</span>
                                            <?php endif; ?>
                                            <div class="cart-item-seller">
                                                From: <a href="farmer.php?id=<?= $item['farmer_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($item['farmer_name']) ?>
                                                </a>
                                            </div>
                                            <div class="cart-item-options">
                                                <div class="d-flex align-items-center">
                                                    <input type="number" name="quantity[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" 
                                                           min="1" max="99" class="quantity-input" id="quantity-<?= $item['id'] ?>"
                                                           onchange="updateCartItem()">
                                                    <span class="ms-2"><?= htmlspecialchars($item['unit']) ?></span>
                                                </div>
                                                
                                                <form method="POST" class="d-inline ms-3">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                    <button type="submit" class="btn-remove">
                                                        <i class="fas fa-trash-alt me-1"></i>Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-6 mt-3 mt-md-0 text-md-center">
                                            <div class="cart-item-price">
                                                €<?= number_format($item['price'], 2) ?>/<?= htmlspecialchars($item['unit']) ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mt-3 mt-md-0 text-end">
                                            <div class="fw-bold">€<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="p-3 text-end">
                                <button type="submit" class="btn btn-primary btn-sm" id="updateCartBtn">
                                    <i class="fas fa-sync-alt me-1"></i>Update Cart
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Continue shopping -->
                <div class="d-flex justify-content-between">
                    <a href="marketplace.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                    
                    <?php if (!empty($cart_items) && !$is_logged_in): ?>
                        <div class="text-end">
                            <p class="mb-2 text-muted">Already have an account?</p>
                            <a href="login.php?redirect=cart.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Continue
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <?php if (!empty($cart_items)): ?>
                    <div class="cart-container">
                        <div class="cart-header">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                        </div>
                        
                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>€<?= number_format($subtotal, 2) ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?= $shipping > 0 ? '€' . number_format($shipping, 2) : 'Free' ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Tax (<?= ($tax_rate * 100) ?>%)</span>
                                <span>€<?= number_format($tax, 2) ?></span>
                            </div>
                            
                            <div class="summary-row summary-total">
                                <span>Total</span>
                                <span>€<?= number_format($total, 2) ?></span>
                            </div>
                            
                            <?php if ($shipping > 0): ?>
                                <?php 
                                    $free_shipping_threshold = 50;
                                    $more_needed = $free_shipping_threshold - $subtotal;
                                    $progress_percent = min(($subtotal / $free_shipping_threshold) * 100, 100);
                                ?>
                                <div class="alert alert-shipping-threshold mt-3 mb-0">
                                    <i class="fas fa-truck me-2"></i>
                                    Add €<?= number_format($more_needed, 2) ?> more to get FREE shipping!
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $progress_percent ?>%" 
                                             aria-valuenow="<?= $progress_percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-free-shipping mt-3 mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    You've qualified for FREE shipping!
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4 d-grid">
                                <?php if ($is_logged_in): ?>
                                    <a href="checkout.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-lock me-2"></i>Proceed to Checkout
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?redirect=checkout.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user me-2"></i>Login to Checkout
                                    </a>
                                    <a href="register.php?redirect=checkout.php" class="btn btn-outline-primary mt-2">
                                        Create Account
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-center mt-3">
                                <img src="assets/images/payment-methods.png" alt="Payment methods" class="img-fluid" style="max-height: 30px;">
                                <div class="small text-muted mt-2">Secure payment processing</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Promo Code -->
                    <div class="cart-container mt-3">
                        <div class="p-3">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Promo code" id="promo-code">
                                <button class="btn btn-outline-primary" type="button" id="apply-promo">Apply</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Help -->
                    <div class="cart-container mt-3">
                        <div class="p-3">
                            <h6 class="mb-3"><i class="fas fa-question-circle me-2 text-primary"></i>Need Help?</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <a href="shipping-policy.php" class="text-decoration-none">
                                        <i class="fas fa-truck me-2 text-muted"></i>Shipping Information
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="return-policy.php" class="text-decoration-none">
                                        <i class="fas fa-undo me-2 text-muted"></i>Return Policy
                                    </a>
                                </li>
                                <li>
                                    <a href="contact.php" class="text-decoration-none">
                                        <i class="fas fa-envelope me-2 text-muted"></i>Contact Support
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recommended Products -->
        <?php if (!empty($cart_items)): ?>
            <div class="recommendations-container">
                <h3 class="recommendations-heading">You might also like</h3>
                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <?php
                    // Get recommended products from database
                    // Here we can get products that might be related to what's in the cart
                    $recommendedQuery = "SELECT p.id, p.name, p.price, p.unit, p.image_url, f.id as farmer_id, f.name as farmer_name
                                         FROM products p
                                         JOIN farmers f ON p.farmer_id = f.id
                                         WHERE p.id NOT IN (SELECT product_id FROM cart WHERE user_id = ?)
                                         ORDER BY RAND() LIMIT 4";
                    
                    $recommendedStmt = $db->prepare($recommendedQuery);
                    $recommendedStmt->execute([$user_id]);
                    $recommended_products = $recommendedStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // If no recommended products or not enough, we can use some fallback products
                    if (count($recommended_products) < 4) {
                        $defaultProducts = [
                            [
                                'id' => 101,
                                'name' => 'Organic Strawberries',
                                'price' => 4.99,
                                'unit' => 'basket',
                                'image_url' => 'assets/images/products/strawberries.jpg',
                                'farmer_name' => 'Berry Farm',
                                'farmer_id' => 12
                            ],
                            [
                                'id' => 102,
                                'name' => 'Fresh Goat Cheese',
                                'price' => 6.50,
                                'unit' => 'piece',
                                'image_url' => 'assets/images/products/goat-cheese.jpg',
                                'farmer_name' => 'Mountain Dairy',
                                'farmer_id' => 8
                            ],
                            [
                                'id' => 103,
                                'name' => 'Heirloom Tomatoes',
                                'price' => 3.75,
                                'unit' => 'kg',