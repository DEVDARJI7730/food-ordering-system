<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['user_role'] === 'restaurant_owner') {
        header("Location: restaurant/dashboard.php");
        exit();
    }
}

$errors = [];
$success = '';

// Form values initialization for sticky form
$restaurant_name = '';
$owner_name = '';
$email = '';
$phone = '';
$address = '';
$city = '';
$state = '';
$license_number = '';
$license_issue_date = '';
$license_expiry_date = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = trim($_POST['restaurant_name'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $license_issue_date = trim($_POST['license_issue_date'] ?? '');
    $license_expiry_date = trim($_POST['license_expiry_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($restaurant_name) || empty($owner_name) || empty($email) || empty($phone) || 
        empty($address) || empty($city) || empty($state) || empty($license_number) || 
        empty($license_issue_date) || empty($license_expiry_date) || empty($password)) {
        $errors[] = "Please fill in all required fields marked with *.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check date validity
    if (!empty($license_issue_date) && !empty($license_expiry_date)) {
        $issue_ts = strtotime($license_issue_date);
        $expiry_ts = strtotime($license_expiry_date);
        $today_ts = strtotime(date('Y-m-d'));

        if ($expiry_ts <= $issue_ts) {
            $errors[] = "License Expiry Date must be after the License Issue Date.";
        }
        if ($expiry_ts < $today_ts) {
            $errors[] = "The license has already expired. Please submit a valid active license.";
        }
    }

    // Check email uniqueness in users
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered. Please use another email or log in.";
        }
    }

    // Check license number uniqueness
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM restaurants WHERE license_number = ?");
        $stmt->execute([$license_number]);
        if ($stmt->fetch()) {
            $errors[] = "This License Number is already registered on our platform.";
        }
    }

    // License Document Upload handling
    $license_filename = '';
    if (empty($errors)) {
        if (!isset($_FILES['license_document']) || $_FILES['license_document']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Please upload a valid Restaurant License Document.";
        } else {
            $file = $_FILES['license_document'];
            $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];

            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Invalid license document format. Allowed formats: PDF, JPG, JPEG, PNG.";
            } elseif ($file_size > 5 * 1024 * 1024) { // 5MB max
                $errors[] = "License document size exceeds the maximum limit of 5MB.";
            } else {
                $upload_dir = __DIR__ . '/uploads/licenses/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $license_filename = 'license_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                if (!move_uploaded_file($file['tmp_name'], $upload_dir . $license_filename)) {
                    $errors[] = "Failed to upload license document. Please try again.";
                }
            }
        }
    }

    // Restaurant Logo Upload handling (optional)
    $logo_filename = null;
    if (empty($errors) && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo_file = $_FILES['logo'];
        $allowed_logo_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $logo_ext = strtolower(pathinfo($logo_file['name'], PATHINFO_EXTENSION));
        $logo_size = $logo_file['size'];

        if (!in_array($logo_ext, $allowed_logo_exts)) {
            $errors[] = "Invalid logo image format. Allowed formats: JPG, JPEG, PNG, WEBP.";
        } elseif ($logo_size > 2 * 1024 * 1024) { // 2MB max
            $errors[] = "Restaurant logo size exceeds the maximum limit of 2MB.";
        } else {
            $logo_dir = __DIR__ . '/uploads/logos/';
            if (!is_dir($logo_dir)) {
                mkdir($logo_dir, 0777, true);
            }
            $logo_filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $logo_ext;
            if (!move_uploaded_file($logo_file['tmp_name'], $logo_dir . $logo_filename)) {
                $errors[] = "Failed to upload restaurant logo.";
            }
        }
    }

    // Save to database
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // 1. Create owner user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, 'restaurant_owner')");
            $user_stmt->execute([$owner_name, $email, $hashed_password, $phone, $address]);
            $owner_id = $conn->lastInsertId();

            // 2. Create restaurant record with status 'Pending'
            $rest_stmt = $conn->prepare("INSERT INTO restaurants 
                (owner_id, restaurant_name, owner_name, email, phone, address, city, state, license_number, license_issue_date, license_expiry_date, license_document, logo, description, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $rest_stmt->execute([
                $owner_id, $restaurant_name, $owner_name, $email, $phone, 
                $address, $city, $state, $license_number, $license_issue_date, 
                $license_expiry_date, $license_filename, $logo_filename, $description
            ]);

            $conn->commit();

            $success = "Restaurant registered successfully! Your license verification is currently <strong>Pending Admin Approval</strong>. You can now log in to access your Restaurant Owner portal.";
            
            // Clear sticky fields
            $restaurant_name = $owner_name = $email = $phone = $address = $city = $state = $license_number = $license_issue_date = $license_expiry_date = $description = '';
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Registration failed due to a database error: " . $e->getMessage();
        }
    }
}

