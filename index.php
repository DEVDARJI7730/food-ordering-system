<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $food_id = intval($_POST['food_id']);
    $qty = intval($_POST['quantity'] ?? 1);

    if ($food_id > 0 && $qty > 0) {
        $stmt = $conn->prepare("SELECT f.name, f.restaurant_id, r.restaurant_name 
                                FROM food_items f 
                                JOIN restaurants r ON f.restaurant_id = r.id 
                                WHERE f.id = ? AND f.is_available = 1 AND r.status = 'Approved'");
        $stmt->execute([$food_id]);
        $item = $stmt->fetch();

        if ($item) {
            if (isset($_SESSION['cart'][$food_id])) {
                $_SESSION['cart'][$food_id] += $qty;
            } else {
                $_SESSION['cart'][$food_id] = $qty;
            }

            $_SESSION['success'] = '"' . htmlspecialchars($item['name']) . '" from ' . htmlspecialchars($item['restaurant_name']) . ' added to cart!';
        } else {
            $_SESSION['error'] = "Item not found or unavailable.";
        }
    }
    
    $redirect_url = $_SERVER['PHP_SELF'] . '?';
    if (isset($_GET['restaurant_id'])) $redirect_url .= 'restaurant_id=' . intval($_GET['restaurant_id']) . '&';
    if (isset($_GET['catagory'])) $redirect_url .= 'catagory=' . intval($_GET['catagory']) . '&';
    if (isset($_GET['search'])) $redirect_url .= 'search=' . urlencode($_GET['search']) . '&';
    header("Location: " . rtrim($redirect_url, '?&'));
    exit();
}

$restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;
$restaurant_info = null;

