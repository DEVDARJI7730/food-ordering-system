<?php
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = "Invalid restaurant ID.";
    header("Location: restaurants.php");
    exit();
}

// Handle Verification Decisions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'];

    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE restaurants SET status = 'Approved', rejection_reason = NULL, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$admin_id, $id]);
            $_SESSION['success'] = "Restaurant license verified and approved successfully!";
        } elseif ($action === 'update_commission') {
            $commission_rate = isset($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : 10.00;
            if ($commission_rate < 0 || $commission_rate > 100) {
                $_SESSION['error'] = "Commission rate must be between 0% and 100%.";
            } else {
                $stmt = $conn->prepare("UPDATE restaurants SET commission_rate = ? WHERE id = ?");
                $stmt->execute([$commission_rate, $id]);
                $_SESSION['success'] = "Commission rate updated to {$commission_rate}% successfully!";
            }
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? '');
            if (empty($reason)) {
                $_SESSION['error'] = "Please provide a reason for rejecting this license.";
            } else {
                $stmt = $conn->prepare("UPDATE restaurants SET status = 'Rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$reason, $admin_id, $id]);
                $_SESSION['success'] = "Restaurant license rejected with specified reason.";
            }
        } elseif ($action === 'suspend') {
            $stmt = $conn->prepare("UPDATE restaurants SET status = 'Suspended' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Restaurant suspended.";
        } elseif ($action === 'reactivate') {
            $stmt = $conn->prepare("UPDATE restaurants SET status = 'Approved' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Restaurant status reactivated to Approved.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    header("Location: verify-license.php?id=$id");
    exit();
}

// Fetch Restaurant & Owner Details
$stmt = $conn->prepare("SELECT r.*, u.name as owner_account_name, u.email as owner_account_email, 
                               a.name as approver_name
                        FROM restaurants r 
                        JOIN users u ON r.owner_id = u.id 
                        LEFT JOIN users a ON r.approved_by = a.id
                        WHERE r.id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    $_SESSION['error'] = "Restaurant not found.";
    header("Location: restaurants.php");
    exit();
}

// License Date & Expiry Calculations
$today = new DateTime();
$issue_date = new DateTime($r['license_issue_date']);
$expiry_date = new DateTime($r['license_expiry_date']);
$is_expired = $expiry_date < $today;
$days_remaining = $today->diff($expiry_date)->days;
if ($is_expired) {
    $days_remaining = -$days_remaining;
}

$license_file_path = __DIR__ . '/../uploads/licenses/' . $r['license_document'];
$license_file_exists = !empty($r['license_document']) && file_exists($license_file_path);
$license_file_ext = strtolower(pathinfo($r['license_document'] ?? '', PATHINFO_EXTENSION));
$is_pdf = $license_file_ext === 'pdf';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="restaurants.php" class="btn btn-outline-secondary btn-sm rounded-pill mb-2">
      <i class="fa-solid fa-arrow-left me-1"></i>Back to Restaurants
    </a>
    <h3 class="fw-bold mb-0">License Verification & Inspection</h3>
  </div>
  <div>
    <?php if ($r['status'] === 'Approved'): ?>
      <span class="badge bg-success px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Approved</span>
    <?php elseif ($r['status'] === 'Pending'): ?>
      <span class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-clock me-1"></i>Pending Verification</span>
    <?php elseif ($r['status'] === 'Rejected'): ?>
      <span class="badge bg-danger px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-circle-xmark me-1"></i>Rejected</span>
    <?php elseif ($r['status'] === 'Suspended'): ?>
      <span class="badge bg-secondary px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-ban me-1"></i>Suspended</span>
    <?php endif; ?>
  </div>
</div>

<div class="row g-4">
  <!-- Left Column: Verification Review & Actions -->
  <div class="col-lg-5">
    
    <!-- Restaurant Information Card -->
    <div class="card border-0 shadow-sm-custom mb-4" style="border-radius: 16px;">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-store me-2 text-primary"></i>Restaurant Profile</h5>
      </div>
      <div class="card-body px-4">
        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
          <?php if (!empty($r['logo']) && file_exists(__DIR__ . '/../uploads/logos/' . $r['logo'])): ?>
            <img src="../uploads/logos/<?php echo htmlspecialchars($r['logo']); ?>" alt="Logo" class="rounded-circle object-fit-cover shadow-sm" style="width: 56px; height: 56px;">
          <?php else: ?>
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 56px; height: 56px;">
              <?php echo strtoupper(substr($r['restaurant_name'], 0, 2)); ?>
            </div>
          <?php endif; ?>
          <div>
            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($r['restaurant_name']); ?></h5>
            <div class="text-muted small"><?php echo htmlspecialchars($r['city']) . ', ' . htmlspecialchars($r['state']); ?></div>
          </div>
        </div>

        <div class="mb-2">
          <span class="text-muted small d-block">Owner Name:</span>
          <strong><?php echo htmlspecialchars($r['owner_name']); ?></strong>
        </div>
        <div class="mb-2">
          <span class="text-muted small d-block">Email Address:</span>
          <strong><a href="mailto:<?php echo htmlspecialchars($r['email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($r['email']); ?></a></strong>
        </div>
        <div class="mb-2">
          <span class="text-muted small d-block">Phone Number:</span>
          <strong><a href="tel:<?php echo htmlspecialchars($r['phone']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($r['phone']); ?></a></strong>
        </div>
        <div class="mb-2">
          <span class="text-muted small d-block">Address:</span>
          <div><?php echo nl2br(htmlspecialchars($r['address'])); ?></div>
        </div>
        <?php if (!empty($r['description'])): ?>
          <div class="mb-2">
            <span class="text-muted small d-block">Description:</span>
            <div class="text-muted small"><?php echo nl2br(htmlspecialchars($r['description'])); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- License Metadata Card -->
    <div class="card border-0 shadow-sm-custom mb-4" style="border-radius: 16px;">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-id-card me-2 text-primary"></i>License Metadata</h5>
      </div>
      <div class="card-body px-4">
        <div class="p-3 bg-light rounded-3 mb-3">
          <div class="text-muted small">License / Registration Number:</div>
          <div class="fs-5 fw-bold font-monospace text-primary"><?php echo htmlspecialchars($r['license_number']); ?></div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="p-2 border rounded">
              <span class="text-muted small d-block">Issue Date</span>
              <strong><?php echo $issue_date->format('d M Y'); ?></strong>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2 border rounded <?php echo $is_expired ? 'border-danger bg-danger-subtle' : ''; ?>">
              <span class="text-muted small d-block">Expiry Date</span>
              <strong class="<?php echo $is_expired ? 'text-danger' : ''; ?>"><?php echo $expiry_date->format('d M Y'); ?></strong>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center justify-content-between p-2 rounded <?php echo $is_expired ? 'bg-danger text-white' : 'bg-info-subtle text-dark'; ?> small mb-2">
          <span><i class="fa-solid fa-clock me-1"></i>License Validity:</span>
          <strong>
            <?php if ($is_expired): ?>
              Expired <?php echo abs($days_remaining); ?> days ago
            <?php else: ?>
              <?php echo $days_remaining; ?> days remaining
            <?php endif; ?>
          </strong>
        </div>

        <?php if ($r['approved_by']): ?>
          <div class="text-muted small mt-3 pt-2 border-top">
            Last reviewed by <strong><?php echo htmlspecialchars($r['approver_name'] ?? 'Admin'); ?></strong> 
            on <?php echo date('d M Y, h:i A', strtotime($r['approved_at'])); ?>
          </div>
        <?php endif; ?>

        <?php if ($r['status'] === 'Rejected' && !empty($r['rejection_reason'])): ?>
          <div class="alert alert-danger mt-3 mb-0" style="font-size: 0.9rem;">
            <strong><i class="fa-solid fa-circle-exclamation me-1"></i>Rejection Reason:</strong><br>
            <?php echo nl2br(htmlspecialchars($r['rejection_reason'])); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Commission Settings Card -->
    <div class="card border-0 shadow-sm-custom mb-4" style="border-radius: 16px;">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-percent me-2 text-primary"></i>Platform Commission Rate</h5>
      </div>
      <div class="card-body px-4">
        <p class="text-muted small mb-3">Set the percentage of commission deducted by the platform for each order fulfilled by this restaurant partner.</p>
        <form method="POST" action="verify-license.php?id=<?php echo $id; ?>">
          <input type="hidden" name="action" value="update_commission">
          <div class="input-group mb-2">
            <span class="input-group-text bg-light fw-bold"><i class="fa-solid fa-hand-holding-dollar text-primary"></i></span>
            <input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control fw-bold" value="<?php echo number_format((float)($r['commission_rate'] ?? 10.00), 2); ?>" required>
            <span class="input-group-text fw-bold">%</span>
            <button type="submit" class="btn btn-primary px-3">Update</button>
          </div>
          <div class="small text-muted">Current default is 10.00%. Revenue split is calculated dynamically per order.</div>
        </form>
      </div>
    </div>

    <!-- Admin Decision Actions -->
    <div class="card border-0 shadow-sm-custom" style="border-radius: 16px;">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-gavel me-2 text-primary"></i>Verification Decision</h5>
      </div>
      <div class="card-body px-4">
        
        <!-- Approve Form -->
        <form method="POST" action="verify-license.php?id=<?php echo $id; ?>" class="mb-3" onsubmit="return confirm('Confirm approval for this restaurant?');">
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill fw-bold" <?php echo $is_expired ? 'onclick="return confirm(\'Warning: The license date appears expired. Are you sure you want to approve?\');"' : ''; ?>>
            <i class="fa-solid fa-circle-check me-2"></i>Approve Restaurant
          </button>
        </form>

        <!-- Reject Form / Accordion -->
        <div class="accordion mb-3" id="rejectAccordion">
          <div class="accordion-item border rounded-3">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed text-danger fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#rejectCollapse">
                <i class="fa-solid fa-circle-xmark me-2"></i>Reject with Reason
              </button>
            </h2>
            <div id="rejectCollapse" class="accordion-collapse collapse" data-bs-parent="#rejectAccordion">
              <div class="accordion-body">
                <form method="POST" action="verify-license.php?id=<?php echo $id; ?>">
                  <input type="hidden" name="action" value="reject">
                  <div class="mb-3">
                    <label for="rejection_reason" class="form-label small fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="State reason (e.g. Document unreadable, License expired, Address mismatch)..." required><?php echo htmlspecialchars($r['rejection_reason'] ?? ''); ?></textarea>
                  </div>
                  <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold">
                    Confirm & Send Rejection
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Suspend / Reactivate -->
        <?php if ($r['status'] === 'Approved'): ?>
          <form method="POST" action="verify-license.php?id=<?php echo $id; ?>" onsubmit="return confirm('Suspend this restaurant? Customers will no longer be able to place orders.');">
            <input type="hidden" name="action" value="suspend">
            <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill">
              <i class="fa-solid fa-ban me-2"></i>Suspend Restaurant
            </button>
          </form>
        <?php elseif ($r['status'] === 'Suspended'): ?>
          <form method="POST" action="verify-license.php?id=<?php echo $id; ?>" onsubmit="return confirm('Reactivate this restaurant?');">
            <input type="hidden" name="action" value="reactivate">
            <button type="submit" class="btn btn-outline-success w-100 rounded-pill">
              <i class="fa-solid fa-circle-check me-2"></i>Reactivate Restaurant
            </button>
          </form>
        <?php endif; ?>

      </div>
    </div>

  </div>

  <!-- Right Column: Document Viewer -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm-custom h-100" style="border-radius: 16px;">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-file-pdf me-2 text-primary"></i>Uploaded License Document</h5>
        <?php if ($license_file_exists): ?>
          <a href="../uploads/licenses/<?php echo htmlspecialchars($r['license_document']); ?>" target="_blank" download class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="fa-solid fa-download me-1"></i>Download Document
          </a>
        <?php endif; ?>
      </div>
      <div class="card-body px-4 pb-4">
        <?php if (!$license_file_exists): ?>
          <div class="alert alert-warning text-center py-5" style="border-radius: 12px;">
            <i class="fa-solid fa-file-circle-question fs-1 d-block mb-3 text-warning"></i>
            <h5 class="fw-bold">No Document File Found</h5>
            <p class="text-muted mb-0">The license document file (<code><?php echo htmlspecialchars($r['license_document']); ?></code>) is not found on the server.</p>
          </div>
        <?php elseif ($is_pdf): ?>
          <div class="ratio ratio-4x3 border rounded shadow-sm overflow-hidden" style="min-height: 550px;">
            <iframe src="../uploads/licenses/<?php echo htmlspecialchars($r['license_document']); ?>" title="License Document PDF"></iframe>
          </div>
          <div class="mt-2 text-center text-muted small">
            If PDF does not render inline, <a href="../uploads/licenses/<?php echo htmlspecialchars($r['license_document']); ?>" target="_blank" class="fw-bold">click here to open directly</a>.
          </div>
        <?php else: ?>
          <div class="text-center p-3 border rounded bg-light">
            <img src="../uploads/licenses/<?php echo htmlspecialchars($r['license_document']); ?>" alt="License Scan" class="img-fluid rounded shadow-sm" style="max-height: 550px;">
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
