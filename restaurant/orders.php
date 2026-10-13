<?php
include_once 'includes/header.php';

$rest_id = $restaurant['id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $valid_statuses = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        try {
            // Verify this order strictly belongs to this logged-in restaurant
            $check_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND restaurant_id = ?");
            $check_stmt->execute([$order_id, $rest_id]);
            if ($check_stmt->fetch()) {
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND restaurant_id = ?");
                $stmt->execute([$new_status, $order_id, $rest_id]);
                $_SESSION['success'] = "Order #$order_id status updated to '$new_status'!";
            } else {
                $_SESSION['error'] = "Unauthorized: This order does not belong to your restaurant.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
        }
    }
    header("Location: orders.php" . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

$status_filter = $_GET['status'] ?? 'all';
$selected_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch orders strictly for this restaurant
$query = "
    SELECT o.*, u.name AS customer_name, u.email AS customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.restaurant_id = :rest_id
";

$params = [':rest_id' => $rest_id];

if ($status_filter !== 'all') {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

$query .= " ORDER BY o.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// If specific order selected, fetch items strictly for this restaurant
$order_details = null;
$order_items = [];
if ($selected_order_id > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT o.*, u.name AS customer_name, u.email AS customer_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ? AND o.restaurant_id = ?
        ");
        $stmt->execute([$selected_order_id, $rest_id]);
        $order_details = $stmt->fetch();

        if ($order_details) {
            $stmt = $conn->prepare("
                SELECT oi.*, f.name, f.image_url 
                FROM order_items oi 
                JOIN food_items f ON oi.food_item_id = f.id 
                WHERE oi.order_id = ? AND oi.restaurant_id = ?
            ");
            $stmt->execute([$selected_order_id, $rest_id]);
            $order_items = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching order items: " . $e->getMessage();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Restaurant Orders</h2>
    <p class="text-muted mb-0">Track and fulfill customer orders placed at your kitchen</p>
  </div>
</div>

<!-- Filter Tabs -->
<div class="card border-0 shadow-sm-custom mb-4" style="border-radius: 12px;">
  <div class="card-body p-2">
    <div class="nav nav-pills nav-fill">
      <a class="nav-link rounded-pill <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="orders.php">All Orders</a>
      <a class="nav-link rounded-pill <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>" href="orders.php?status=Pending">Pending</a>
      <a class="nav-link rounded-pill <?php echo $status_filter === 'Preparing' ? 'active' : ''; ?>" href="orders.php?status=Preparing">Preparing</a>
      <a class="nav-link rounded-pill <?php echo $status_filter === 'Out for Delivery' ? 'active' : ''; ?>" href="orders.php?status=Out for Delivery">Out for Delivery</a>
      <a class="nav-link rounded-pill <?php echo $status_filter === 'Delivered' ? 'active' : ''; ?>" href="orders.php?status=Delivered">Delivered</a>
      <a class="nav-link rounded-pill <?php echo $status_filter === 'Cancelled' ? 'active' : ''; ?>" href="orders.php?status=Cancelled">Cancelled</a>
    </div>
  </div>
</div>

<?php if ($order_details): ?>
  <!-- Order Detail Card -->
  <div class="card border-0 shadow-sm-custom p-4 mb-4 border-start border-4 border-primary" style="border-radius: 16px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0">Order Details #<?php echo $order_details['id']; ?></h5>
      <a href="orders.php<?php echo $status_filter !== 'all' ? '?status=' . urlencode($status_filter) : ''; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
        <i class="fa-solid fa-xmark me-1"></i>Close View
      </a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="p-3 bg-light rounded">
          <span class="text-muted small d-block">Customer</span>
          <strong><?php echo htmlspecialchars($order_details['customer_name']); ?></strong>
          <div class="small text-muted"><?php echo htmlspecialchars($order_details['phone']); ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 bg-light rounded">
          <span class="text-muted small d-block">Delivery Address</span>
          <div class="small"><?php echo nl2br(htmlspecialchars($order_details['delivery_address'])); ?></div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="p-3 bg-light rounded">
          <span class="text-muted small d-block">Payment Method</span>
          <strong><?php echo htmlspecialchars($order_details['payment_method']); ?></strong>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-3 bg-light rounded">
          <span class="text-muted small d-block">Update Status</span>
          <form method="POST" action="orders.php" class="d-flex gap-2 mt-1">
            <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
            <select name="status" class="form-select form-select-sm">
              <?php foreach (['Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo $order_details['status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="update_status" class="btn btn-primary btn-sm rounded-pill px-3">Update</button>
          </form>
        </div>
      </div>
    </div>

    <h6 class="fw-bold mb-2">Items & Financial Slip for this Order</h6>
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Dish</th>
            <th>Unit Price</th>
            <th>Quantity</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $calc_total = 0;
          foreach ($order_items as $oi): 
            $sub = $oi['price'] * $oi['quantity'];
            $calc_total += $sub;
          ?>
            <tr>
              <td><?php echo htmlspecialchars($oi['name']); ?></td>
              <td>₹<?php echo number_format($oi['price'], 2); ?></td>
              <td><?php echo $oi['quantity']; ?></td>
              <td class="text-end fw-bold">₹<?php echo number_format($sub, 2); ?></td>
            </tr>
          <?php endforeach; 
          $ord_subtotal = (float)($order_details['subtotal'] ?? 0);
          if ($ord_subtotal <= 0) {
              $ord_subtotal = round($order_details['total_amount'] / 1.05, 2);
              $ord_cgst = round($ord_subtotal * 0.025, 2);
              $ord_sgst = round($ord_subtotal * 0.025, 2);
          } else {
              $ord_cgst = (float)($order_details['cgst'] ?? round($ord_subtotal * 0.025, 2));
              $ord_sgst = (float)($order_details['sgst'] ?? round($ord_subtotal * 0.025, 2));
          }
          $comm_rate = (float)($order_details['commission_rate'] ?? 10.00);
          $comm_amt = (float)($order_details['commission_amount'] ?? round($ord_subtotal * ($comm_rate / 100), 2));
          $net_amt = (float)($order_details['partner_earnings'] ?? ($order_details['total_amount'] - $comm_amt));
          ?>
          <tr>
            <td colspan="3" class="text-end text-secondary fw-semibold">Food Items Subtotal:</td>
            <td class="text-end fw-bold text-dark">₹<?php echo number_format($ord_subtotal, 2); ?></td>
          </tr>
          <tr>
            <td colspan="3" class="text-end text-secondary">CGST (2.5%):</td>
            <td class="text-end text-dark">₹<?php echo number_format($ord_cgst, 2); ?></td>
          </tr>
          <tr>
            <td colspan="3" class="text-end text-secondary">SGST (2.5%):</td>
            <td class="text-end text-dark">₹<?php echo number_format($ord_sgst, 2); ?></td>
          </tr>
          <tr class="table-light">
            <td colspan="3" class="text-end text-secondary fw-bold">Gross Order Total (Customer Billing):</td>
            <td class="text-end fw-bold text-dark fs-6">₹<?php echo number_format($order_details['total_amount'], 2); ?></td>
          </tr>
          <tr>
            <td colspan="3" class="text-end text-danger fw-semibold">
              <i class="fa-solid fa-hand-holding-dollar me-1"></i>Platform Commission (<?php echo number_format($comm_rate, 1); ?>% on food subtotal):
            </td>
            <td class="text-end fw-bold text-danger">-₹<?php echo number_format($comm_amt, 2); ?></td>
          </tr>
          <tr class="table-success">
            <td colspan="3" class="text-end fw-bold text-success fs-6">
              <i class="fa-solid fa-coins me-1"></i>Net Partner Earnings Payable:
            </td>
            <td class="text-end fw-extrabold text-success fs-5">₹<?php echo number_format($net_amt, 2); ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Orders Table -->
<div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
  <?php if (count($orders) > 0): ?>
    <div class="table-responsive">
      <table class="table align-middle table-hover">
        <thead class="table-light">
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Delivery Address</th>
            <th>Gross Value</th>
            <th>Commission</th>
            <th>Net Earning</th>
            <th>Status</th>
            <th>Ordered At</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): 
            $badge_class = 'badge-pending';
            if ($order['status'] === 'Preparing') $badge_class = 'badge-preparing';
            elseif ($order['status'] === 'Out for Delivery') $badge_class = 'badge-delivery';
            elseif ($order['status'] === 'Delivered') $badge_class = 'badge-delivered';
            elseif ($order['status'] === 'Cancelled') $badge_class = 'badge-cancelled';
            $ord_comm = (float)($order['commission_amount'] ?? ($order['total_amount'] * 0.10));
            $ord_net = (float)($order['partner_earnings'] ?? ($order['total_amount'] - $ord_comm));
          ?>
            <tr class="<?php echo $selected_order_id === (int)$order['id'] ? 'table-primary' : ''; ?>">
              <td class="fw-bold">#<?php echo $order['id']; ?></td>
              <td>
                <div class="fw-medium"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                <div class="text-muted small"><?php echo htmlspecialchars($order['phone']); ?></div>
              </td>
              <td class="text-muted small" style="max-width: 200px;"><?php echo htmlspecialchars(substr($order['delivery_address'], 0, 40)) . '...'; ?></td>
              <td class="fw-bold text-dark">₹<?php echo number_format($order['total_amount'], 2); ?></td>
              <td>
                <span class="text-danger font-monospace small">-₹<?php echo number_format($ord_comm, 2); ?></span>
                <div class="text-muted" style="font-size: 11px;"><?php echo number_format((float)($order['commission_rate'] ?? 10), 0); ?>% fee</div>
              </td>
              <td>
                <span class="fw-bold text-success font-monospace">₹<?php echo number_format($ord_net, 2); ?></span>
              </td>
              <td>
                <span class="badge rounded-pill px-2.5 py-1.5 <?php echo $badge_class; ?>">
                  <?php echo htmlspecialchars($order['status']); ?>
                </span>
              </td>
              <td><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
              <td class="text-end">
                <a href="orders.php?order_id=<?php echo $order['id']; ?><?php echo $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                  <i class="fa-regular fa-eye me-1"></i>View & Manage
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
      <p class="text-muted mb-0">No orders found in this status category.</p>
    </div>
  <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
