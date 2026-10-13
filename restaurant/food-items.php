<?php
include_once 'includes/header.php';

$rest_id = $restaurant['id'];
$errors = [];
$food_id = 0;
$name = '';
$description = '';
$price = '';
$catagory_id = '';
$image_url = '';
$is_available = 1;
$is_form_active = false;
$form_title = 'Add New Menu Item';

// Delete Item (scoped to this restaurant)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("DELETE FROM food_items WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$delete_id, $rest_id]);
        $_SESSION['success'] = "Menu item deleted successfully!";
        header("Location: food-items.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Could not delete item: " . $e->getMessage();
        header("Location: food-items.php");
        exit();
    }
}

// Edit Item (scoped to this restaurant)
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $food_id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM food_items WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$food_id, $rest_id]);
        $food = $stmt->fetch();
        if ($food) {
            $name = $food['name'];
            $description = $food['description'];
            $price = $food['price'];
            $catagory_id = $food['catagory_id'];
            $image_url = $food['image_url'];
            $is_available = $food['is_available'];
            $is_form_active = true;
            $form_title = 'Edit Menu Item (#' . $food_id . ')';
        } else {
            $_SESSION['error'] = "Menu item not found or does not belong to your restaurant.";
            header("Location: food-items.php");
            exit();
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching item details.";
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $is_form_active = true;
}

