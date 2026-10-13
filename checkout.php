<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must log in to checkout.";
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty. Please add items before checking out.";
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $phone = $_POST['phone'] ?? $user['phone'];
    $address = $_POST['address'] ?? $user['address'];
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$cart_items = [];
$subtotal_amount = 0.00;

$ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';

$checkout_restaurants = [];

try {
    $stmt = $conn->prepare("SELECT f.*, r.restaurant_name FROM food_items f 
                            LEFT JOIN restaurants r ON f.restaurant_id = r.id 
                            WHERE f.id IN ($placeholders) AND f.is_available = 1");
    $stmt->execute($ids);
    $db_items = $stmt->fetchAll();

    foreach ($db_items as $item) {
        $id = $item['id'];
        $qty = $_SESSION['cart'][$id];
        $item_subtotal = $item['price'] * $qty;
        $subtotal_amount += $item_subtotal;
        $r_name = !empty($item['restaurant_name']) ? $item['restaurant_name'] : 'Restaurant Kitchen';
        if (!in_array($r_name, $checkout_restaurants)) {
            $checkout_restaurants[] = $r_name;
        }

        $cart_items[] = [
            'id' => $id,
            'restaurant_id' => $item['restaurant_id'],
            'restaurant_name' => $r_name,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $qty,
            'subtotal' => $item_subtotal
        ];
    }
} catch (PDOException $e) {
    die("Error loading cart for checkout: " . $e->getMessage());
}

$cgst_amount = round($subtotal_amount * 0.025, 2);
$sgst_amount = round($subtotal_amount * 0.025, 2);
$delivery_fee = 0.00;
$total_amount = $subtotal_amount + $cgst_amount + $sgst_amount + $delivery_fee;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';
    if ($payment_method === 'Online Payment') {
        $online_type = $_POST['online_payment_type'] ?? 'card';
        if ($online_type === 'upi') {
            $payment_method = 'Online (UPI Scan)';
        } else {
            $payment_method = 'Online (Card)';
        }
    }

    if (empty($phone)) $errors[] = "Contact phone number is required.";
    if (empty($address)) $errors[] = "Delivery address is required.";

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Group cart items by restaurant
            $restaurant_groups = [];
            foreach ($cart_items as $item) {
                $r_id = (int)$item['restaurant_id'];
                if (!isset($restaurant_groups[$r_id])) {
                    $restaurant_groups[$r_id] = [
                        'restaurant_name' => $item['restaurant_name'],
                        'items' => [],
                        'subtotal' => 0.00
                    ];
                }
                $restaurant_groups[$r_id]['items'][] = $item;
                $restaurant_groups[$r_id]['subtotal'] += $item['subtotal'];
            }

            $created_order_ids = [];

            // Process separate order for each restaurant
            foreach ($restaurant_groups as $r_id => $group) {
                $r_subtotal = $group['subtotal'];
                $r_cgst = round($r_subtotal * 0.025, 2);
                $r_sgst = round($r_subtotal * 0.025, 2);
                $r_delivery = 0.00;
                $r_total = $r_subtotal + $r_cgst + $r_sgst + $r_delivery;

                // Get restaurant commission rate
                $comm_rate = 10.00;
                $r_comm_stmt = $conn->prepare("SELECT commission_rate FROM restaurants WHERE id = ?");
                $r_comm_stmt->execute([$r_id]);
                $rc_row = $r_comm_stmt->fetch();
                if ($rc_row && isset($rc_row['commission_rate'])) {
                    $comm_rate = (float)$rc_row['commission_rate'];
                }

                $comm_amt = round($r_subtotal * ($comm_rate / 100), 2);
                $partner_earn = $r_total - $comm_amt;

                $ord_stmt = $conn->prepare("INSERT INTO orders (user_id, restaurant_id, subtotal, cgst, sgst, delivery_fee, total_amount, commission_rate, commission_amount, partner_earnings, delivery_address, phone, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $ord_stmt->execute([$user_id, $r_id, $r_subtotal, $r_cgst, $r_sgst, $r_delivery, $r_total, $comm_rate, $comm_amt, $partner_earn, $address, $phone, $payment_method]);
                $order_id = (int)$conn->lastInsertId();
                $created_order_ids[] = $order_id;

                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, restaurant_id, food_item_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
                foreach ($group['items'] as $it) {
                    $item_stmt->execute([$order_id, $r_id, $it['id'], $it['quantity'], $it['price']]);
                }
            }

            $conn->commit();

            $_SESSION['cart'] = [];
            if (count($created_order_ids) === 1) {
                $_SESSION['success'] = "Order placed successfully! Order ID: #" . $created_order_ids[0];
            } else {
                $_SESSION['success'] = "Orders placed successfully across " . count($created_order_ids) . " restaurants! Order IDs: #" . implode(', #', $created_order_ids);
            }
            header("Location: orders.php");
            exit();

        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Transaction failed: " . $e->getMessage();
        }
    }
}

