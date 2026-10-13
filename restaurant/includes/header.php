<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'restaurant_owner') {
    $_SESSION['error'] = "Access denied. Restaurant owner authorization required.";
    header("Location: ../login.php");
    exit();
}

require_once '../config/db.php';

$owner_id = $_SESSION['user_id'];
$restaurant = null;

try {
    $stmt = $conn->prepare("SELECT * FROM restaurants WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $restaurant = $stmt->fetch();
} catch (PDOException $e) {}

if (!$restaurant) {
    $_SESSION['error'] = "No restaurant profile associated with this account.";
    header("Location: ../logout.php");
    exit();
}

// Update session with latest restaurant status
$_SESSION['restaurant_id'] = $restaurant['id'];
$_SESSION['restaurant_name'] = $restaurant['restaurant_name'];
$_SESSION['restaurant_status'] = $restaurant['status'];

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($restaurant['restaurant_name']); ?> | Partner Portal</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-success d-flex align-items-center" href="dashboard.php">
        <i class="fa-solid fa-store me-2 text-success"></i>
        <span><?php echo htmlspecialchars($restaurant['restaurant_name']); ?> <span class="badge bg-secondary text-sm font-monospace ms-1" style="font-size: 0.7rem;">PARTNER</span></span>
      </a>

      <div class="ms-auto d-flex align-items-center gap-3">
        <!-- Live Status Pill in Navbar -->
        <?php if ($restaurant['status'] === 'Approved'): ?>
          <span class="badge bg-success px-3 py-1.5 rounded-pill d-none d-sm-inline-block"><i class="fa-solid fa-circle-check me-1"></i>Approved & Live</span>
        <?php elseif ($restaurant['status'] === 'Pending'): ?>
          <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill d-none d-sm-inline-block"><i class="fa-solid fa-clock me-1"></i>Pending Admin Approval</span>
        <?php elseif ($restaurant['status'] === 'Rejected'): ?>
          <span class="badge bg-danger px-3 py-1.5 rounded-pill d-none d-sm-inline-block"><i class="fa-solid fa-circle-xmark me-1"></i>Action Required (Rejected)</span>
        <?php elseif ($restaurant['status'] === 'Suspended'): ?>
          <span class="badge bg-secondary px-3 py-1.5 rounded-pill d-none d-sm-inline-block"><i class="fa-solid fa-ban me-1"></i>Suspended</span>
        <?php endif; ?>

        <span class="text-light d-none d-md-inline"><i class="fa-regular fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fa-solid fa-right-from-bracket me-1"></i>Logout</a>
      </div>
    </div>
  </nav>

  <!-- Container with Sidebar & Content -->
  <div class="admin-container">
    
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar shadow-sm">
      <nav class="nav flex-column">
        <a href="dashboard.php" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-chart-line"></i>Dashboard
        </a>
        <a href="profile.php" class="sidebar-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-id-card"></i>Restaurant & License
        </a>
        <a href="food-items.php" class="sidebar-link <?php echo $current_page === 'food-items.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-burger"></i>Menu Items
        </a>
        <a href="orders.php" class="sidebar-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
          <i class="fa-solid fa-receipt"></i>Orders
        </a>
        
        <hr class="my-3 text-secondary">
        
        <a href="../index.php?restaurant_id=<?php echo $restaurant['id']; ?>" class="sidebar-link text-info" target="_blank">
          <i class="fa-solid fa-arrow-up-right-from-square"></i>View Public Menu
        </a>
        <a href="../logout.php" class="sidebar-link text-danger">
          <i class="fa-solid fa-power-off"></i>Logout
        </a>
      </nav>
    </aside>

    <!-- Main Content Area -->
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
