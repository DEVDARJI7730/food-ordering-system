<?php
include_once 'includes/header.php';

$rest_id = $restaurant['id'];

// Calculate Restaurant Specific Stats
try {
    // Total Menu Items
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(is_available = 1) as available FROM food_items WHERE restaurant_id = ?");
    $stmt->execute([$rest_id]);
    $food_stats = $stmt->fetch();
    $total_menu_items = (int)($food_stats['total'] ?? 0);
    $active_menu_items = (int)($food_stats['available'] ?? 0);

    // Total Orders & Revenue Split strictly for this restaurant
    $stmt = $conn->prepare("SELECT COUNT(o.id) as total_orders, 
                                   SUM(o.total_amount) as gross_sales,
                                   SUM(o.commission_amount) as total_commission,
                                   SUM(o.partner_earnings) as net_earnings
                            FROM orders o 
                            WHERE o.restaurant_id = ?
                            AND o.status != 'Cancelled'");
    $stmt->execute([$rest_id]);
    $order_stats = $stmt->fetch();
    $total_orders = (int)($order_stats['total_orders'] ?? 0);
    $gross_sales = (float)($order_stats['gross_sales'] ?? 0.0);
    $total_commission = (float)($order_stats['total_commission'] ?? 0.0);
    $net_earnings = (float)($order_stats['net_earnings'] ?? ($gross_sales - $total_commission));

    // Recent Orders strictly for this restaurant
    $recent_stmt = $conn->prepare("SELECT o.*, u.name as customer_name 
                                  FROM orders o 
                                  JOIN users u ON o.user_id = u.id
                                  WHERE o.restaurant_id = ?
                                  ORDER BY o.id DESC 
                                  LIMIT 5");
    $recent_stmt->execute([$rest_id]);
    $recent_orders = $recent_stmt->fetchAll();

} catch (PDOException $e) {
    die("Error fetching dashboard statistics: " . $e->getMessage());
}

$today = new DateTime();
$expiry_date = new DateTime($restaurant['license_expiry_date']);
$is_expired = $expiry_date < $today;
$days_remaining = $today->diff($expiry_date)->days;
if ($is_expired) $days_remaining = -$days_remaining;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Restaurant Dashboard</h2>
    <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($restaurant['owner_name']); ?> | <strong><?php echo htmlspecialchars($restaurant['restaurant_name']); ?></strong></p>
  </div>
  <div class="d-flex gap-2">
    <span class="badge bg-light text-dark border p-2 d-flex align-items-center">
      <i class="fa-solid fa-hand-holding-dollar text-primary me-1"></i>Commission Rate: <strong class="ms-1 font-monospace"><?php echo number_format((float)($restaurant['commission_rate'] ?? 10.00), 2); ?>%</strong>
    </span>
    <a href="food-items.php?action=add" class="btn btn-primary rounded-pill px-3 shadow-sm">
      <i class="fa-solid fa-plus me-1"></i>Add Menu Item
    </a>
  </div>
</div>

<!-- Dynamic Status Notification Banners -->
<?php if ($restaurant['status'] === 'Pending'): ?>
  <div class="alert alert-warning border-0 shadow-sm p-4 mb-4" style="border-radius: 14px;">
    <div class="d-flex align-items-start gap-3">
      <i class="fa-solid fa-hourglass-half fs-2 text-warning mt-1"></i>
      <div>
        <h5 class="fw-bold text-warning-emphasis mb-1">License Verification Pending Admin Approval</h5>
        <p class="mb-2 text-secondary">
          Your restaurant application and license document (<strong><?php echo htmlspecialchars($restaurant['license_number']); ?></strong>) have been submitted and are currently being reviewed by our compliance team.
        </p>
        <div class="small text-muted">
          <i class="fa-solid fa-circle-info me-1"></i>You can continue setting up your food menu and business profile. Your restaurant will become live for customer orders as soon as the administrator verifies your license.
        </div>
      </div>
    </div>
  </div>
<?php elseif ($restaurant['status'] === 'Rejected'): ?>
  <div class="alert alert-danger border-0 shadow-sm p-4 mb-4" style="border-radius: 14px;">
    <div class="d-flex align-items-start gap-3">
      <i class="fa-solid fa-triangle-exclamation fs-2 text-danger mt-1"></i>
      <div>
        <h5 class="fw-bold text-danger mb-1">Application Action Required</h5>
        <p class="mb-2 text-secondary">
          Your restaurant application was not approved during license verification.
        </p>
        <?php if (!empty($restaurant['rejection_reason'])): ?>
          <div class="p-3 bg-white rounded border border-danger-subtle mb-3">
            <strong class="text-danger"><i class="fa-solid fa-circle-info me-1"></i>Admin Reason:</strong>
            <p class="mb-0 text-dark"><?php echo nl2br(htmlspecialchars($restaurant['rejection_reason'])); ?></p>
          </div>
        <?php endif; ?>
        <a href="profile.php" class="btn btn-danger btn-sm rounded-pill px-3">
          <i class="fa-solid fa-file-pen me-1"></i>Update License / Profile Details
        </a>
      </div>
    </div>
  </div>
<?php elseif ($restaurant['status'] === 'Suspended'): ?>
  <div class="alert alert-secondary border-0 shadow-sm p-4 mb-4" style="border-radius: 14px;">
    <div class="d-flex align-items-start gap-3">
      <i class="fa-solid fa-ban fs-2 text-secondary mt-1"></i>
      <div>
        <h5 class="fw-bold mb-1">Restaurant Operations Suspended</h5>
        <p class="mb-0 text-muted">
          Your restaurant ordering is temporarily suspended. Please contact platform support or admin for assistance.
        </p>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="alert alert-success border-0 shadow-sm p-3 mb-4 d-flex align-items-center justify-content-between" style="border-radius: 12px;">
    <div class="d-flex align-items-center gap-2">
      <i class="fa-solid fa-circle-check fs-4 text-success"></i>
      <div>
        <strong class="text-success">Verified & Active Partner</strong>
        <span class="text-muted small ms-2 d-none d-md-inline">License #<?php echo htmlspecialchars($restaurant['license_number']); ?></span>
      </div>
    </div>
    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill font-monospace">ONLINE & ACCEPTING ORDERS</span>
  </div>
<?php endif; ?>

<!-- Financial & Operations Stats Cards -->
<div class="row g-3 mb-4">
  <!-- Total Orders -->
  <div class="col-xl-3 col-md-6 col-6">
    <div class="stat-card shadow-sm-custom">
      <div>
        <span class="text-secondary text-sm d-block mb-1">Orders Received</span>
        <h3 class="fw-extrabold mb-0"><?php echo $total_orders; ?></h3>
        <span class="text-muted small"><?php echo $active_menu_items; ?> menu items live</span>
      </div>
      <div class="stat-icon stat-primary">
        <i class="fa-solid fa-cart-shopping"></i>
      </div>
    </div>
  </div>

  <!-- Gross Sales -->
  <div class="col-xl-3 col-md-6 col-6">
    <div class="stat-card shadow-sm-custom">
      <div>
        <span class="text-secondary text-sm d-block mb-1">Gross Sales</span>
        <h3 class="fw-extrabold text-dark mb-0">₹<?php echo number_format($gross_sales, 2); ?></h3>
        <span class="text-muted small">Total customer billings</span>
      </div>
      <div class="stat-icon stat-secondary">
        <i class="fa-solid fa-receipt"></i>
      </div>
    </div>
  </div>

  <!-- Commission Deducted -->
  <div class="col-xl-3 col-md-6 col-6">
    <div class="stat-card shadow-sm-custom border-start border-4 border-warning">
      <div>
        <span class="text-warning-emphasis text-sm fw-bold d-block mb-1">Commission (<?php echo number_format((float)($restaurant['commission_rate'] ?? 10.00), 1); ?>%)</span>
        <h3 class="fw-extrabold text-danger mb-0">-₹<?php echo number_format($total_commission, 2); ?></h3>
        <span class="text-muted small">Platform fee per order</span>
      </div>
      <div class="stat-icon stat-accent">
        <i class="fa-solid fa-percent"></i>
      </div>
    </div>
  </div>

  <!-- Net Earnings -->
  <div class="col-xl-3 col-md-6 col-6">
    <div class="stat-card shadow-sm-custom border-start border-4 border-success">
      <div>
        <span class="text-success text-sm fw-bold d-block mb-1">Net Partner Earnings</span>
        <h3 class="fw-extrabold text-success mb-0">₹<?php echo number_format($net_earnings, 2); ?></h3>
        <span class="text-muted small">Payable revenue</span>
      </div>
      <div class="stat-icon stat-success">
        <i class="fa-solid fa-indian-rupee-sign"></i>
      </div>
    </div>
  </div>
</div>

<!-- Recent Orders Table -->
<div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Recent Orders for <?php echo htmlspecialchars($restaurant['restaurant_name']); ?></h5>
    <a href="orders.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Orders</a>
  </div>

  <?php if (!empty($recent_orders)): ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="text-secondary fw-semibold">
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Gross Value</th>
            <th>Commission (<?php echo number_format((float)($restaurant['commission_rate'] ?? 10.00), 0); ?>%)</th>
            <th>Net Earning</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Ordered At</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_orders as $order): 
            $badge_class = 'badge-pending';
            if ($order['status'] === 'Preparing') $badge_class = 'badge-preparing';
            elseif ($order['status'] === 'Out for Delivery') $badge_class = 'badge-delivery';
            elseif ($order['status'] === 'Delivered') $badge_class = 'badge-delivered';
            elseif ($order['status'] === 'Cancelled') $badge_class = 'badge-cancelled';
            $ord_comm = (float)($order['commission_amount'] ?? ($order['total_amount'] * 0.10));
            $ord_net = (float)($order['partner_earnings'] ?? ($order['total_amount'] - $ord_comm));
          ?>
            <tr>
              <td class="fw-bold">#<?php echo $order['id']; ?></td>
              <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
              <td class="fw-bold">₹<?php echo number_format($order['total_amount'], 2); ?></td>
              <td class="text-danger font-monospace small">-₹<?php echo number_format($ord_comm, 2); ?></td>
              <td class="fw-bold text-success font-monospace">₹<?php echo number_format($ord_net, 2); ?></td>
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
    <div class="text-center py-5">
      <i class="fa-solid fa-receipt text-muted fs-1 mb-2 opacity-50"></i>
      <p class="text-muted mb-0">No orders received for your restaurant yet.</p>
    </div>
  <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