include_once 'includes/header.php';
?>

<div class="row justify-content-center my-4">
  <div class="col-lg-9">
    <div class="card shadow border-0" style="border-radius: 20px;">
      <div class="card-header bg-gradient text-white p-4" style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 20px 20px 0 0;">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
            <i class="fa-solid fa-store fs-3"></i>
          </div>
          <div>
            <h3 class="fw-bold mb-1 text-white">Partner with Nivaala</h3>
            <p class="mb-0 text-white-50">Register your restaurant & start receiving orders today</p>
          </div>
        </div>
      </div>

      <div class="card-body p-4 p-md-5">
        <?php if (!empty($success)): ?>
          <div class="alert alert-success border-0 shadow-sm p-4 text-center mb-4" role="alert" style="border-radius: 12px;">
            <i class="fa-solid fa-circle-check text-success fs-1 d-block mb-3"></i>
            <h4 class="fw-bold text-success">Registration Submitted!</h4>
            <p class="mb-3"><?php echo $success; ?></p>
            <div class="d-flex justify-content-center gap-3 mt-3">
              <a href="login.php" class="btn btn-success px-4 rounded-pill fw-bold"><i class="fa-solid fa-right-to-bracket me-2"></i>Go to Login</a>
              <a href="index.php" class="btn btn-outline-secondary px-4 rounded-pill">Back to Home</a>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger border-0 shadow-sm-custom mb-4" role="alert">
            <h6 class="fw-bold mb-2"><i class="fa-solid fa-circle-exclamation me-2"></i>Please resolve the following issues:</h6>
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
        <form action="restaurant-register.php" method="POST" enctype="multipart/form-data">
          
          <!-- Section 1: Restaurant Basic Info -->
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <span class="badge bg-primary rounded-circle px-2 py-1">1</span>
            <h5 class="fw-bold mb-0 text-secondary">Restaurant & Owner Details</h5>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="restaurant_name" class="form-label fw-medium">Restaurant Name <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-utensils text-muted"></i></span>
                <input type="text" class="form-control border-start-0" id="restaurant_name" name="restaurant_name" value="<?php echo htmlspecialchars($restaurant_name); ?>" placeholder="e.g. Royal Spice Kitchen" required>
              </div>
            </div>

            <div class="col-md-6">
              <label for="owner_name" class="form-label fw-medium">Owner Full Name <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                <input type="text" class="form-control border-start-0" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($owner_name); ?>" placeholder="e.g. Rajesh Kumar" required>
              </div>
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label fw-medium">Business / Owner Email <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                <input type="email" class="form-control border-start-0" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="owner@restaurant.com" required>
              </div>
            </div>

            <div class="col-md-6">
              <label for="phone" class="form-label fw-medium">Contact Phone Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                <input type="tel" class="form-control border-start-0" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="e.g. 9876543210" required>
              </div>
            </div>

            <div class="col-12">
              <label for="address" class="form-label fw-medium">Restaurant Street Address <span class="text-danger">*</span></label>
              <textarea class="form-control" id="address" name="address" rows="2" placeholder="Full street address, building, landmark" required><?php echo htmlspecialchars($address); ?></textarea>
            </div>

            <div class="col-md-6">
              <label for="city" class="form-label fw-medium">City <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="e.g. Mumbai, New Delhi" required>
            </div>

            <div class="col-md-6">
              <label for="state" class="form-label fw-medium">State / Region <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($state); ?>" placeholder="e.g. Maharashtra, Delhi" required>
            </div>

            <div class="col-12">
              <label for="description" class="form-label fw-medium">Restaurant Description</label>
              <textarea class="form-control" id="description" name="description" rows="2" placeholder="Tell customers about your cuisines, specialties, and dining experience..."><?php echo htmlspecialchars($description); ?></textarea>
            </div>
          </div>

          <!-- Section 2: License & Verification -->
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <span class="badge bg-primary rounded-circle px-2 py-1">2</span>
            <h5 class="fw-bold mb-0 text-secondary">License & Regulatory Verification</h5>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label for="license_number" class="form-label fw-medium">License / FSSAI Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-id-card text-muted"></i></span>
                <input type="text" class="form-control border-start-0 font-monospace" id="license_number" name="license_number" value="<?php echo htmlspecialchars($license_number); ?>" placeholder="e.g. FSSAI-100234567890" required>
              </div>
            </div>

            <div class="col-md-4">
              <label for="license_issue_date" class="form-label fw-medium">Issue Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="license_issue_date" name="license_issue_date" value="<?php echo htmlspecialchars($license_issue_date); ?>" required>
            </div>

            <div class="col-md-4">
              <label for="license_expiry_date" class="form-label fw-medium">Expiry Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="license_expiry_date" name="license_expiry_date" value="<?php echo htmlspecialchars($license_expiry_date); ?>" required>
            </div>

            <div class="col-md-6">
              <label for="license_document" class="form-label fw-medium">Upload License Document <span class="text-danger">*</span></label>
              <input type="file" class="form-control" id="license_document" name="license_document" accept=".pdf,.jpg,.jpeg,.png" required>
              <div class="form-text text-muted">Upload scanned license (PDF, JPG, PNG - Max 5MB). Admin will review this document.</div>
            </div>

            <div class="col-md-6">
              <label for="logo" class="form-label fw-medium">Restaurant Logo (Optional)</label>
              <input type="file" class="form-control" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp">
              <div class="form-text text-muted">JPG, PNG, or WEBP (Max 2MB). Displayed on your restaurant profile.</div>
            </div>
          </div>

          <!-- Section 3: Owner Account Security -->
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
            <span class="badge bg-primary rounded-circle px-2 py-1">3</span>
            <h5 class="fw-bold mb-0 text-secondary">Account Credentials</h5>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label for="password" class="form-label fw-medium">Account Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                <input type="password" class="form-control border-start-0" id="password" name="password" minlength="6" placeholder="At least 6 characters" required>
              </div>
            </div>

            <div class="col-md-6">
              <label for="confirm_password" class="form-label fw-medium">Confirm Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock-check text-muted"></i></span>
                <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" minlength="6" placeholder="Repeat your password" required>
              </div>
            </div>
          </div>

          <div class="alert alert-info border-0 shadow-sm-custom d-flex align-items-center gap-3 mb-4">
            <i class="fa-solid fa-shield-halved fs-3 text-info"></i>
            <div style="font-size: 0.9rem;">
              <strong>Verification Notice:</strong> All restaurant registrations undergo manual license inspection by the system administrator. Once approved, your restaurant will be publicly visible to customers for ordering.
            </div>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm py-3">
              <i class="fa-solid fa-paper-plane me-2"></i>Submit Restaurant Registration
            </button>
          </div>

          <div class="text-center mt-4">
            <span class="text-muted">Already registered as a partner?</span>
            <a href="login.php" class="fw-bold text-primary text-decoration-none ms-1">Log in to Restaurant Portal</a>
          </div>

        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>
