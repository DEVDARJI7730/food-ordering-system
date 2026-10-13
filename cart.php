<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update') {
        $food_id = intval($_POST['food_id']);
        $qty = intval($_POST['quantity']);
        if ($food_id > 0) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$food_id]);
                $_SESSION['success'] = "Item removed from cart.";
            } else {
                $_SESSION['cart'][$food_id] = $qty;
                $_SESSION['success'] = "Cart updated successfully.";
            }
        }
    } 
    elseif ($action === 'remove') {
        $food_id = intval($_POST['food_id']);
        if ($food_id > 0) {
            unset($_SESSION['cart'][$food_id]);
            $_SESSION['success'] = "Item removed from cart.";
        }
    } 
    elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        $_SESSION['success'] = "Cart cleared successfully.";
    }

    header("Location: cart.php");
    exit();
}

$cart_items = [];
$subtotal_amount = 0.00;
$cart_restaurants = [];

if (!empty($_SESSION['cart'])) {
    
    $ids = array_keys($_SESSION['cart']);
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    try {
        $stmt = $conn->prepare("SELECT f.*, r.restaurant_name, r.city AS restaurant_city 
                                FROM food_items f 
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
            if (!in_array($r_name, $cart_restaurants)) {
                $cart_restaurants[] = $r_name;
            }

            $cart_items[] = [
                'id' => $id,
                'restaurant_id' => $item['restaurant_id'],
                'restaurant_name' => $r_name,
                'name' => $item['name'],
                'price' => $item['price'],
                'image_url' => $item['image_url'],
                'quantity' => $qty,
                'subtotal' => $item_subtotal
            ];
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error loading cart items: " . $e->getMessage();
    }
}

$cgst_amount = round($subtotal_amount * 0.025, 2);
$sgst_amount = round($subtotal_amount * 0.025, 2);
$delivery_fee = 0.00;
$total_amount = $subtotal_amount + $cgst_amount + $sgst_amount + $delivery_fee;

function getFoodImage($imageUrl) {
    if (!empty($imageUrl)) {
        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }
        if (file_exists("uploads/food/" . $imageUrl)) {
            return "uploads/food/" . $imageUrl;
        }
        if (file_exists("assets/images/" . $imageUrl)) {
            return "assets/images/" . $imageUrl;
        }
    }
    $fallbackImages = [
        'garlic_bread.jpg' => 'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?w=100&auto=format&fit=crop&q=60',
        'paneer_tikka.jpg' => 'https://www.cookwithmanali.com/wp-content/uploads/2015/07/Restaurant-Style-Recipe-Paneer-Tikka-500x500.jpg',
        'spring_rolls.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRKvEmfxsU_vmi8QU4hNjHYcx__RgRifQzz1xdl-HpSR1Dctxq-SDXZMfI&s=10',
        'corn_chaat.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSW8rC6uLTFZ2irN-Nw25I8Z0eBE9v9eTRxCBX6kLDAwZQXmPI9nKV305cp&s=10',
        'hara_bhara.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRKLHxK6H3QTSOLEsNUMti0pzBsU244YLKRwnulKOiDYJwbfoXdEGMtLsuH&s=10',
        'margherita_pizza.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSKMlvNvCk8QfJIthn0Ow_2S49Zq-2y-fpPHo62ZOdHyD1qFbYTa-ZxJ9Y&s=10',
        'paneer_butter.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQaRXVKiv719zaAwibMWDnutsRih3eu_0j-1FQEFNeB155NQ6juK0R5JIVt&s=10',
        'dal_makhani.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSN2E3WWCI1Z0XcfpKF8Tg5u-QjbwfYZjtA7r14qBnC26LgflUlmzrb3vYe&s=10',
        'veg_biryani.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWMav_J-QWJV5-PfSJ3Oe2dl0Ya1owsHwFM3rzGzD0ZCado6hmocwQ3kE&s=10',
        'chole_bhature.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS83okijSL6VQD0mH6STUjHDa4XGDsPu6heQR2Fv52c_S15S3Wz66kj2Kg&s=10',
        'palak_paneer.jpg' => 'https://www.indianveggiedelight.com/wp-content/uploads/2017/10/palak-paneer-recipe-featured.jpg',
        'lava_cake.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRg5JvKa8IyQpXjhoDWjuoasDGFWpMY995Fub87OUrXRSnHuayHVNbVR6tC&s=10',
        'brownie.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6Aq1q9qx4o0FSbKXEdEpqSk-qKFuJJu98gyDJR6wrKi64hlXQFz3oAbPf&s=10',
        'cheesecake.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT8v6NQiFxidRbHclYdtq2eMLHHHl7ZO3qsbWRR9o7pgLIkwhkkTbD0W4k_&s=10',
        'gulab_jamun.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQnkiEZFfbgDaqgpM5YleUxN61m9duQN029zCaALb65wdSKe_i_7U1JhIs&s=10',
        'iced_tea.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQMhAnKUutMbbTgY4nfJR12K6LyUdxIJ6ZqqY3aZNFUDuLQ16lak5jd9qr2&s=10',
        'chocolate_shake.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTIHIz91mveCtEXSXDp9TWHfr2iKRTwVvzxkmnOp-9eqj75KAjabwyDzjA&s=10',
        'water.jpg' => 'https://images.unsplash.com/photo-1523362628745-0c100150b504?w=100&auto=format&fit=crop&q=60',
        'mango_lassi.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNOqLCmgDaeWBclXTFzyobgtPHB7-gZ1HJin0D_keW2wj_ClyJR8O0__zp&s=10',
        'cold_coffee.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRX7Def1B17wbzjck_PrHpzCDSZsOTGuiIrU56QxId-MITBcmzF8uKaTCE&s=10',
        'veg_burger.jpg' => 'https://i.pinimg.com/564x/82/2f/0f/822f0fbd10e7f3d03405a7daaec81c7c.jpg',
        'loaded_fries.jpg' => 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=100&auto=format&fit=crop&q=60',
        'cheese_garlic_bread.jpg' => 'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?w=100&auto=format&fit=crop&q=60',
        'veg_momos.jpg' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=100&auto=format&fit=crop&q=60'
    ];
    
    if (isset($fallbackImages[$imageUrl])) {
        return $fallbackImages[$imageUrl];
    }
    return 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=100&auto=format&fit=crop&q=60';
}

