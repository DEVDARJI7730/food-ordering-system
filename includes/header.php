<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';

$base_url = "/food-ordering-system";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nivaala | Online Food Ordering</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

  
  <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <i class="fa-solid fa-utensils me-2"></i>
        <span>Nivaala</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="index.php"><i class="fa-solid fa-house me-1"></i>Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="restaurants.php"><i class="fa-solid fa-store me-1"></i>Restaurants</a>
          </li>
          <?php if ($is_logged_in && $user_role === 'customer'): ?>
          <li class="nav-item">
            <a class="nav-link" href="orders.php"><i class="fa-solid fa-clock-rotate-left me-1"></i>My Orders</a>
          </li>
          <?php endif; ?>
          <?php if ($is_logged_in && $user_role === 'restaurant_owner'): ?>
          <li class="nav-item">
            <a class="nav-link fw-bold text-success" href="restaurant/dashboard.php"><i class="fa-solid fa-gauge-high me-1"></i>Restaurant Portal</a>
          </li>
          <?php endif; ?>
          <?php if ($is_logged_in && $user_role === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link fw-bold text-primary" href="admin/dashboard.php"><i class="fa-solid fa-shield-halved me-1"></i>Admin Panel</a>
          </li>
          <?php endif; ?>
        </ul>
        
        <div class="d-flex align-items-center gap-3">
          
          <?php if (!$is_logged_in || $user_role === 'customer'): ?>
          <a href="cart.php" class="btn btn-outline-secondary position-relative px-3 border-2 rounded-pill">
            <i class="fa-solid fa-cart-shopping me-1"></i>
            <span class="d-none d-md-inline">Cart</span>
            <?php if ($cart_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?php echo $cart_count; ?>
            </span>
            <?php endif; ?>
          </a>
          <?php endif; ?>

          <?php if (!$is_logged_in): ?>
            <a href="restaurant-register.php" class="btn btn-outline-success btn-sm rounded-pill px-3 d-none d-md-inline-block">
              <i class="fa-solid fa-store me-1"></i>Partner with Us
            </a>
          <?php endif; ?>

          
          <?php if ($is_logged_in): ?>
            <div class="dropdown">
              <button class="btn btn-primary dropdown-toggle px-4 rounded-pill" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-regular fa-user me-2"></i>Hi, <?php echo htmlspecialchars($user_name); ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                <?php if ($user_role === 'admin'): ?>
                  <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Admin Dashboard</a></li>
                  <li><a class="dropdown-item" href="admin/restaurants.php"><i class="fa-solid fa-store me-2"></i>Manage Restaurants</a></li>
                <?php elseif ($user_role === 'restaurant_owner'): ?>
                  <li><a class="dropdown-item" href="restaurant/dashboard.php"><i class="fa-solid fa-gauge me-2"></i>Owner Dashboard</a></li>
                  <li><a class="dropdown-item" href="restaurant/profile.php"><i class="fa-solid fa-id-card me-2"></i>Restaurant Profile</a></li>
                  <li><a class="dropdown-item" href="restaurant/food-items.php"><i class="fa-solid fa-burger me-2"></i>Menu Items</a></li>
                  <li><a class="dropdown-item" href="restaurant/orders.php"><i class="fa-solid fa-receipt me-2"></i>Restaurant Orders</a></li>
                <?php else: ?>
                  <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2"></i>My Profile</a></li>
                  <li><a class="dropdown-item" href="orders.php"><i class="fa-solid fa-list me-2"></i>My Orders</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
              </ul>
            </div>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline-primary px-4 rounded-pill">Login</a>
            <a href="register.php" class="btn btn-primary px-4 rounded-pill">Sign Up</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  
  <main class="py-4">
    <div class="container">
      
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm-custom" role="alert">
          <i class="fa-solid fa-circle-check me-2"></i>
          <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm-custom" role="alert">
          <i class="fa-solid fa-circle-xmark me-2"></i>
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
