<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$search = trim($_GET['search'] ?? '');
$city_filter = trim($_GET['city'] ?? '');
$sort_by = $_GET['sort'] ?? 'relevance';

$sql = "SELECT r.*, 
               (SELECT COUNT(*) FROM food_items f WHERE f.restaurant_id = r.id AND f.is_available = 1) as total_dishes
        FROM restaurants r 
        WHERE r.status = 'Approved'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (r.restaurant_name LIKE ? OR r.description LIKE ? OR r.city LIKE ?)";
    $s_term = "%$search%";
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
}

if (!empty($city_filter)) {
    $sql .= " AND r.city = ?";
    $params[] = $city_filter;
}

if ($sort_by === 'name_asc') {
    $sql .= " ORDER BY r.restaurant_name ASC";
} else {
    $sql .= " ORDER BY r.restaurant_name ASC";
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $restaurants = $stmt->fetchAll();

    // Get list of distinct cities for quick filter
    $cities = $conn->query("SELECT DISTINCT city FROM restaurants WHERE status = 'Approved' ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include_once 'includes/header.php';
?>

<!-- Hero Header -->
<div class="row align-items-center mb-5 bg-white p-4 p-md-5 rounded-4 shadow-sm-custom border border-light">
  <div class="col-lg-7">
    <span class="badge text-uppercase fw-bold px-3 py-2 mb-3 shadow-sm-custom border border-light" style="background-color: rgba(16, 185, 129, 0.1); color: #059669;">
      <i class="fa-solid fa-certificate me-1"></i> 100% License Verified Kitchens
    </span>
    <h1 class="display-5 fw-extrabold text-dark mb-3">Order from Your Favorite <span class="text-primary">Restaurants</span></h1>
    <p class="lead text-secondary mb-4">Discover verified culinary partners across your city. All restaurants are certified and quality checked.</p>
    
    <form action="restaurants.php" method="GET" class="d-flex gap-2">
      <div class="input-group">
        <span class="input-group-text bg-light border-0 ps-3 rounded-start-pill"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
        <input type="text" class="form-control bg-light border-0 py-3 rounded-end-pill" name="search" placeholder="Search restaurants by name, cuisine, city..." value="<?php echo htmlspecialchars($search); ?>">
      </div>
      <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold">Find</button>
    </form>
  </div>
  <div class="col-lg-5 d-none d-lg-block text-center">
    <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80" alt="Restaurant Dining" class="img-fluid rounded-4 shadow-md-custom" style="height: 280px; width: 100%; object-fit: cover;">
  </div>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
  <div class="d-flex align-items-center gap-2 overflow-x-auto pb-1">
    <?php if (count($cities) > 1): ?>
      <span class="fw-bold text-muted small me-2"><i class="fa-solid fa-location-dot me-1"></i>City:</span>
      <a href="restaurants.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="btn btn-sm <?php echo empty($city_filter) ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3">
        All Cities
      </a>
      <?php foreach ($cities as $c): ?>
        <a href="restaurants.php?city=<?php echo urlencode($c); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm <?php echo $city_filter === $c ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill px-3">
          <?php echo htmlspecialchars($c); ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Restaurant Cards Grid -->
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
  <?php if (count($restaurants) > 0): ?>
    <?php foreach ($restaurants as $r): ?>
      <div class="col">
        <div class="card h-100 border-0 shadow-sm-custom hover-shadow transition" style="border-radius: 20px; overflow: hidden;">
          
          <!-- Card Top Bar with Cover / Image -->
          <div class="p-4 bg-light position-relative d-flex align-items-center gap-3 border-bottom">
            <?php if (!empty($r['logo']) && file_exists(__DIR__ . '/uploads/logos/' . $r['logo'])): ?>
              <img src="uploads/logos/<?php echo htmlspecialchars($r['logo']); ?>" alt="<?php echo htmlspecialchars($r['restaurant_name']); ?>" class="rounded-circle object-fit-cover shadow-sm bg-white p-1" style="width: 70px; height: 70px;">
            <?php else: ?>
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow-sm" style="width: 70px; height: 70px; min-width: 70px;">
                <?php echo strtoupper(substr($r['restaurant_name'], 0, 2)); ?>
              </div>
            <?php endif; ?>

            <div>
              <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($r['restaurant_name']); ?></h5>
              <div class="text-muted small">
                <i class="fa-solid fa-location-dot text-danger me-1"></i><?php echo htmlspecialchars($r['city']) . ', ' . htmlspecialchars($r['state']); ?>
              </div>
            </div>

            <span class="badge bg-success-subtle text-success position-absolute top-0 end-0 m-3 px-2.5 py-1.5 rounded-pill small border border-success-subtle">
              <i class="fa-solid fa-shield-check me-1"></i>Verified
            </span>
          </div>

          <div class="card-body p-4 d-flex flex-column">
            <p class="text-secondary small mb-3 flex-grow-1" style="min-height: 48px;">
              <?php 
                $desc = !empty($r['description']) ? $r['description'] : 'Serving freshly prepared, mouthwatering food prepared under certified hygiene standards.';
                echo htmlspecialchars(strlen($desc) > 120 ? substr($desc, 0, 117) . '...' : $desc); 
              ?>
            </p>

            <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3 mb-3 small">
              <span class="text-muted"><i class="fa-solid fa-utensils me-1 text-primary"></i>Dishes: <strong class="text-dark"><?php echo (int)$r['total_dishes']; ?> items</strong></span>
              <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small">
                <i class="fa-solid fa-certificate me-1"></i>Certified
              </span>
            </div>

            <a href="index.php?restaurant_id=<?php echo $r['id']; ?>" class="btn btn-primary rounded-pill py-2.5 fw-bold w-100 shadow-sm">
              <i class="fa-solid fa-book-open me-2"></i>Explore Menu & Order
            </a>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="col-12 text-center py-5">
      <i class="fa-solid fa-store-slash text-muted fs-1 mb-3 opacity-50"></i>
      <h4 class="fw-bold">No Verified Restaurants Found</h4>
      <p class="text-muted">No approved restaurants match your search query. Try broadening your filter.</p>
      <a href="restaurants.php" class="btn btn-primary rounded-pill px-4">View All Restaurants</a>
    </div>
  <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
