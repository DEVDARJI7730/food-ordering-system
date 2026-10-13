<?php
include_once 'includes/header.php';

try {
    $stmt = $conn->query("SELECT COUNT(*) AS total FROM orders");
    $total_orders = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT SUM(total_amount) AS total, SUM(commission_amount) AS total_commission FROM orders WHERE status != 'Cancelled'");
    $revenue_data = $stmt->fetch();
    $total_revenue = $revenue_data['total'] ?? 0.00;
    $total_commission = $revenue_data['total_commission'] ?? 0.00;

    $stmt = $conn->query("SELECT COUNT(*) AS total FROM food_items");
    $total_foods = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
    $total_customers = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) AS total FROM restaurants");
    $total_restaurants = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) AS total FROM restaurants WHERE status = 'Pending'");
    $pending_restaurants = $stmt->fetch()['total'];

    $recent_orders_stmt = $conn->query("
        SELECT o.*, u.name AS customer_name, r.restaurant_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN restaurants r ON o.restaurant_id = r.id
        ORDER BY o.id DESC 
        LIMIT 5
    ");
    $recent_orders = $recent_orders_stmt->fetchAll();

    $recent_pending_rest_stmt = $conn->query("
        SELECT * FROM restaurants 
        WHERE status = 'Pending' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_pending_restaurants = $recent_pending_rest_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database stats fetch error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Dashboard Overview</h2>
    <p class="text-muted mb-0">Platform performance, financial split & verification status</p>
  </div>
  <span class="badge bg-light text-dark p-2 border"><?php echo date('l, d F Y'); ?></span>
</div>

<div class="row g-3 mb-4">
  <!-- Total Orders -->
  <div class="col-xl-2 col-md-4 col-6">
    <div class="stat-card shadow-sm-custom">
      <div>
        <span class="text-secondary text-sm d-block mb-1">Total Orders</span>
        <h3 class="fw-extrabold mb-0"><?php echo $total_orders; ?></h3>
      </div>
      <div class="stat-icon stat-primary">
        <i class="fa-solid fa-cart-shopping"></i>
      </div>
    </div>
  </div>
  
  <!-- Gross Platform Revenue -->
  <div class="col-xl-2 col-md-4 col-6">
    <div class="stat-card shadow-sm-custom">
      <div>
        <span class="text-secondary text-sm d-block mb-1">Gross Sales</span>
        <h3 class="fw-extrabold mb-0">₹<?php echo number_format($total_revenue, 2); ?></h3>
      </div>
      <div class="stat-icon stat-secondary">
        <i class="fa-solid fa-receipt"></i>
      </div>
    </div>
  </div>

  <!-- Platform Commission Revenue -->
  <div class="col-xl-3 col-md-4 col-6">
    <div class="stat-card shadow-sm-custom border-start border-4 border-success">
      <div>
        <span class="text-success text-sm fw-bold d-block mb-1"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Platform Commission</span>
        <h3 class="fw-extrabold text-success mb-0">₹<?php echo number_format($total_commission, 2); ?></h3>
        <span class="text-muted small" style="font-size: 11px;">Partner fee earnings</span>
      </div>
      <div class="stat-icon stat-success">
        <i class="fa-solid fa-indian-rupee-sign"></i>
      </div>
    </div>
  </div>

  <!-- Restaurants -->
  <div class="col-xl-2 col-md-4 col-6">
    <div class="stat-card shadow-sm-custom">
      <div>
        <span class="text-secondary text-sm d-block mb-1">Restaurants</span>
        <h3 class="fw-extrabold mb-0"><?php echo $total_restaurants; ?> <span class="fs-6 fw-normal text-muted">(<?php echo $pending_restaurants; ?>)</span></h3>
      </div>
      <div class="stat-icon stat-accent">
        <i class="fa-solid fa-store"></i>
      </div>
    </div>
  </div>

  <!-- Food Items -->
  <div class="col-xl-1 col-md-4 col-6">
    <div class="stat-card shadow-sm-custom">
      <div>
        <span class="text-secondary text-sm d-block mb-1">Foods</span>
        <h3 class="fw-extrabold mb-0"><?php echo $total_foods; ?></h3>
      </div>
      <div class="stat-icon stat-secondary">
        <i class="fa-solid fa-utensils"></i>
      </div>
    </div>
  </div>

  <!-- Customers -->
  <div class="col-xl-2 col-md-4 col-6">
    <div class="stat-card shadow-sm-custom">
      <div>
        <span class="text-secondary text-sm d-block mb-1">Customers</span>
        <h3 class="fw-extrabold mb-0"><?php echo $total_customers; ?></h3>
      </div>
      <div class="stat-icon stat-primary">
        <i class="fa-solid fa-users"></i>
      </div>
    </div>
  </div>
</div>

<!-- Pending License Verification Section -->
<?php if (!empty($recent_pending_restaurants)): ?>
<div class="card border-0 shadow-sm-custom mb-4 border-start border-4 border-warning" style="border-radius: 16px;">
  <div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-bell text-warning fs-5"></i>
        <h5 class="fw-bold mb-0">Pending License Verifications (<?php echo count($recent_pending_restaurants); ?>)</h5>
      </div>
      <a href="restaurants.php?status=Pending" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3">View All Pending</a>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Restaurant Name</th>
            <th>Owner</th>
            <th>License Number</th>
            <th>Applied On</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_pending_restaurants as $pr): ?>
            <tr>
              <td class="fw-bold"><?php echo htmlspecialchars($pr['restaurant_name']); ?></td>
              <td><?php echo htmlspecialchars($pr['owner_name']); ?> (<?php echo htmlspecialchars($pr['phone']); ?>)</td>
              <td><span class="font-monospace fw-bold text-dark"><?php echo htmlspecialchars($pr['license_number']); ?></span></td>
              <td><?php echo date('d M Y, h:i A', strtotime($pr['created_at'])); ?></td>
              <td class="text-end">
                <a href="verify-license.php?id=<?php echo $pr['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                  <i class="fa-solid fa-magnifying-glass me-1"></i>Verify License
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Recent Orders -->
<div class="row">
  <div class="col-12">
    <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Recent Orders</h5>
        <a href="orders.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Orders</a>
      </div>

      <?php if (count($recent_orders) > 0): ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="text-secondary fw-semibold">
              <tr>
                <th scope="col">Order ID</th>
                <th scope="col">Customer</th>
                <th scope="col">Restaurant</th>
                <th scope="col">Gross Total</th>
                <th scope="col">Commission</th>
                <th scope="col">Status</th>
                <th scope="col">Payment</th>
                <th scope="col">Order Date</th>
                <th scope="col" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_orders as $order): ?>
                <?php
                $badge_class = 'badge-pending';
                if ($order['status'] === 'Preparing') $badge_class = 'badge-preparing';
                elseif ($order['status'] === 'Out for Delivery') $badge_class = 'badge-delivery';
                elseif ($order['status'] === 'Delivered') $badge_class = 'badge-delivered';
                elseif ($order['status'] === 'Cancelled') $badge_class = 'badge-cancelled';
                ?>
                <tr>
                  <td class="fw-bold">#<?php echo $order['id']; ?></td>
                  <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                  <td>
                    <span class="badge bg-light text-dark border small">
                      <i class="fa-solid fa-store me-1 text-primary"></i><?php echo htmlspecialchars($order['restaurant_name'] ?? 'Direct'); ?>
                    </span>
                  </td>
                  <td class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                  <td>
                    <span class="text-success fw-bold font-monospace small">
                      +₹<?php echo number_format((float)($order['commission_amount'] ?? 0), 2); ?>
                    </span>
                    <span class="text-muted" style="font-size: 11px;">(<?php echo number_format((float)($order['commission_rate'] ?? 10), 0); ?>%)</span>
                  </td>
                  <td>
                    <span class="badge rounded-pill px-2.5 py-1.5 <?php echo $badge_class; ?>">
                      <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                  <td><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
                  <td class="text-end">
                    <a href="orders.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                      <i class="fa-regular fa-eye me-1"></i>Manage
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4">
          <i class="fa-solid fa-folder-open text-muted fs-2 mb-2"></i>
          <p class="text-secondary mb-0">No orders received yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>
