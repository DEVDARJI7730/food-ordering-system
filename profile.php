<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    $_SESSION['error'] = "You must log in to view your profile.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } else {
        
        $cleanPhone = preg_replace('/[\s\-()]/', '', $phone);
        if (!preg_match('/^[0-9]{10}$/', $cleanPhone)) {
            $errors[] = "Please enter a valid 10-digit phone number.";
        }
    }
    if (empty($address)) $errors[] = "Default delivery address is required.";

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $user_id]);

            
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_address'] = $address;

            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to update profile: " . $e->getMessage();
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
        <li class="breadcrumb-item active" aria-current="page">My Profile</li>
      </ol>
    </nav>
    <h2 class="fw-bold mb-4">My Account Profile</h2>
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

  <div class="col-lg-6 mb-4">
    <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
      <h5 class="fw-bold mb-4"><i class="fa-solid fa-user-pen text-primary me-2"></i>Update Personal Details</h5>
      
      <form action="profile.php" method="POST" id="profileForm">
        <div class="mb-3">
          <label for="email" class="form-label text-secondary">Email Address (Cannot be changed)</label>
          <input type="email" class="form-control rounded-pill px-3 bg-light text-muted" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
        </div>

        <div class="mb-3">
          <label for="name" class="form-label">Full Name</label>
          <input type="text" class="form-control rounded-pill px-3" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>

        <div class="mb-3">
          <label for="phone" class="form-label">Phone Number</label>
          <input type="text" class="form-control rounded-pill px-3" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
        </div>

        <div class="mb-3">
          <label for="address" class="form-label">Default Delivery Address</label>
          <textarea class="form-control px-3" id="address" name="address" rows="4" style="border-radius: 12px;" required><?php echo htmlspecialchars($user['address']); ?></textarea>
        </div>

        <div class="d-grid mt-4">
          <button type="submit" name="update_profile" class="btn btn-primary rounded-pill py-2.5 fw-bold">
            <i class="fa-solid fa-check-double me-2"></i>Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm-custom p-4 h-100 d-flex flex-column justify-content-between" style="border-radius: 16px; background-color: rgba(37, 99, 235, 0.02);">
      <div>
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-circle-info text-secondary me-2"></i>Account Information</h5>
        <p class="text-secondary">Your default details will be prefilled automatically whenever you place new orders on the checkout page.</p>
        
        <div class="mt-4">
          <div class="d-flex align-items-center mb-3">
            <i class="fa-solid fa-shield-halved text-success fs-4 me-3"></i>
            <div>
              <h6 class="mb-0 fw-bold text-dark">Secure Data</h6>
              <span class="text-secondary text-sm">Your contact information is protected and stored securely.</span>
            </div>
          </div>
          <div class="d-flex align-items-center mb-3">
            <i class="fa-solid fa-clock-rotate-left text-primary fs-4 me-3"></i>
            <div>
              <h6 class="mb-0 fw-bold text-dark">Order History</h6>
              <span class="text-secondary text-sm">Track your past purchases in the "My Orders" tab.</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="pt-4 border-top border-light-subtle">
        <a href="orders.php" class="btn btn-outline-secondary rounded-pill w-100 fw-bold">
          <i class="fa-solid fa-list-check me-2"></i>View My Order History
        </a>
      </div>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>
