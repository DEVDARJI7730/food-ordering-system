<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must log in to view your order history.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $orders_stmt = $conn->prepare("
        SELECT o.*, r.restaurant_name, r.city AS restaurant_city 
        FROM orders o 
        LEFT JOIN restaurants r ON o.restaurant_id = r.id 
        WHERE o.user_id = ? 
        ORDER BY o.id DESC
    ");
    $orders_stmt->execute([$user_id]);
    $orders = $orders_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include_once 'includes/header.php';
?>

<div class="row my-4">
  <div class="col-12">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">My Orders</li>
      </ol>
    </nav>
    <h2 class="fw-bold mb-4">Your Order History</h2>
  </div>

  <?php if (count($orders) > 0): ?>
    <div class="col-12">
      <?php foreach ($orders as $order): ?>
        <?php
        try {
            $items_stmt = $conn->prepare("
                SELECT oi.*, f.name AS food_name, r.restaurant_name AS item_restaurant 
                FROM order_items oi 
                JOIN food_items f ON oi.food_item_id = f.id 
                LEFT JOIN restaurants r ON oi.restaurant_id = r.id 
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$order['id']]);
            $items = $items_stmt->fetchAll();
        } catch (PDOException $e) {
            $items = [];
        }

        $badge_class = 'badge-pending';
        if ($order['status'] === 'Preparing') {
            $badge_class = 'badge-preparing';
        } elseif ($order['status'] === 'Out for Delivery') {
            $badge_class = 'badge-delivery';
        } elseif ($order['status'] === 'Delivered') {
            $badge_class = 'badge-delivered';
        } elseif ($order['status'] === 'Cancelled') {
            $badge_class = 'badge-cancelled';
        }
        ?>

        <div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
          
          <div class="row align-items-center mb-3 pb-3 border-bottom">
            <div class="col-sm-6 mb-2 mb-sm-0">
              <span class="fs-5 fw-bold text-dark">Order ID: #<?php echo $order['id']; ?></span>
              <?php if (!empty($order['restaurant_name'])): ?>
                <span class="badge bg-light text-primary border border-primary-subtle rounded-pill ms-2 px-2.5 py-1 small">
                  <i class="fa-solid fa-store me-1"></i><?php echo htmlspecialchars($order['restaurant_name']); ?>
                </span>
              <?php endif; ?>
              <span class="d-block text-secondary text-sm mt-1">Ordered on: <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="col-sm-6 text-sm-end">
              <span class="badge px-3 py-2 rounded-pill font-weight-bold <?php echo $badge_class; ?>" style="font-size: 0.9rem;">
                <?php echo htmlspecialchars($order['status']); ?>
              </span>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-7">
              <h6 class="fw-bold text-secondary mb-2">Items Ordered</h6>
              <ul class="list-group list-group-flush mb-3 mb-md-0">
                <?php foreach ($items as $item): ?>
                  <li class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent border-light">
                    <span class="text-dark fw-medium">
                      <?php echo htmlspecialchars($item['food_name']); ?> 
                      <span class="text-secondary text-sm font-weight-normal ms-1">x <?php echo $item['quantity']; ?></span>
                    </span>
                    <span class="text-dark">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
            
            <div class="col-md-5 border-start-md">
              <h6 class="fw-bold text-secondary mb-2">Delivery & Payment Details</h6>
              <p class="mb-1 text-dark"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
              <p class="mb-1 text-dark"><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
              <p class="mb-0 text-dark"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
            </div>
          </div>

          <div class="pt-3 border-top bg-transparent">
            <?php 
            $ord_subtotal = (float)($order['subtotal'] ?? 0);
            if ($ord_subtotal <= 0) {
                // Fallback for orders created before migration
                $ord_subtotal = round($order['total_amount'] / 1.05, 2);
                $ord_cgst = round($ord_subtotal * 0.025, 2);
                $ord_sgst = round($ord_subtotal * 0.025, 2);
            } else {
                $ord_cgst = (float)($order['cgst'] ?? round($ord_subtotal * 0.025, 2));
                $ord_sgst = (float)($order['sgst'] ?? round($ord_subtotal * 0.025, 2));
            }
            ?>
            <div class="row text-sm text-secondary mb-2">
              <div class="col-sm-6"></div>
              <div class="col-sm-6">
                <div class="d-flex justify-content-between mb-1">
                  <span>Subtotal:</span>
                  <span class="text-dark fw-medium">₹<?php echo number_format($ord_subtotal, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>CGST (2.5%):</span>
                  <span class="text-dark fw-medium">₹<?php echo number_format($ord_cgst, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>SGST (2.5%):</span>
                  <span class="text-dark fw-medium">₹<?php echo number_format($ord_sgst, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>Delivery Charges:</span>
                  <span class="text-success fw-medium">FREE</span>
                </div>
              </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pt-2 border-top gap-3">
              <div>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">
                  <i class="fa-solid fa-receipt me-1 text-primary"></i>Order #<?php echo $order['id']; ?> Completed
                </span>
              </div>
              <div class="text-sm-end">
                <span class="fs-6 fw-bold text-dark me-2">Grand Total:</span>
                <span class="fs-4 fw-extrabold text-primary">₹<?php echo number_format($order['total_amount'], 2); ?></span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="col-12 text-center my-5 py-5 bg-white rounded-4 border border-light shadow-sm-custom">
      <i class="fa-solid fa-receipt text-muted display-1 mb-4"></i>
      <h3 class="fw-bold text-dark">No Orders Found</h3>
      <p class="text-secondary col-md-6 mx-auto mb-4">You haven't placed any orders yet. Once you make an order, you will see its status and details here.</p>
      <a href="index.php" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold"><i class="fa-solid fa-bowl-food me-2"></i>Order Now</a>
    </div>
  <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