include_once 'includes/header.php';
?>

<div class="row my-4">
  <div class="col-12">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
        <li class="breadcrumb-item"><a href="cart.php" class="text-decoration-none">Cart</a></li>
        <li class="breadcrumb-item active" aria-current="page">Checkout</li>
      </ol>
    </nav>
    <h2 class="fw-bold mb-4">Complete Your Order</h2>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="col-12">
      <div class="alert alert-danger border-0 shadow-sm-custom">
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <div class="col-lg-7 mb-4">
    <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
      <h4 class="fw-bold mb-4"><i class="fa-solid fa-truck text-primary me-2"></i>Delivery Details</h4>
      
      <form action="checkout.php" method="POST">
        <div class="mb-3">
          <label for="name" class="form-label text-secondary">Customer Name</label>
          <input type="text" class="form-control rounded-pill px-3 bg-light" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
        </div>
        <div class="mb-3">
          <label for="phone" class="form-label">Contact Phone Number</label>
          <input type="text" class="form-control rounded-pill px-3" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
        </div>
        <div class="mb-3">
          <label for="address" class="form-label">Delivery Address</label>
          <textarea class="form-control px-3" id="address" name="address" rows="3" style="border-radius: 12px;" required><?php echo htmlspecialchars($address); ?></textarea>
        </div>

        <h4 class="fw-bold my-4"><i class="fa-solid fa-credit-card text-primary me-2"></i>Payment Method</h4>
        
        <div class="form-check p-3 border rounded-3 border-2 border-primary mb-3" style="background-color: rgba(37, 99, 235, 0.04);" id="codContainer">
          <input class="form-check-input ms-1 me-2" type="radio" name="payment_method" id="cod" value="Cash on Delivery" checked onclick="togglePaymentForm(false)">
          <label class="form-check-label fw-bold text-dark" for="cod">
            Cash on Delivery (COD)
          </label>
          <span class="d-block text-secondary text-sm mt-1 ms-4">Pay in cash or UPI when your food is delivered to your doorstep.</span>
        </div>

        <div class="form-check p-3 border rounded-3 border-2 border-light mb-4" id="onlineContainer">
          <input class="form-check-input ms-1 me-2" type="radio" name="payment_method" id="online" value="Online Payment" onclick="togglePaymentForm(true)">
          <label class="form-check-label fw-bold text-dark" for="online">
            Online Payment (Card / UPI / NetBanking)
          </label>
          <span class="d-block text-secondary text-sm mt-1 ms-4">Pay securely using your Debit/Credit card, NetBanking, or UPI.</span>
          
          <div id="cardDetailsForm" class="mt-3 p-3 bg-white border rounded shadow-sm d-none">
            
            <div class="d-flex justify-content-center mb-3 p-1 bg-light rounded-pill">
              <button type="button" class="btn btn-sm btn-primary w-50 rounded-pill" id="btnPayCard" onclick="switchOnlineMode('card')">
                <i class="fa-solid fa-credit-card me-1"></i>Pay via Card
              </button>
              <button type="button" class="btn btn-sm btn-light text-secondary w-50 rounded-pill" id="btnPayUpi" onclick="switchOnlineMode('upi')">
                <i class="fa-solid fa-qrcode me-1"></i>Scan UPI QR
              </button>
            </div>

            <div id="cardPaymentPanel">
              <h6 class="fw-bold text-dark mb-3"><i class="fa-regular fa-credit-card me-2 text-primary"></i>Card Information (Mock Gateway)</h6>
              <div class="mb-3">
                <label class="form-label text-xs" style="font-size: 0.8rem;">Cardholder Name</label>
                <input type="text" class="form-control rounded-pill px-3" placeholder="Dev Patel" id="cardName">
              </div>
              <div class="mb-3">
                <label class="form-label text-xs" style="font-size: 0.8rem;">Card Number</label>
                <input type="text" class="form-control rounded-pill px-3" placeholder="1234 5678 9876 5432" id="cardNumber" maxlength="19">
              </div>
              <div class="row">
                <div class="col-6 mb-3">
                  <label class="form-label text-xs" style="font-size: 0.8rem;">Expiry Date</label>
                  <input type="text" class="form-control rounded-pill px-3" placeholder="MM/YY" id="cardExpiry" maxlength="5">
                </div>
                <div class="col-6 mb-3">
                  <label class="form-label text-xs" style="font-size: 0.8rem;">CVV</label>
                  <input type="password" class="form-control rounded-pill px-3" placeholder="123" id="cardCvv" maxlength="3">
                </div>
              </div>
            </div>

            <div id="upiPaymentPanel" class="d-none text-center p-3">
              <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-qrcode me-2 text-primary"></i>Scan QR Code to Pay</h6>
              <p class="text-secondary text-sm mb-3">Scan this code using any UPI app (GPay, PhonePe, Paytm, BHIM) to pay <strong>₹<?php echo number_format($total_amount, 2); ?></strong></p>
              
              <div class="d-inline-block p-3 bg-white border rounded-3 mb-3 shadow-sm">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=upi://pay?pa=nivaalafoods@upi%26pn=Nivaala%20Foods%26am=<?php echo $total_amount; ?>%26cu=INR" alt="UPI QR Code" class="img-fluid" style="width: 160px; height: 160px;">
              </div>
              
              <div class="text-muted text-xs" style="font-size: 0.75rem;">
                <i class="fa-solid fa-lock me-1 text-success"></i>Secure UPI Sandbox Environment
              </div>
            </div>

            <input type="hidden" name="online_payment_type" id="online_payment_type" value="card">
          </div>
        </div>

        <script>
        let currentOnlineMode = 'card';

        function switchOnlineMode(mode) {
            const btnCard = document.getElementById('btnPayCard');
            const btnUpi = document.getElementById('btnPayUpi');
            const cardPanel = document.getElementById('cardPaymentPanel');
            const upiPanel = document.getElementById('upiPaymentPanel');
            const onlineTypeInput = document.getElementById('online_payment_type');
            
            const cardName = document.getElementById('cardName');
            const cardNumber = document.getElementById('cardNumber');
            const cardExpiry = document.getElementById('cardExpiry');
            const cardCvv = document.getElementById('cardCvv');
            
            currentOnlineMode = mode;
            if (onlineTypeInput) {
                onlineTypeInput.value = mode;
            }
            
            if (mode === 'card') {
                btnCard.className = "btn btn-sm btn-primary w-50 rounded-pill";
                btnUpi.className = "btn btn-sm btn-light text-secondary w-50 rounded-pill";
                
                cardPanel.classList.remove('d-none');
                upiPanel.classList.add('d-none');
                
                if (document.getElementById('online').checked) {
                    cardName.setAttribute('required', 'required');
                    cardNumber.setAttribute('required', 'required');
                    cardExpiry.setAttribute('required', 'required');
                    cardCvv.setAttribute('required', 'required');
                }
            } else {
                btnUpi.className = "btn btn-sm btn-primary w-50 rounded-pill";
                btnCard.className = "btn btn-sm btn-light text-secondary w-50 rounded-pill";
                
                upiPanel.classList.remove('d-none');
                cardPanel.classList.add('d-none');
                
                cardName.removeAttribute('required');
                cardNumber.removeAttribute('required');
                cardExpiry.removeAttribute('required');
                cardCvv.removeAttribute('required');
            }
        }

        function togglePaymentForm(show) {
            const form = document.getElementById('cardDetailsForm');
            const codBox = document.getElementById('codContainer');
            const onlineBox = document.getElementById('onlineContainer');
            
            const cardName = document.getElementById('cardName');
            const cardNumber = document.getElementById('cardNumber');
            const cardExpiry = document.getElementById('cardExpiry');
            const cardCvv = document.getElementById('cardCvv');
            
            if (show) {
                form.classList.remove('d-none');
                onlineBox.classList.add('border-primary');
                onlineBox.style.backgroundColor = 'rgba(37, 99, 235, 0.04)';
                
                codBox.classList.remove('border-primary');
                codBox.classList.add('border-light');
                codBox.style.backgroundColor = 'transparent';
                
                if (currentOnlineMode === 'card') {
                    cardName.setAttribute('required', 'required');
                    cardNumber.setAttribute('required', 'required');
                    cardExpiry.setAttribute('required', 'required');
                    cardCvv.setAttribute('required', 'required');
                } else {
                    cardName.removeAttribute('required');
                    cardNumber.removeAttribute('required');
                    cardExpiry.removeAttribute('required');
                    cardCvv.removeAttribute('required');
                }
            } else {
                form.classList.add('d-none');
                codBox.classList.add('border-primary');
                codBox.style.backgroundColor = 'rgba(37, 99, 235, 0.04)';
                
                onlineBox.classList.remove('border-primary');
                onlineBox.classList.add('border-light');
                onlineBox.style.backgroundColor = 'transparent';
                
                cardName.removeAttribute('required');
                cardNumber.removeAttribute('required');
                cardExpiry.removeAttribute('required');
                cardCvv.removeAttribute('required');
            }
        }
        </script>

        <div class="d-grid mt-4">
          <button type="submit" name="place_order" class="btn btn-primary rounded-pill py-2.5 fw-bold text-uppercase">
            <i class="fa-solid fa-circle-check me-2"></i>Place Order
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Order Items</h4>
        <?php if (count($checkout_restaurants) === 1): ?>
          <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small">
            <i class="fa-solid fa-store me-1"></i><?php echo htmlspecialchars($checkout_restaurants[0]); ?>
          </span>
        <?php elseif (count($checkout_restaurants) > 1): ?>
          <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small">
            <i class="fa-solid fa-kitchen-set me-1"></i><?php echo count($checkout_restaurants); ?> Kitchens
          </span>
        <?php endif; ?>
      </div>
      <div class="list-group list-group-flush mb-4">
        <?php foreach ($cart_items as $item): ?>
          <div class="list-group-item px-0 d-flex justify-content-between align-items-center bg-transparent border-light py-2.5">
            <div>
              <h6 class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($item['name']); ?></h6>
              <span class="text-secondary text-sm">
                ₹<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?>
                <span class="text-muted d-block small"><i class="fa-solid fa-store me-1"></i><?php echo htmlspecialchars($item['restaurant_name']); ?></span>
              </span>
            </div>
            <span class="fw-bold text-dark">₹<?php echo number_format($item['subtotal'], 2); ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="d-flex justify-content-between mb-2">
        <span class="text-secondary">Subtotal</span>
        <span class="text-dark fw-medium">₹<?php echo number_format($subtotal_amount, 2); ?></span>
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span class="text-secondary">CGST (2.5%)</span>
        <span class="text-dark fw-medium">₹<?php echo number_format($cgst_amount, 2); ?></span>
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span class="text-secondary">SGST (2.5%)</span>
        <span class="text-dark fw-medium">₹<?php echo number_format($sgst_amount, 2); ?></span>
      </div>
      <div class="d-flex justify-content-between mb-3">
        <span class="text-secondary">Delivery Charges</span>
        <span class="text-success fw-medium">FREE</span>
      </div>
      <hr class="border-light">
      <div class="d-flex justify-content-between align-items-center">
        <span class="fs-5 fw-bold text-dark">Total Amount</span>
        <span class="fs-4 fw-extrabold text-primary">₹<?php echo number_format($total_amount, 2); ?></span>
      </div>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>