include_once 'includes/header.php';
?>

<div class="row my-4">
  <div class="col-12">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
      </ol>
    </nav>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
      <h2 class="fw-bold mb-0">Your Shopping Cart</h2>
      <?php if (count($cart_restaurants) === 1): ?>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fs-6">
          <i class="fa-solid fa-store me-2"></i>Ordering from: <strong><?php echo htmlspecialchars($cart_restaurants[0]); ?></strong>
        </span>
      <?php elseif (count($cart_restaurants) > 1): ?>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fs-6" title="<?php echo htmlspecialchars(implode(', ', $cart_restaurants)); ?>">
          <i class="fa-solid fa-kitchen-set me-2"></i>Ordering from: <strong><?php echo count($cart_restaurants); ?> Different Restaurants</strong>
        </span>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($cart_items)): ?>
    <div class="col-lg-8 mb-4">
      <div class="card border-0 shadow-sm-custom p-3" style="border-radius: 16px;">
        <div class="table-responsive">
          <table class="table cart-table table-borderless">
            <thead>
              <tr>
                <th scope="col" style="width: 10%;">Item</th>
                <th scope="col" style="width: 35%;">Name</th>
                <th scope="col" style="width: 15%;">Price</th>
                <th scope="col" style="width: 20%;">Quantity</th>
                <th scope="col" style="width: 15%;">Total</th>
                <th scope="col" style="width: 5%;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cart_items as $item): ?>
                <tr>
                  <td>
                    <img src="<?php echo getFoodImage($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid rounded" style="width: 60px; height: 60px; object-fit: cover;">
                  </td>
                  <td>
                    <h6 class="fw-semibold text-dark mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill small px-2 py-0.5" style="font-size: 0.75rem;">
                      <i class="fa-solid fa-store me-1"></i><?php echo htmlspecialchars($item['restaurant_name']); ?>
                    </span>
                  </td>
                  <td>₹<?php echo number_format($item['price'], 2); ?></td>
                  <td>
                    
                    <form action="cart.php" method="POST" class="d-flex align-items-center gap-1">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                      <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="20" class="form-control form-control-sm text-center px-1 rounded" style="width: 60px;">
                      <button type="submit" class="btn btn-sm btn-outline-primary" title="Update Quantity">
                        <i class="fa-solid fa-rotate"></i>
                      </button>
                    </form>
                  </td>
                  <td class="fw-bold">₹<?php echo number_format($item['subtotal'], 2); ?></td>
                  <td>
                    
                    <form action="cart.php" method="POST">
                      <input type="hidden" name="action" value="remove">
                      <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                      <button type="submit" class="btn btn-sm text-danger" title="Remove Item">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
          
          <form action="cart.php" method="POST">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-outline-danger rounded-pill px-4 confirm-delete" data-message="Are you sure you want to clear the entire shopping cart?">
              <i class="fa-solid fa-trash-arrow-up me-2"></i>Clear Cart
            </button>
          </form>
          <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-2"></i>Continue Ordering
          </a>
        </div>
      </div>
    </div>

    
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
        <h4 class="fw-bold mb-4">Summary</h4>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
          <span class="fs-5 fw-bold text-dark">Grand Total</span>
          <span class="fs-4 fw-extrabold text-primary">₹<?php echo number_format($total_amount, 2); ?></span>
        </div>

        <div class="d-grid">
          <a href="checkout.php" class="btn btn-primary rounded-pill py-2.5 fw-bold text-uppercase">
            Proceed to Checkout<i class="fa-solid fa-arrow-right ms-2"></i>
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    
    <div class="col-12 text-center my-5 py-5 bg-white rounded-4 border border-light shadow-sm-custom">
      <i class="fa-solid fa-cart-shopping text-muted display-1 mb-4"></i>
      <h3 class="fw-bold text-dark">Your Cart is Empty</h3>
      <p class="text-secondary col-md-6 mx-auto mb-4">It looks like you haven't added any food items to your cart yet. Explore our menu and pick something delicious!</p>
      <a href="index.php" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold"><i class="fa-solid fa-bowl-food me-2"></i>Browse Menu</a>
    </div>
  <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
