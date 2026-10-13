<?php
if (!ob_get_level()) {
    ob_start();
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin authorization required.";
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Count pending restaurant verification requests
$pending_restaurants_count = 0;
try {
    $p_stmt = $conn->query("SELECT COUNT(*) AS total FROM restaurants WHERE status = 'Pending'");
    $pending_restaurants_count = (int)$p_stmt->fetch()['total'];
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nivaala | Admin Panel</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

  
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="dashboard.php">
        <i class="fa-solid fa-utensils me-2 text-primary"></i>
        <span>Nivaala <span class="text-white text-sm font-weight-normal font-monospace" style="font-size: 0.75rem;">ADMIN</span></span>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light d-none d-sm-inline"><i class="fa-regular fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a>
      </div>
    </div>
  </nav>

  
  <div class="admin-container">
    
    
    <aside class="admin-sidebar shadow-sm">
      <nav class="nav flex-column">
        <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-chart-line"></i>Dashboard
        </a>
        <a href="restaurants.php" class="sidebar-link d-flex justify-content-between align-items-center <?php echo in_array($current_page, ['restaurants.php', 'verify-license.php']) ? 'active' : ''; ?>">
          <span><i class="fa-solid fa-store me-2"></i>Restaurants</span>
          <?php if ($pending_restaurants_count > 0): ?>
            <span class="badge rounded-pill bg-warning text-dark font-monospace"><?php echo $pending_restaurants_count; ?></span>
          <?php endif; ?>
        </a>
        <a href="catagories.php" class="sidebar-link <?php echo $current_page === 'catagories.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-folder-open"></i>Catagories
        </a>
        <a href="food-items.php" class="sidebar-link <?php echo $current_page === 'food-items.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-burger"></i>Food Items
        </a>
        <a href="orders.php" class="sidebar-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-receipt"></i>Orders
        </a>
        <a href="customers.php" class="sidebar-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-users"></i>Customers
        </a>
        
        <hr class="my-3 text-secondary">
        
        <a href="../index.php" class="sidebar-link text-info">
          <i class="fa-solid fa-globe"></i>View Website
        </a>
        <a href="../logout.php" class="sidebar-link text-danger confirm-delete" data-message="Are you sure you want to log out?">
          <i class="fa-solid fa-power-off"></i>Logout
        </a>
      </nav>
    </aside>

    
    <main class="admin-content">
      
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm-custom mb-4" role="alert">
          <i class="fa-solid fa-circle-check me-2"></i>
          <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm-custom mb-4" role="alert">
          <i class="fa-solid fa-circle-xmark me-2"></i>
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