if ($restaurant_id > 0) {
    $r_stmt = $conn->prepare("
        SELECT r.*
        FROM restaurants r 
        WHERE r.id = ? AND r.status = 'Approved'
    ");
    $r_stmt->execute([$restaurant_id]);
    $restaurant_info = $r_stmt->fetch();
    
    if (!$restaurant_info) {
        $_SESSION['error'] = "The selected restaurant is either unavailable or pending license verification.";
        header("Location: restaurants.php");
        exit();
    }
}

try {
    $catagories = $conn->query("SELECT * FROM catagories ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$catagory_id = isset($_GET['catagory']) ? intval($_GET['catagory']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Strict Synonym / Typo Dictionary for Exact & Typo-Tolerant Matching
$synonyms = [
    // Paneer / Cottage Cheese & typos
    'panner' => ['paneer', 'cottage cheese'],
    'paner' => ['paneer', 'cottage cheese'],
    'paneer' => ['paneer', 'cottage cheese'],
    'cottage cheese' => ['paneer', 'cottage cheese'],
    
    // Cheese & typos
    'chese' => ['cheese'],
    'cheese' => ['cheese'],
    
    // Mushroom & typos
    'mashroom' => ['mushroom'],
    'musroom' => ['mushroom'],
    'mushroom' => ['mushroom', 'mushrooms'],
    'mushrooms' => ['mushroom', 'mushrooms'],
    
    // Tacos & Mexican
    'taco' => ['taco', 'tacos'],
    'tacos' => ['taco', 'tacos'],
    'takos' => ['taco', 'tacos'],
    'tako' => ['taco', 'tacos'],
    'quesadilla' => ['quesadilla', 'quesadillas'],
    'quesadillas' => ['quesadilla', 'quesadillas'],
    
    // Khichdi
    'khichdi' => ['khichdi', 'khichri'],
    'khichri' => ['khichdi', 'khichri'],
    'khichadi' => ['khichdi'],
    
    // Dimsums / Dumplings / Baos
    'dimsum' => ['dimsum', 'dimsums', 'dim sum'],
    'dimsums' => ['dimsum', 'dimsums', 'dim sum'],
    'dim sum' => ['dimsum', 'dimsums', 'dim sum'],
    'dumpling' => ['dumpling', 'dumplings'],
    'dumplings' => ['dumpling', 'dumplings'],
    'bao' => ['bao', 'baos'],
    'baos' => ['bao', 'baos'],
    
    // Coffee & Drinks
    'coffe' => ['coffee'],
    'coffee' => ['coffee'],
    'latte' => ['latte'],
    'espresso' => ['espresso'],
    'cappuccino' => ['cappuccino'],
    'matcha' => ['matcha'],
    
    // Salad
    'salad' => ['salad', 'salads'],
    'salads' => ['salad', 'salads'],
    
    // Manchurian
    'manchurian' => ['manchurian', 'mancurian'],
    'mancurian' => ['manchurian'],
    
    // Chilli / Starters
    'chilli' => ['chilli', 'chili', 'chilly'],
    'chili' => ['chilli', 'chili', 'chilly'],
    'chilly' => ['chilli', 'chili', 'chilly'],
    
    // Pasta & Italian
    'pasta' => ['pasta'],
    'ravioli' => ['ravioli'],
    'spaghetti' => ['spaghetti'],
    'gnocchi' => ['gnocchi'],
    'lasagna' => ['lasagna', 'lasagne'],
    
    // Sushi
    'sushi' => ['sushi', 'maki'],
    
    // Burgers & Sandwiches
    'burger' => ['burger', 'burgers'],
    'burgers' => ['burger', 'burgers'],
    'sandwich' => ['sandwich', 'sandwiches'],
    'sandwiches' => ['sandwich', 'sandwiches'],
    'toastie' => ['toastie', 'toasties'],
    
    // Desserts
    'cake' => ['cake', 'cheesecake'],
    'cheesecake' => ['cheesecake', 'cheese cake'],
    'brownie' => ['brownie', 'brownies'],
    'tiramisu' => ['tiramisu'],
    'fondue' => ['fondue'],
    'dessert' => ['dessert', 'desserts', 'cake', 'cheesecake', 'brownie', 'tiramisu'],
    'desserts' => ['dessert', 'desserts', 'cake', 'cheesecake', 'brownie', 'tiramisu'],
    
    // Pizza & typos
    'piza' => ['pizza', 'pizzas'],
    'pizaa' => ['pizza', 'pizzas'],
    'pizza' => ['pizza', 'pizzas'],
    'pizzas' => ['pizza', 'pizzas'],
    
    // Soups
    'soup' => ['soup', 'soups'],
    'soups' => ['soup', 'soups'],
    
    // Combos & Meals
    'combo' => ['combo', 'combos', 'meal'],
    'combos' => ['combo', 'combos', 'meal'],
    'meal' => ['meal', 'combo'],

    // Sides
    'fries' => ['fries'],
    'wedges' => ['wedges', 'wedge'],
    'nachos' => ['nachos', 'nacho'],
    'garlic bread' => ['garlic bread', 'garlic / jain bread', 'bread'],
    'bread' => ['garlic bread', 'bread']
];

$sort_by = $_GET['sort'] ?? 'relevance';
$filter_time = $_GET['time'] ?? '';
$filter_price = $_GET['price_range'] ?? '';
$filter_trust = $_GET['trust'] ?? '';

$sql = "SELECT f.*, c.name AS catagory_name, r.restaurant_name, r.city AS restaurant_city
        FROM food_items f 
        JOIN catagories c ON f.catagory_id = c.id 
        JOIN restaurants r ON f.restaurant_id = r.id 
        WHERE f.is_available = 1 AND r.status = 'Approved'";
$params = [];

if ($restaurant_id > 0) {
    $sql .= " AND f.restaurant_id = ?";
    $params[] = $restaurant_id;
}

if ($catagory_id > 0) {
    $sql .= " AND f.catagory_id = ?";
    $params[] = $catagory_id;
}

if ($filter_price === 'under_200') {
    $sql .= " AND f.price < 200";
} elseif ($filter_price === '200_350') {
    $sql .= " AND f.price >= 200 AND f.price <= 350";
} elseif ($filter_price === 'above_350') {
    $sql .= " AND f.price > 350";
}

if (!empty($search)) {
    $search_terms = [$search];
    $s_lower = strtolower($search);
    foreach ($synonyms as $k => $alts) {
        if (str_contains($s_lower, $k)) {
            foreach ($alts as $a) {
                if (!in_array($a, $search_terms)) $search_terms[] = $a;
            }
        }
    }

    $where_search = [];
    foreach ($search_terms as $term) {
        $where_search[] = "(f.name LIKE ? OR f.description LIKE ? OR c.name LIKE ? OR r.restaurant_name LIKE ?)";
        $t_param = '%' . $term . '%';
        $params[] = $t_param;
        $params[] = $t_param;
        $params[] = $t_param;
        $params[] = $t_param;
    }
    $sql .= " AND (" . implode(" OR ", $where_search) . ")";
}

if ($sort_by === 'price_asc') {
    $sql .= " ORDER BY f.price ASC";
} elseif ($sort_by === 'price_desc') {
    $sql .= " ORDER BY f.price DESC";
} elseif ($sort_by === 'name_asc') {
    $sql .= " ORDER BY f.name ASC";
} else {
    $sql .= " ORDER BY f.catagory_id ASC, f.name ASC";
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $food_items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch Recommended & Related dishes if search or filter applied
$recommended_items = [];
if (!empty($search) || $catagory_id > 0) {
    try {
        $existing_ids = array_column($food_items, 'id');
        $not_in = !empty($existing_ids) ? 'AND f.id NOT IN (' . implode(',', array_map('intval', $existing_ids)) . ')' : '';
        
        $rec_sql = "
            SELECT f.*, c.name AS catagory_name, r.restaurant_name, r.city AS restaurant_city 
            FROM food_items f 
            JOIN catagories c ON f.catagory_id = c.id 
            JOIN restaurants r ON f.restaurant_id = r.id 
            WHERE f.is_available = 1 AND r.status = 'Approved' $not_in
            ORDER BY RAND()
            LIMIT 4
        ";
        $rec_stmt = $conn->query($rec_sql);
        $recommended_items = $rec_stmt->fetchAll();
    } catch (PDOException $e) {}
}

// Fetch Matching Restaurants if searching
$matching_restaurants = [];
if (!empty($search)) {
    try {
        $rest_sql = "
            SELECT r.*, 
                   (SELECT COUNT(*) FROM food_items f WHERE f.restaurant_id = r.id AND f.is_available = 1) AS total_dishes
            FROM restaurants r
            WHERE r.status = 'Approved' AND (r.restaurant_name LIKE ? OR r.description LIKE ? OR r.city LIKE ?)
            ORDER BY 
                CASE 
                    WHEN r.restaurant_name LIKE ? THEN 1
                    WHEN r.restaurant_name LIKE ? THEN 2
                    ELSE 3
                END,
                r.restaurant_name ASC
            LIMIT 4
        ";
        $exact_p = $search . '%';
        $like_p = '%' . $search . '%';
        $rest_stmt = $conn->prepare($rest_sql);
        $rest_stmt->execute([$like_p, $like_p, $like_p, $exact_p, $like_p]);
        $matching_restaurants = $rest_stmt->fetchAll();
    } catch (PDOException $e) {}
}

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
        'garlic_bread.jpg' => 'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?w=500&auto=format&fit=crop&q=60',
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
        'water.jpg' => 'https://images.unsplash.com/photo-1523362628745-0c100150b504?w=500&auto=format&fit=crop&q=60',
        'mango_lassi.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNOqLCmgDaeWBclXTFzyobgtPHB7-gZ1HJin0D_keW2wj_ClyJR8O0__zp&s=10',
        'cold_coffee.jpg' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRX7Def1B17wbzjck_PrHpzCDSZsOTGuiIrU56QxId-MITBcmzF8uKaTCE&s=10',
        'veg_burger.jpg' => 'https://i.pinimg.com/564x/82/2f/0f/822f0fbd10e7f3d03405a7daaec81c7c.jpg',
        'loaded_fries.jpg' => 'https://images.unsplash.com/photo-1585109649139-366815a0d713?w=500&auto=format&fit=crop&q=60',
        'cheese_garlic_bread.jpg' => 'https://images.unsplash.com/photo-1619535860434-ba1d8fa12536?w=500&auto=format&fit=crop&q=60',
        'veg_momos.jpg' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=60'
    ];
    
    if (isset($fallbackImages[$imageUrl])) {
        return $fallbackImages[$imageUrl];
    }
    return 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=500&auto=format&fit=crop&q=60';
}

include_once 'includes/header.php';
?>

<?php if ($restaurant_info): ?>
  <!-- Specific Restaurant Banner -->
  <div class="card border-0 shadow-sm-custom mb-4 p-4 p-md-5" style="border-radius: 20px; background: linear-gradient(135deg, #ffffff, #f8fafc);">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
      <div class="d-flex align-items-center gap-3">
        <?php if (!empty($restaurant_info['logo']) && file_exists(__DIR__ . '/uploads/logos/' . $restaurant_info['logo'])): ?>
          <img src="uploads/logos/<?php echo htmlspecialchars($restaurant_info['logo']); ?>" alt="Logo" class="rounded-circle shadow-sm object-fit-cover bg-white p-1" style="width: 80px; height: 80px;">
        <?php else: ?>
          <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2 shadow-sm" style="width: 80px; height: 80px; min-width: 80px;">
            <?php echo strtoupper(substr($restaurant_info['restaurant_name'], 0, 2)); ?>
          </div>
        <?php endif; ?>
        <div>
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h2 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($restaurant_info['restaurant_name']); ?></h2>
            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 small border border-success-subtle">
              <i class="fa-solid fa-certificate me-1"></i>Verified License
            </span>
          </div>
          <p class="text-secondary mb-1"><i class="fa-solid fa-location-dot text-danger me-1"></i><?php echo htmlspecialchars($restaurant_info['address']) . ', ' . htmlspecialchars($restaurant_info['city']) . ', ' . htmlspecialchars($restaurant_info['state']); ?></p>
          <?php if (!empty($restaurant_info['description'])): ?>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($restaurant_info['description']); ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="text-md-end">
        <a href="restaurants.php" class="btn btn-outline-secondary rounded-pill px-3">
          <i class="fa-solid fa-arrow-left me-1"></i>All Restaurants
        </a>
      </div>
    </div>
  </div>
<?php else: ?>
  <!-- General Hero Section -->
  <div class="row align-items-center mb-5 bg-white p-4 p-md-5 rounded-4 shadow-sm-custom border border-light">
    <div class="col-lg-6">
      <span class="badge text-uppercase fw-bold px-3 py-2 mb-3 shadow-sm-custom border border-light" style="background-color: rgba(124, 58, 237, 0.1); color: var(--ai-accent);">
        <i class="fa-solid fa-bolt me-1"></i> Fast & Certified Delivery
      </span>
      <h1 class="display-4 fw-extrabold text-dark mb-3">Delicious Food, Delivered to <span class="text-primary">Your Door</span></h1>
      <p class="lead text-secondary mb-4">Explore mouthwatering dishes from top verified restaurant kitchens in your neighborhood.</p>
      
    <form action="index.php" method="GET" class="position-relative" id="searchForm">
      <?php if ($catagory_id > 0): ?>
        <input type="hidden" name="catagory" value="<?php echo $catagory_id; ?>">
      <?php endif; ?>
      <?php if ($restaurant_id > 0): ?>
        <input type="hidden" name="restaurant_id" value="<?php echo $restaurant_id; ?>">
      <?php endif; ?>
      <div class="input-group shadow-sm-custom rounded-pill overflow-hidden bg-light border">
        <span class="input-group-text bg-transparent border-0 ps-3"><i class="fa-solid fa-magnifying-glass text-primary"></i></span>
        <input type="text" class="form-control bg-transparent border-0 py-3" id="mainSearchInput" name="search" placeholder="Search restaurants or dishes (e.g. Sam's Pizza, paneer, tacos, salad)..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
        <?php if (!empty($search)): ?>
          <a href="index.php<?php echo $restaurant_id > 0 ? '?restaurant_id=' . $restaurant_id : ''; ?>" class="btn btn-light border-0 d-flex align-items-center text-muted" title="Clear Search">
            <i class="fa-solid fa-xmark"></i>
          </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary px-4 fw-bold">Search</button>
      </div>

      <!-- Live Search Suggestions Dropdown -->
      <div id="liveSuggestionsBox" class="dropdown-menu w-100 shadow-lg border-0 mt-2 p-2 rounded-4 d-none" style="position: absolute; top: 100%; left: 0; z-index: 1050; max-height: 380px; overflow-y: auto;"></div>
    </form>

  </div>
  <div class="col-lg-6 d-none d-lg-block text-center">
    <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=600&auto=format&fit=crop&q=80" alt="Delicious Food" class="img-fluid rounded-4 shadow-md-custom" style="height: 320px; width: 100%; object-fit: cover;">
  </div>
</div>
<?php endif; ?>

<!-- Active Search / Filter Banner -->
<?php 
$has_active_filters = (!empty($search) || !empty($filter_price) || !empty($filter_time) || (!empty($sort_by) && $sort_by !== 'relevance') || !empty($filter_trust));
?>

<?php if ($has_active_filters): ?>
  <div class="alert bg-white border shadow-sm-custom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 p-3 mb-4 rounded-4">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <span class="text-dark fw-bold small"><i class="fa-solid fa-sliders text-primary me-1"></i>Active Filters:</span>
      
      <?php if (!empty($search)): ?>
        <span class="badge bg-primary fs-6 px-2.5 py-1.5 rounded-pill">
          Keyword: <?php echo htmlspecialchars($search); ?>
        </span>
      <?php endif; ?>

      <?php if (!empty($filter_price)): ?>
        <span class="badge bg-dark text-white px-2.5 py-1.5 rounded-pill small">
          Price: <?php echo $filter_price === 'under_200' ? 'Under ₹200' : ($filter_price === '200_350' ? '₹200 - ₹350' : 'Above ₹350'); ?>
        </span>
      <?php endif; ?>

      <?php if (!empty($sort_by) && $sort_by !== 'relevance'): ?>
        <span class="badge bg-secondary text-white px-2.5 py-1.5 rounded-pill small">
          Sort: <?php 
            if ($sort_by === 'price_asc') echo 'Price: Low to High';
            elseif ($sort_by === 'price_desc') echo 'Price: High to Low';
            elseif ($sort_by === 'name_asc') echo 'Name: A to Z';
          ?>
        </span>
      <?php endif; ?>

      <?php if (!empty($filter_time)): ?>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small">
          ⚡ <?php echo $filter_time === 'fast' ? 'Near & Fast' : 'Schedule'; ?>
        </span>
      <?php endif; ?>

      <?php if (!empty($filter_trust)): ?>
        <span class="badge bg-success text-white px-2.5 py-1.5 rounded-pill small">
          🛡️ <?php echo $filter_trust === 'verified' ? 'Verified Kitchens' : '100% Pure Veg'; ?>
        </span>
      <?php endif; ?>

      <span class="text-muted small ms-1">(<?php echo count($food_items); ?> dishes found)</span>
    </div>

    <a href="index.php<?php echo $restaurant_id > 0 ? '?restaurant_id=' . $restaurant_id : ''; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 align-self-start align-self-sm-auto fw-semibold">
      <i class="fa-solid fa-rotate-left me-1"></i>Reset All
    </a>
  </div>
<?php endif; ?>

<!-- Matching Restaurants Section when Searching -->
<?php if (!empty($matching_restaurants) && !$restaurant_info): ?>
  <div class="card border-0 shadow-sm-custom p-4 mb-4 rounded-4" style="background: linear-gradient(135deg, #ffffff, #f8fafc); border-left: 4px solid var(--bs-primary) !important;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div>
        <h5 class="fw-bold mb-0 text-dark">
          <i class="fa-solid fa-store text-primary me-2"></i>Matching Restaurants (<?php echo count($matching_restaurants); ?>)
        </h5>
        <span class="text-muted small">Certified restaurant kitchens matching "<strong><?php echo htmlspecialchars($search); ?></strong>"</span>
      </div>
      <a href="restaurants.php?search=<?php echo urlencode($search); ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
        View in All Restaurants <i class="fa-solid fa-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-3">
      <?php foreach ($matching_restaurants as $m_rest): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 border rounded-4 p-3 shadow-xs bg-white hover-shadow transition">
            <div class="d-flex align-items-center gap-3">
              <?php if (!empty($m_rest['logo']) && file_exists(__DIR__ . '/uploads/logos/' . $m_rest['logo'])): ?>
                <img src="uploads/logos/<?php echo htmlspecialchars($m_rest['logo']); ?>" alt="Logo" class="rounded-circle shadow-sm object-fit-cover bg-light p-1" style="width: 56px; height: 56px; min-width: 56px;">
              <?php else: ?>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 56px; height: 56px; min-width: 56px;">
                  <?php echo strtoupper(substr($m_rest['restaurant_name'], 0, 2)); ?>
                </div>
              <?php endif; ?>
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                  <h6 class="fw-bold text-dark mb-0 text-truncate" title="<?php echo htmlspecialchars($m_rest['restaurant_name']); ?>">
                    <?php echo htmlspecialchars($m_rest['restaurant_name']); ?>
                  </h6>
                </div>
                <div class="text-muted small mb-2 text-truncate" style="font-size: 0.75rem;">
                  <i class="fa-solid fa-location-dot text-danger me-1"></i><?php echo htmlspecialchars($m_rest['city']); ?>
                  <span class="mx-1">•</span> <?php echo $m_rest['total_dishes']; ?> Dishes
                </div>
                <a href="index.php?restaurant_id=<?php echo $m_rest['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3 py-1 w-100 fw-semibold text-xs" style="font-size: 0.8rem;">
                  <i class="fa-solid fa-utensils me-1"></i>View Full Menu
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Category Filter Pills -->
<div class="row mb-4 align-items-center">
  <div class="col-12">
    <h4 class="fw-bold mb-3"><?php echo $restaurant_info ? 'Menu Categories' : 'Browse by Category'; ?></h4>
  </div>

  <div class="col-12">
    <div class="d-flex flex-wrap gap-2 py-1">
      <a href="index.php<?php 
          $q = [];
          if ($restaurant_id > 0) $q[] = 'restaurant_id=' . $restaurant_id;
          if (!empty($search)) $q[] = 'search=' . urlencode($search);
          echo !empty($q) ? '?' . implode('&', $q) : '';
        ?>" class="btn category-btn <?php echo $catagory_id === 0 ? 'active' : ''; ?>">
        All Dishes
      </a>
      <?php foreach ($catagories as $cat): ?>
        <a href="index.php?catagory=<?php echo $cat['id']; ?><?php 
            if ($restaurant_id > 0) echo '&restaurant_id=' . $restaurant_id;
            if (!empty($search)) echo '&search=' . urlencode($search);
          ?>" 
           class="btn category-btn <?php echo $catagory_id === $cat['id'] ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($cat['name']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Food Items Grid -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-5" id="foodItemsContainer">
  <?php if (count($food_items) > 0): ?>
    <?php foreach ($food_items as $item): ?>
      <div class="col food-item-container">
        <div class="card h-100 food-card shadow-sm-custom">
          
          <div class="food-card-img-container position-relative">
            <img src="<?php echo getFoodImage($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="food-card-img">
            <?php if (!$restaurant_info): ?>
              <span class="badge bg-dark bg-opacity-75 text-white position-absolute bottom-0 start-0 m-2 px-2.5 py-1 rounded-pill small" style="font-size: 0.72rem;">
                <i class="fa-solid fa-store me-1 text-primary"></i><?php echo htmlspecialchars($item['restaurant_name']); ?>
              </span>
            <?php endif; ?>
          </div>
          
          <div class="food-card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h5 class="food-card-title text-dark mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-light text-secondary border border-light py-1 px-2.5 rounded-pill text-xs fw-semibold" style="font-size:0.75rem;">
                <?php echo htmlspecialchars($item['catagory_name']); ?>
              </span>
            </div>
            <p class="food-card-desc mb-3"><?php echo htmlspecialchars($item['description']); ?></p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <span class="fs-5 fw-bold text-dark">₹<?php echo number_format($item['price'], 2); ?></span>
              
              <form action="index.php<?php 
                  $q = [];
                  if ($restaurant_id > 0) $q[] = 'restaurant_id=' . $restaurant_id;
                  if ($catagory_id > 0) $q[] = 'catagory=' . $catagory_id;
                  if (!empty($search)) $q[] = 'search=' . urlencode($search);
                  echo !empty($q) ? '?' . implode('&', $q) : '';
                ?>" method="POST" class="d-flex align-items-center">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm-custom">
                  <i class="fa-solid fa-plus me-1"></i> Add
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="col-12 text-center my-5">
      <i class="fa-solid fa-bowl-food text-warning fs-1 mb-3"></i>
      <h4 class="fw-bold">No Exact Dishes Found for "<?php echo htmlspecialchars($search); ?>"</h4>
      <p class="text-secondary">Explore our related chef recommendations below or try searching another keyword like <strong>Paneer</strong>, <strong>Cheese</strong>, <strong>Tacos</strong>, or <strong>Khichdi</strong>.</p>
      <a href="index.php<?php echo $restaurant_id > 0 ? '?restaurant_id=' . $restaurant_id : ''; ?>" class="btn btn-outline-primary rounded-pill px-4 mt-2">View Full Menu</a>
    </div>
  <?php endif; ?>
</div>

<!-- Recommended & Related Dishes Section -->
<?php if (!empty($recommended_items)): ?>
  <div class="card border-0 shadow-sm-custom p-4 mb-5" style="border-radius: 20px; background: linear-gradient(135deg, #f8fafc, #f1f5f9);">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-wand-magic-sparkles text-primary fs-4"></i>
        <div>
          <h4 class="fw-bold mb-0 text-dark">You Might Also Like</h4>
          <span class="text-muted small">Handpicked gourmet dishes and kitchen favorites</span>
        </div>
      </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
      <?php foreach ($recommended_items as $rec): ?>
        <div class="col">
          <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white hover-shadow transition">
            <div class="position-relative" style="height: 140px; overflow: hidden;">
              <img src="<?php echo getFoodImage($rec['image_url']); ?>" alt="<?php echo htmlspecialchars($rec['name']); ?>" class="w-100 h-100 object-fit-cover">
              <span class="badge bg-dark bg-opacity-75 text-white position-absolute bottom-0 start-0 m-2 px-2 py-1 rounded-pill small" style="font-size: 0.7rem;">
                <i class="fa-solid fa-store me-1 text-primary"></i><?php echo htmlspecialchars($rec['restaurant_name']); ?>
              </span>
            </div>
            <div class="p-3 d-flex flex-column flex-grow-1">
              <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?php echo htmlspecialchars($rec['name']); ?>"><?php echo htmlspecialchars($rec['name']); ?></h6>
              <span class="badge bg-light text-secondary border rounded-pill mb-2 align-self-start" style="font-size: 0.7rem;">
                <?php echo htmlspecialchars($rec['catagory_name']); ?>
              </span>
              <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                <span class="fw-bold text-dark">₹<?php echo number_format($rec['price'], 2); ?></span>
                <form action="index.php" method="POST">
                  <input type="hidden" name="action" value="add_to_cart">
                  <input type="hidden" name="food_id" value="<?php echo $rec['id']; ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button type="submit" class="btn btn-sm btn-primary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Add to Cart">
                    <i class="fa-solid fa-plus" style="font-size: 11px;"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Live Instant Search JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('mainSearchInput');
  const suggestionsBox = document.getElementById('liveSuggestionsBox');
  let debounceTimer;

  if (searchInput && suggestionsBox) {
    searchInput.addEventListener('input', function() {
      const q = this.value.trim();
      clearTimeout(debounceTimer);

      if (q.length < 2) {
        suggestionsBox.classList.add('d-none');
        suggestionsBox.innerHTML = '';
        return;
      }

      debounceTimer = setTimeout(() => {
        fetch('api/search-suggestions.php?q=' + encodeURIComponent(q))
          .then(res => res.json())
          .then(data => {
            let hasRestaurants = data.restaurants && data.restaurants.length > 0;
            let hasDishes = data.results && data.results.length > 0;

            if (hasRestaurants || hasDishes) {
              let html = '';

              // 1. Render Matching Restaurants
              if (hasRestaurants) {
                html += '<div class="px-3 py-1 text-muted small fw-bold text-uppercase border-bottom mb-2"><i class="fa-solid fa-store me-1 text-primary"></i>Matching Restaurants</div>';
                data.restaurants.forEach(rest => {
                  const initial = rest.restaurant_name.substring(0, 2).toUpperCase();
                  html += `
                    <a href="index.php?restaurant_id=${rest.id}" class="dropdown-item d-flex align-items-center justify-content-between p-2 rounded-3 text-wrap mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold small shadow-xs" style="width: 34px; height: 34px; min-width: 34px;">
                          ${escapeHtml(initial)}
                        </div>
                        <div>
                          <div class="fw-bold text-dark text-sm mb-0">${escapeHtml(rest.restaurant_name)}</div>
                          <div class="text-muted small" style="font-size: 11px;">
                            <span class="text-danger"><i class="fa-solid fa-location-dot me-0.5"></i>${escapeHtml(rest.city || 'City')}</span>
                            <span class="ms-1.5 badge bg-light text-secondary border px-1.5 py-0.5 rounded-pill">${rest.total_dishes || 0} dishes</span>
                          </div>
                        </div>
                      </div>
                      <span class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5" style="font-size: 11px;">
                        Menu <i class="fa-solid fa-arrow-right ms-0.5"></i>
                      </span>
                    </a>
                  `;
                });
              }

              // 2. Render Matching Dishes
              if (hasDishes) {
                if (hasRestaurants) {
                  html += '<div class="px-3 py-1 text-muted small fw-bold text-uppercase border-bottom my-2"><i class="fa-solid fa-utensils me-1 text-primary"></i>Matching Food Items</div>';
                } else {
                  html += '<div class="px-3 py-1 text-muted small fw-bold text-uppercase border-bottom mb-2"><i class="fa-solid fa-utensils me-1 text-primary"></i>Matching Food Items</div>';
                }
                data.results.forEach(item => {
                  html += `
                    <a href="index.php?search=${encodeURIComponent(item.name)}" class="dropdown-item d-flex align-items-center justify-content-between p-2 rounded-3 text-wrap mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; min-width: 34px;">
                          <i class="fa-solid fa-bowl-food text-primary"></i>
                        </div>
                        <div>
                          <div class="fw-bold text-dark text-sm mb-0">${escapeHtml(item.name)}</div>
                          <div class="text-muted small" style="font-size: 11px;">
                            <span class="badge bg-light text-secondary border px-1.5 py-0.5 rounded-pill">${escapeHtml(item.category_name)}</span>
                            <span class="ms-1 text-primary"><i class="fa-solid fa-store me-0.5"></i>${escapeHtml(item.restaurant_name)}</span>
                          </div>
                        </div>
                      </div>
                      <span class="fw-bold text-primary font-monospace">₹${parseFloat(item.price).toFixed(2)}</span>
                    </a>
                  `;
                });
              }

              html += `<div class="p-2 text-center border-top mt-1"><a href="index.php?search=${encodeURIComponent(q)}" class="btn btn-sm btn-outline-primary rounded-pill w-100 fw-bold">View all results for "${escapeHtml(q)}"</a></div>`;
              suggestionsBox.innerHTML = html;
              suggestionsBox.classList.remove('d-none');
            } else {
              suggestionsBox.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fa-solid fa-circle-question me-1"></i>No matching restaurants or dishes found. Try another keyword.</div>';
              suggestionsBox.classList.remove('d-none');
            }
          })
          .catch(() => {
            suggestionsBox.classList.add('d-none');
          });
      }, 200);
    });

    // Close suggestion box on click outside
    document.addEventListener('click', function(e) {
      if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.classList.add('d-none');
      }
    });

    searchInput.addEventListener('focus', function() {
      if (this.value.trim().length >= 2 && suggestionsBox.innerHTML.trim() !== '') {
        suggestionsBox.classList.remove('d-none');
      }
    });
  }

  function escapeHtml(text) {
    if (!text) return '';
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
  }
});
</script>

<?php include_once 'includes/footer.php'; ?>