// Save Item (scoped to this restaurant)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_food'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $catagory_id = intval($_POST['catagory_id'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $food_id = intval($_POST['food_id'] ?? 0);

    if (empty($name)) $errors[] = "Menu item name is required.";
    if ($price <= 0) $errors[] = "Price must be a positive number.";
    if ($catagory_id <= 0) $errors[] = "Please select a category.";

    // Handle optional file upload for food photo
    if (empty($errors) && isset($_FILES['food_image']) && $_FILES['food_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['food_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $dest_dir = __DIR__ . '/../uploads/food/';
            if (!is_dir($dest_dir)) mkdir($dest_dir, 0777, true);
            $new_name = 'food_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dest_dir . $new_name)) {
                $image_url = $new_name;
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($food_id > 0) {
                // Verify ownership and update
                $stmt = $conn->prepare("UPDATE food_items SET name = ?, description = ?, price = ?, catagory_id = ?, image_url = ?, is_available = ? WHERE id = ? AND restaurant_id = ?");
                $stmt->execute([$name, $description, $price, $catagory_id, $image_url, $is_available, $food_id, $rest_id]);
                $_SESSION['success'] = "Menu item updated successfully!";
            } else {
                // Insert with current restaurant_id
                $stmt = $conn->prepare("INSERT INTO food_items (restaurant_id, name, description, price, catagory_id, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$rest_id, $name, $description, $price, $catagory_id, $image_url, $is_available]);
                $_SESSION['success'] = "Menu item added successfully!";
            }
            header("Location: food-items.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error saving food item: " . $e->getMessage();
            $is_form_active = true;
        }
    } else {
        $is_form_active = true;
    }
}

// Fetch categories and restaurant's food items
try {
    $catagories = $conn->query("SELECT * FROM catagories ORDER BY name ASC")->fetchAll();
    
    $stmt = $conn->prepare("
        SELECT f.*, c.name AS catagory_name 
        FROM food_items f 
        JOIN catagories c ON f.catagory_id = c.id 
        WHERE f.restaurant_id = ?
        ORDER BY c.name ASC, f.name ASC
    ");
    $stmt->execute([$rest_id]);
    $food_items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

function getFoodItemImg($imageUrl) {
    if (!empty($imageUrl)) {
        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return $imageUrl;
        }
        if (file_exists(__DIR__ . '/../uploads/food/' . $imageUrl)) {
            return '../uploads/food/' . $imageUrl;
        }
        if (file_exists(__DIR__ . '/../assets/images/' . $imageUrl)) {
            return '../assets/images/' . $imageUrl;
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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Manage Menu Items</h2>
    <p class="text-muted mb-0">Add, update prices, and control availability for your dishes</p>
  </div>
  <?php if (!$is_form_active): ?>
    <a href="food-items.php?action=add" class="btn btn-primary rounded-pill px-4 shadow-sm">
      <i class="fa-solid fa-plus me-2"></i>Add Food Item
    </a>
  <?php endif; ?>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger border-0 shadow-sm-custom mb-4">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if ($is_form_active): ?>
  <div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
    <h5 class="fw-bold mb-4"><?php echo $form_title; ?></h5>
    
    <form action="food-items.php" method="POST" enctype="multipart/form-data">
      <?php if ($food_id > 0): ?>
        <input type="hidden" name="food_id" value="<?php echo $food_id; ?>">
      <?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label for="name" class="form-label fw-medium">Food Item Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        
        <div class="col-md-6">
          <label for="catagory_id" class="form-label fw-medium">Category <span class="text-danger">*</span></label>
          <select class="form-select" id="catagory_id" name="catagory_id" required>
            <option value="">-- Select Category --</option>
            <?php foreach ($catagories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo $catagory_id == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label for="price" class="form-label fw-medium">Price (₹) <span class="text-danger">*</span></label>
          <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
        </div>
        
        <div class="col-md-4">
          <label for="food_image" class="form-label fw-medium">Upload Image</label>
          <input type="file" class="form-control" id="food_image" name="food_image" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="col-md-4">
          <label for="image_url" class="form-label fw-medium">Or Preset Filename</label>
          <input type="text" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($image_url); ?>" placeholder="e.g. paneer_tikka.jpg">
        </div>

        <div class="col-12">
          <label for="description" class="form-label fw-medium">Description</label>
          <textarea class="form-control" id="description" name="description" rows="3" placeholder="Ingredients, taste notes, serving portion..."><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="is_available" name="is_available" <?php echo $is_available == 1 ? 'checked' : ''; ?>>
            <label class="form-check-label fw-medium text-dark" for="is_available">Available for Customer Orders</label>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" name="save_food" class="btn btn-primary rounded-pill px-4 py-2 fw-bold">
          <i class="fa-solid fa-floppy-disk me-2"></i>Save Menu Item
        </button>
        <a href="food-items.php" class="btn btn-outline-secondary rounded-pill px-4 py-2">Cancel</a>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Menu Items (<?php echo count($food_items); ?>)</h5>
  </div>
  
  <?php if (count($food_items) > 0): ?>
    <div class="table-responsive">
      <table class="table align-middle table-hover">
        <thead class="table-light">
          <tr>
            <th style="width: 60px;">Photo</th>
            <th>Item Name</th>
            <th>Category</th>
            <th>Description</th>
            <th>Price</th>
            <th>Availability</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($food_items as $item): ?>
            <tr>
              <td>
                <img src="<?php echo getFoodItemImg($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="rounded shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
              </td>
              <td>
                <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['name']); ?></div>
              </td>
              <td>
                <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill small">
                  <?php echo htmlspecialchars($item['catagory_name']); ?>
                </span>
              </td>
              <td class="text-secondary small" style="max-width: 250px;"><?php echo htmlspecialchars($item['description']); ?></td>
              <td class="fw-bold text-dark">₹<?php echo number_format($item['price'], 2); ?></td>
              <td>
                <?php if ($item['is_available'] == 1): ?>
                  <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill small"><i class="fa-solid fa-check me-1"></i>Available</span>
                <?php else: ?>
                  <span class="badge bg-danger-subtle text-danger px-2.5 py-1 rounded-pill small"><i class="fa-solid fa-xmark me-1"></i>Unavailable</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="food-items.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-light border rounded-pill px-2.5 me-1" title="Edit Item">
                  <i class="fa-solid fa-pen text-primary"></i>
                </a>
                <a href="food-items.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-light border rounded-pill px-2.5" onclick="return confirm('Are you sure you want to delete this menu item?');" title="Delete Item">
                  <i class="fa-solid fa-trash text-danger"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="text-center py-5">
      <i class="fa-solid fa-burger text-muted fs-1 mb-2 opacity-50"></i>
      <p class="text-muted mb-2">No menu items added for your restaurant yet.</p>
      <a href="food-items.php?action=add" class="btn btn-outline-primary btn-sm rounded-pill px-3">Add Your First Item</a>
    </div>
  <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
