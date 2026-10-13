<?php
include_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $valid_statuses = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];
    
    if (in_array($new_status, $valid_statuses) && $order_id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $_SESSION['success'] = "Order #{$order_id} status updated to '{$new_status}' successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update order status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid status update request.";
    }
    
    header("Location: orders.php" . (isset($_GET['order_id']) ? "?order_id=" . intval($_GET['order_id']) : ""));
    exit();
}

$selected_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order_details = null;
$order_items = [];

if ($selected_order_id > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT o.*, u.name AS customer_name, u.email AS customer_email, 
                   r.restaurant_name, r.city AS restaurant_city, r.phone AS restaurant_phone
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            LEFT JOIN restaurants r ON o.restaurant_id = r.id
            WHERE o.id = ?
        ");
        $stmt->execute([$selected_order_id]);
        $order_details = $stmt->fetch();

        if ($order_details) {
            $items_stmt = $conn->prepare("
                SELECT oi.*, f.name AS food_name, r.restaurant_name AS item_restaurant 
                FROM order_items oi 
                JOIN food_items f ON oi.food_item_id = f.id 
                LEFT JOIN restaurants r ON oi.restaurant_id = r.id
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$selected_order_id]);
            $order_items = $items_stmt->fetchAll();
        } else {
            $_SESSION['error'] = "Order #{$selected_order_id} not found.";
            header("Location: orders.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    try {
        $orders = $conn->query("
            SELECT o.*, u.name AS customer_name, r.restaurant_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            LEFT JOIN restaurants r ON o.restaurant_id = r.id
            ORDER BY o.id DESC
        ")->fetchAll();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="fw-bold mb-0">Manage Orders</h2>
  <?php if ($selected_order_id > 0): ?>
    <a href="orders.php" class="btn btn-outline-secondary rounded-pill px-4">
      <i class="fa-solid fa-arrow-left me-2"></i>Back to Orders
    </a>
  <?php endif; ?>
</div>

<?php if ($selected_order_id > 0 && $order_details): ?>
  
  <div class="row">
    <div class="col-lg-8 mb-4">
      <div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
          <div>
            <h5 class="fw-bold mb-1">Items in Order #<?php echo $order_details['id']; ?></h5>
            <span class="text-secondary text-sm">Ordered on: <?php echo date('d M Y, h:i A', strtotime($order_details['created_at'])); ?></span>
            <?php if (!empty($order_details['restaurant_name'])): ?>
              <span class="badge bg-light text-primary border border-primary-subtle rounded-pill ms-2 px-2.5 py-1">
                <i class="fa-solid fa-store me-1"></i><?php echo htmlspecialchars($order_details['restaurant_name']); ?>
              </span>
            <?php endif; ?>
          </div>
          <?php
          $badge_class = 'badge-pending';
          if ($order_details['status'] === 'Preparing') $badge_class = 'badge-preparing';
          elseif ($order_details['status'] === 'Out for Delivery') $badge_class = 'badge-delivery';
          elseif ($order_details['status'] === 'Delivered') $badge_class = 'badge-delivered';
          elseif ($order_details['status'] === 'Cancelled') $badge_class = 'badge-cancelled';
          ?>
          <span class="badge rounded-pill px-3 py-2 fs-6 <?php echo $badge_class; ?>">
            <?php echo $order_details['status']; ?>
          </span>
        </div>

        <div class="table-responsive mb-4">
          <table class="table table-borderless">
            <thead>
              <tr class="text-secondary border-bottom">
                <th scope="col" style="width: 50%;">Food Item</th>
                <th scope="col" style="width: 20%;">Restaurant</th>
                <th scope="col" style="width: 15%;" class="text-center">Price</th>
                <th scope="col" style="width: 5%;" class="text-center">Qty</th>
                <th scope="col" style="width: 10%;" class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($order_items as $item): ?>
                <tr class="border-bottom-light">
                  <td class="fw-semibold text-dark"><?php echo htmlspecialchars($item['food_name']); ?></td>
                  <td class="small text-muted"><?php echo htmlspecialchars($item['item_restaurant'] ?? $order_details['restaurant_name'] ?? 'Nivaala Kitchen'); ?></td>
                  <td class="text-center">₹<?php echo number_format($item['price'], 2); ?></td>
                  <td class="text-center"><?php echo $item['quantity']; ?></td>
                  <td class="text-end fw-bold text-dark">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php 
        $ord_subtotal = (float)($order_details['subtotal'] ?? 0);
        if ($ord_subtotal <= 0) {
            $ord_subtotal = round($order_details['total_amount'] / 1.05, 2);
            $ord_cgst = round($ord_subtotal * 0.025, 2);
            $ord_sgst = round($ord_subtotal * 0.025, 2);
        } else {
            $ord_cgst = (float)($order_details['cgst'] ?? round($ord_subtotal * 0.025, 2));
            $ord_sgst = (float)($order_details['sgst'] ?? round($ord_subtotal * 0.025, 2));
        }
        ?>
        <div class="row align-items-center mb-3">
          <div class="col-md-6 text-secondary text-sm">
            Payment Method: <strong><?php echo htmlspecialchars($order_details['payment_method']); ?></strong>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-between text-sm text-secondary mb-1">
              <span>Items Subtotal:</span>
              <span class="text-dark fw-medium">₹<?php echo number_format($ord_subtotal, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between text-sm text-secondary mb-1">
              <span>CGST (2.5%):</span>
              <span class="text-dark fw-medium">₹<?php echo number_format($ord_cgst, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between text-sm text-secondary mb-1">
              <span>SGST (2.5%):</span>
              <span class="text-dark fw-medium">₹<?php echo number_format($ord_sgst, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between text-sm text-secondary mb-2">
              <span>Delivery Charges:</span>
              <span class="text-success fw-medium">FREE</span>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <span class="text-secondary fw-bold">Gross Order Total</span>
              <span class="fs-4 fw-extrabold text-primary">₹<?php echo number_format($order_details['total_amount'], 2); ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial Split Breakdown Card -->
      <div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-coins text-success me-2"></i>Order Financial & Commission Split</h5>
        <div class="row g-3">
          <div class="col-md-3">
            <div class="p-3 bg-light rounded-3">
              <span class="text-muted small d-block mb-1">Food Subtotal</span>
              <h5 class="fw-bold text-dark mb-0">₹<?php echo number_format($ord_subtotal, 2); ?></h5>
              <span class="text-muted small">Tax: ₹<?php echo number_format($ord_cgst + $ord_sgst, 2); ?> (5%)</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 bg-light rounded-3">
              <span class="text-muted small d-block mb-1">Gross Order Total</span>
              <h5 class="fw-bold text-dark mb-0">₹<?php echo number_format($order_details['total_amount'], 2); ?></h5>
              <span class="text-muted small">Paid by customer</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle">
              <span class="text-success small fw-bold d-block mb-1"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Commission (<?php echo number_format((float)($order_details['commission_rate'] ?? 10), 1); ?>%)</span>
              <h5 class="fw-bold text-success mb-0">₹<?php echo number_format((float)($order_details['commission_amount'] ?? 0), 2); ?></h5>
              <span class="text-muted small">Platform revenue</span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 bg-primary-subtle rounded-3 border border-primary-subtle">
              <span class="text-primary small fw-bold d-block mb-1"><i class="fa-solid fa-store me-1"></i>Partner Net Payout</span>
              <h5 class="fw-bold text-primary mb-0">₹<?php echo number_format((float)($order_details['partner_earnings'] ?? $order_details['total_amount']), 2); ?></h5>
              <span class="text-muted small">Payable to restaurant</span>
            </div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-truck text-secondary me-2"></i>Delivery & Customer Details</h5>
        <div class="row">
          <div class="col-sm-6 mb-3 mb-sm-0">
            <p class="mb-1 text-secondary">Customer Name</p>
            <h6 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($order_details['customer_name']); ?></h6>
            <p class="mb-1 text-secondary">Email Address</p>
            <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($order_details['customer_email']); ?></h6>
          </div>
          <div class="col-sm-6">
            <p class="mb-1 text-secondary">Contact Phone</p>
            <h6 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($order_details['phone']); ?></h6>
            <p class="mb-1 text-secondary">Shipping Address</p>
            <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($order_details['delivery_address']); ?></h6>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
        <h5 class="fw-bold mb-4">Update Order Status</h5>
        
        <form action="orders.php?order_id=<?php echo $order_details['id']; ?>" method="POST">
          <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
          
          <div class="mb-4">
            <label for="status" class="form-label text-secondary">Fulfillment Status</label>
            <select class="form-select rounded-pill px-3 py-2 fw-semibold" id="status" name="status">
              <option value="Pending" <?php echo $order_details['status'] === 'Pending' ? 'selected' : ''; ?>>Pending (Awaiting Action)</option>
              <option value="Preparing" <?php echo $order_details['status'] === 'Preparing' ? 'selected' : ''; ?>>Preparing (In Kitchen)</option>
              <option value="Out for Delivery" <?php echo $order_details['status'] === 'Out for Delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
              <option value="Delivered" <?php echo $order_details['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
              <option value="Cancelled" <?php echo $order_details['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
          </div>

          <div class="d-grid">
            <button type="submit" name="update_status" class="btn btn-primary rounded-pill py-2.5 fw-bold text-uppercase">
              <i class="fa-solid fa-arrows-rotate me-2"></i>Update Status
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php else: ?>
  
  <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
    <h5 class="fw-bold mb-4">Orders List</h5>

    <?php if (count($orders) > 0): ?>
      <div class="table-responsive">
        <table class="table align-middle table-hover">
          <thead class="text-secondary fw-semibold">
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Customer</th>
              <th scope="col">Restaurant</th>
              <th scope="col">Gross Total</th>
              <th scope="col">Commission</th>
              <th scope="col">Partner Net</th>
              <th scope="col">Status</th>
              <th scope="col">Payment</th>
              <th scope="col">Order Date</th>
              <th scope="col" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <?php
              $badge_class = 'badge-pending';
              if ($order['status'] === 'Preparing') $badge_class = 'badge-preparing';
              elseif ($order['status'] === 'Out for Delivery') $badge_class = 'badge-delivery';
              elseif ($order['status'] === 'Delivered') $badge_class = 'badge-delivered';
              elseif ($order['status'] === 'Cancelled') $badge_class = 'badge-cancelled';
              ?>
              <tr>
                <td class="fw-bold">#<?php echo $order['id']; ?></td>
                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                <td>
                  <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2 py-1 small">
                    <i class="fa-solid fa-store me-1"></i><?php echo htmlspecialchars($order['restaurant_name'] ?? 'Nivaala Kitchen'); ?>
                  </span>
                </td>
                <td class="fw-bold text-dark">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                <td>
                  <span class="text-success fw-bold font-monospace small">
                    ₹<?php echo number_format((float)($order['commission_amount'] ?? 0), 2); ?>
                  </span>
                  <div class="text-muted" style="font-size: 11px;"><?php echo number_format((float)($order['commission_rate'] ?? 10), 0); ?>% fee</div>
                </td>
                <td>
                  <span class="text-primary fw-bold font-monospace small">
                    ₹<?php echo number_format((float)($order['partner_earnings'] ?? $order['total_amount']), 2); ?>
                  </span>
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
        <i class="fa-solid fa-receipt text-muted fs-1 mb-2"></i>
        <p class="text-secondary mb-0">No orders placed on the system yet.</p>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>
