<?php
include_once 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_name = trim($_POST['restaurant_name'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $license_issue_date = trim($_POST['license_issue_date'] ?? '');
    $license_expiry_date = trim($_POST['license_expiry_date'] ?? '');

    if (empty($restaurant_name) || empty($owner_name) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($license_number)) {
        $errors[] = "Please fill in all required fields marked with *.";
    }

    // Check license date
    if (!empty($license_issue_date) && !empty($license_expiry_date)) {
        if (strtotime($license_expiry_date) <= strtotime($license_issue_date)) {
            $errors[] = "License Expiry Date must be after Issue Date.";
        }
    }

    // Check license number uniqueness (if changed)
    if (empty($errors) && $license_number !== $restaurant['license_number']) {
        $check_stmt = $conn->prepare("SELECT id FROM restaurants WHERE license_number = ? AND id != ?");
        $check_stmt->execute([$license_number, $restaurant['id']]);
        if ($check_stmt->fetch()) {
            $errors[] = "This License Number is already registered by another restaurant.";
        }
    }

    // Logo upload
    $logo_filename = $restaurant['logo'];
    if (empty($errors) && isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo_file = $_FILES['logo'];
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($logo_file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid logo file format. Allowed: JPG, PNG, WEBP.";
        } elseif ($logo_file['size'] > 2 * 1024 * 1024) {
            $errors[] = "Logo file must be under 2MB.";
        } else {
            $logo_dir = __DIR__ . '/../uploads/logos/';
            if (!is_dir($logo_dir)) mkdir($logo_dir, 0777, true);
            $logo_filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($logo_file['tmp_name'], $logo_dir . $logo_filename);
        }
    }

    // License document re-upload
    $license_doc_name = $restaurant['license_document'];
    $document_reuploaded = false;
    if (empty($errors) && isset($_FILES['license_document']) && $_FILES['license_document']['error'] === UPLOAD_ERR_OK) {
        $doc_file = $_FILES['license_document'];
        $allowed_doc = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($doc_file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_doc)) {
            $errors[] = "Invalid license document format. Allowed: PDF, JPG, PNG.";
        } elseif ($doc_file['size'] > 5 * 1024 * 1024) {
            $errors[] = "License document must be under 5MB.";
        } else {
            $doc_dir = __DIR__ . '/../uploads/licenses/';
            if (!is_dir($doc_dir)) mkdir($doc_dir, 0777, true);
            $license_doc_name = 'license_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($doc_file['tmp_name'], $doc_dir . $license_doc_name)) {
                $document_reuploaded = true;
            }
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Update user name and phone
            $u_stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $u_stmt->execute([$owner_name, $phone, $address, $owner_id]);

            // If previously rejected and owner re-uploaded or modified license, reset status to Pending
            $new_status = $restaurant['status'];
            $rejection_reason = $restaurant['rejection_reason'];
            if ($restaurant['status'] === 'Rejected' && ($document_reuploaded || $license_number !== $restaurant['license_number'])) {
                $new_status = 'Pending';
                $rejection_reason = NULL;
            }

            // Update restaurant
            $r_stmt = $conn->prepare("UPDATE restaurants SET 
                restaurant_name = ?, owner_name = ?, phone = ?, address = ?, city = ?, state = ?, 
                description = ?, license_number = ?, license_issue_date = ?, license_expiry_date = ?, 
                license_document = ?, logo = ?, status = ?, rejection_reason = ?
                WHERE id = ?");
            $r_stmt->execute([
                $restaurant_name, $owner_name, $phone, $address, $city, $state,
                $description, $license_number, $license_issue_date, $license_expiry_date,
                $license_doc_name, $logo_filename, $new_status, $rejection_reason,
                $restaurant['id']
            ]);

            $conn->commit();

            $_SESSION['success'] = "Restaurant profile updated successfully!" . ($new_status === 'Pending' && $restaurant['status'] === 'Rejected' ? " Your updated license has been submitted for re-verification." : "");
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Failed to update profile: " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0">Restaurant & License Profile</h2>
    <p class="text-muted mb-0">Manage your business information and compliance credentials</p>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger border-0 shadow-sm-custom mb-4">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="profile.php" enctype="multipart/form-data">
  <div class="row g-4">
    
    <!-- Left Column: Business Details -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-store text-primary me-2"></i>Business Information</h5>
        
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-medium">Restaurant Name <span class="text-danger">*</span></label>
            <input type="text" name="restaurant_name" class="form-control" value="<?php echo htmlspecialchars($restaurant['restaurant_name']); ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-medium">Owner Full Name <span class="text-danger">*</span></label>
            <input type="text" name="owner_name" class="form-control" value="<?php echo htmlspecialchars($restaurant['owner_name']); ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-medium">Email Address</label>
            <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($restaurant['email']); ?>" readonly>
            <div class="form-text text-muted">Primary account email cannot be changed.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-medium">Phone Number <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($restaurant['phone']); ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label fw-medium">Street Address <span class="text-danger">*</span></label>
            <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($restaurant['address']); ?></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-medium">City <span class="text-danger">*</span></label>
            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($restaurant['city']); ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-medium">State <span class="text-danger">*</span></label>
            <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($restaurant['state']); ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label fw-medium">Restaurant Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Tell customers about your kitchen, specialties, etc."><?php echo htmlspecialchars($restaurant['description'] ?? ''); ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label fw-medium">Change Restaurant Logo</label>
            <div class="d-flex align-items-center gap-3">
              <?php if (!empty($restaurant['logo']) && file_exists(__DIR__ . '/../uploads/logos/' . $restaurant['logo'])): ?>
                <img src="../uploads/logos/<?php echo htmlspecialchars($restaurant['logo']); ?>" alt="Current Logo" class="rounded-circle object-fit-cover shadow-sm" style="width: 50px; height: 50px;">
              <?php endif; ?>
              <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: License & Compliance -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm-custom p-4 mb-4" style="border-radius: 16px;">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-id-card text-primary me-2"></i>License & Verification</h5>

        <div class="p-3 mb-3 rounded-3 <?php echo $restaurant['status'] === 'Approved' ? 'bg-success-subtle text-success' : ($restaurant['status'] === 'Pending' ? 'bg-warning-subtle text-dark' : 'bg-danger-subtle text-danger'); ?>">
          <div class="d-flex justify-content-between align-items-center">
            <strong>Current Verification Status:</strong>
            <span class="badge <?php echo $restaurant['status'] === 'Approved' ? 'bg-success' : ($restaurant['status'] === 'Pending' ? 'bg-warning text-dark' : 'bg-danger'); ?> px-3 py-1.5 rounded-pill">
              <?php echo $restaurant['status']; ?>
            </span>
          </div>
          <?php if (!empty($restaurant['rejection_reason'])): ?>
            <div class="mt-2 small">
              <strong>Admin Note:</strong> <?php echo htmlspecialchars($restaurant['rejection_reason']); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label fw-medium">License / FSSAI Number <span class="text-danger">*</span></label>
          <input type="text" name="license_number" class="form-control font-monospace" value="<?php echo htmlspecialchars($restaurant['license_number']); ?>" required>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-medium small">Issue Date <span class="text-danger">*</span></label>
            <input type="date" name="license_issue_date" class="form-control" value="<?php echo htmlspecialchars($restaurant['license_issue_date']); ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label fw-medium small">Expiry Date <span class="text-danger">*</span></label>
            <input type="date" name="license_expiry_date" class="form-control" value="<?php echo htmlspecialchars($restaurant['license_expiry_date']); ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-medium">Upload Updated License Document</label>
          <input type="file" name="license_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
          <div class="form-text text-muted">Upload a new document to replace the current file (PDF, JPG, PNG - Max 5MB).</div>
        </div>

        <?php if (!empty($restaurant['license_document']) && file_exists(__DIR__ . '/../uploads/licenses/' . $restaurant['license_document'])): ?>
          <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center mb-3">
            <div class="small text-truncate">
              <i class="fa-solid fa-file-lines me-1 text-primary"></i>
              <span><?php echo htmlspecialchars($restaurant['license_document']); ?></span>
            </div>
            <a href="../uploads/licenses/<?php echo htmlspecialchars($restaurant['license_document']); ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2">
              <i class="fa-solid fa-eye me-1"></i>View
            </a>
          </div>
        <?php endif; ?>

        <div class="p-3 mb-3 rounded-3 bg-light border">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small d-block">Platform Partner Commission:</span>
              <strong class="text-dark fs-5 font-monospace"><?php echo number_format((float)($restaurant['commission_rate'] ?? 10.00), 2); ?>%</strong>
              <span class="text-muted small">per fulfilled order</span>
            </div>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
              <i class="fa-solid fa-handshake me-1"></i>Active Contract
            </span>
          </div>
          <div class="text-muted small mt-1" style="font-size: 11px;">
            <i class="fa-solid fa-circle-info me-1"></i>Commission is deducted automatically on customer checkout and recorded on your sales slips.
          </div>
        </div>

        <div class="d-grid mt-4">
          <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
            <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
          </button>
        </div>
      </div>
    </div>

  </div>
</form>

<?php include_once 'includes/footer.php'; ?>
