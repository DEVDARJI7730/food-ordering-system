<?php
require_once 'includes/header.php';

// Handle Action Requests (Approve, Reject, Suspend, Reactivate, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['restaurant_id'])) {
    $action = $_POST['action'];
    $restaurant_id = (int)$_POST['restaurant_id'];
    $admin_id = $_SESSION['user_id'];

    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE restaurants SET status = 'Approved', rejection_reason = NULL, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$admin_id, $restaurant_id]);
            $_SESSION['success'] = "Restaurant #$restaurant_id has been approved successfully!";
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? '');
            if (empty($reason)) {
                $_SESSION['error'] = "Rejection reason is required.";
            } else {
                $stmt = $conn->prepare("UPDATE restaurants SET status = 'Rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$reason, $admin_id, $restaurant_id]);
                $_SESSION['success'] = "Restaurant #$restaurant_id has been rejected.";
            }
        } elseif ($action === 'suspend') {
            $stmt = $conn->prepare("UPDATE restaurants SET status = 'Suspended' WHERE id = ?");
            $stmt->execute([$restaurant_id]);
            $_SESSION['success'] = "Restaurant #$restaurant_id has been suspended.";
        } elseif ($action === 'reactivate') {
            $stmt = $conn->prepare("UPDATE restaurants SET status = 'Approved' WHERE id = ?");
            $stmt->execute([$restaurant_id]);
            $_SESSION['success'] = "Restaurant #$restaurant_id has been reactivated.";
        } elseif ($action === 'update_commission') {
            $commission_rate = isset($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : 10.00;
            if ($commission_rate < 0 || $commission_rate > 100) {
                $_SESSION['error'] = "Commission rate must be between 0% and 100%.";
            } else {
                $stmt = $conn->prepare("UPDATE restaurants SET commission_rate = ? WHERE id = ?");
                $stmt->execute([$commission_rate, $restaurant_id]);
                $_SESSION['success'] = "Commission rate for Restaurant #$restaurant_id updated to {$commission_rate}% successfully!";
            }
        } elseif ($action === 'delete') {
            // Fetch owner user id
            $o_stmt = $conn->prepare("SELECT owner_id, license_document, logo FROM restaurants WHERE id = ?");
            $o_stmt->execute([$restaurant_id]);
            $rest_row = $o_stmt->fetch();
            
            $stmt = $conn->prepare("DELETE FROM restaurants WHERE id = ?");
            $stmt->execute([$restaurant_id]);

            if ($rest_row && $rest_row['owner_id']) {
                $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'restaurant_owner'")->execute([$rest_row['owner_id']]);
            }
            $_SESSION['success'] = "Restaurant #$restaurant_id and associated owner account deleted successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Action failed: " . $e->getMessage();
    }
    header("Location: restaurants.php" . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

// KPI Counts
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'suspended' => 0
];
try {
    $counts_query = $conn->query("SELECT status, COUNT(*) as count FROM restaurants GROUP BY status");
    while ($row = $counts_query->fetch()) {
        $st = strtolower($row['status']);
        if (isset($stats[$st])) {
            $stats[$st] = (int)$row['count'];
        }
        $stats['total'] += (int)$row['count'];
    }
} catch (PDOException $e) {}

// Filtering and Search
$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT r.*, u.name as owner_account_name, u.email as owner_account_email
        FROM restaurants r 
        JOIN users u ON r.owner_id = u.id 
        WHERE 1=1";
$params = [];

if ($filter_status !== 'all' && in_array($filter_status, ['Pending', 'Approved', 'Rejected', 'Suspended'])) {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $sql .= " AND (r.restaurant_name LIKE ? OR r.owner_name LIKE ? OR r.license_number LIKE ? OR r.city LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY (r.status = 'Pending') DESC, r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="fw-bold mb-1">Restaurant Management</h3>
    <p class="text-muted mb-0">Review licenses, approve registrations, and oversee restaurant partners</p>
  </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-2 col-sm-6 col-6">
    <a href="restaurants.php" class="text-decoration-none">
      <div class="card border-0 shadow-sm-custom text-center p-3 <?php echo $filter_status === 'all' ? 'border border-2 border-primary' : ''; ?>" style="border-radius: 12px;">
        <div class="text-muted small fw-bold">TOTAL</div>
        <div class="fs-3 fw-bold text-dark"><?php echo $stats['total']; ?></div>
      </div>
    </a>
  </div>
  <div class="col-md-2 col-sm-6 col-6">
    <a href="restaurants.php?status=Pending" class="text-decoration-none">
      <div class="card border-0 shadow-sm-custom text-center p-3 bg-warning-subtle <?php echo $filter_status === 'Pending' ? 'border border-2 border-warning' : ''; ?>" style="border-radius: 12px;">
        <div class="text-warning-emphasis small fw-bold"><i class="fa-solid fa-hourglass-half me-1"></i>PENDING</div>
        <div class="fs-3 fw-bold text-warning-emphasis"><?php echo $stats['pending']; ?></div>
      </div>
    </a>
  </div>
  <div class="col-md-2 col-sm-6 col-6">
    <a href="restaurants.php?status=Approved" class="text-decoration-none">
      <div class="card border-0 shadow-sm-custom text-center p-3 bg-success-subtle <?php echo $filter_status === 'Approved' ? 'border border-2 border-success' : ''; ?>" style="border-radius: 12px;">
        <div class="text-success small fw-bold"><i class="fa-solid fa-circle-check me-1"></i>APPROVED</div>
        <div class="fs-3 fw-bold text-success"><?php echo $stats['approved']; ?></div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6 col-6">
    <a href="restaurants.php?status=Rejected" class="text-decoration-none">
      <div class="card border-0 shadow-sm-custom text-center p-3 bg-danger-subtle <?php echo $filter_status === 'Rejected' ? 'border border-2 border-danger' : ''; ?>" style="border-radius: 12px;">
        <div class="text-danger small fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>REJECTED</div>
        <div class="fs-3 fw-bold text-danger"><?php echo $stats['rejected']; ?></div>
      </div>
    </a>
  </div>
  <div class="col-md-3 col-sm-6 col-6">
    <a href="restaurants.php?status=Suspended" class="text-decoration-none">
      <div class="card border-0 shadow-sm-custom text-center p-3 bg-secondary-subtle <?php echo $filter_status === 'Suspended' ? 'border border-2 border-secondary' : ''; ?>" style="border-radius: 12px;">
        <div class="text-secondary small fw-bold"><i class="fa-solid fa-ban me-1"></i>SUSPENDED</div>
        <div class="fs-3 fw-bold text-secondary"><?php echo $stats['suspended']; ?></div>
      </div>
    </a>
  </div>
</div>

<!-- Filter and Search Bar -->
<div class="card border-0 shadow-sm-custom mb-4" style="border-radius: 12px;">
  <div class="card-body p-3">
    <form method="GET" action="restaurants.php" class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-0 bg-light" placeholder="Search by name, owner, license, city..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select border-0 bg-light">
          <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
          <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending Verification</option>
          <option value="Approved" <?php echo $filter_status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
          <option value="Rejected" <?php echo $filter_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
          <option value="Suspended" <?php echo $filter_status === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary rounded-pill px-4">Filter</button>
        <?php if (!empty($search) || $filter_status !== 'all'): ?>
          <a href="restaurants.php" class="btn btn-outline-secondary rounded-pill px-3">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Restaurants Table -->
<div class="card border-0 shadow-sm-custom" style="border-radius: 12px;">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">Restaurant</th>
            <th>Owner Contact</th>
            <th>License Details</th>
            <th>Commission</th>
            <th>Status</th>
            <th class="text-end pe-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($restaurants)): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="fa-solid fa-store-slash fs-1 d-block mb-3 opacity-50"></i>
                No restaurants found matching the selected criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($restaurants as $r): 
              $is_expired = strtotime($r['license_expiry_date']) < strtotime(date('Y-m-d'));
              $comm_rate = isset($r['commission_rate']) ? number_format((float)$r['commission_rate'], 2) : '10.00';
            ?>
              <tr>
                <td class="ps-4">
                  <div class="d-flex align-items-center gap-3">
                    <?php if (!empty($r['logo']) && file_exists(__DIR__ . '/../uploads/logos/' . $r['logo'])): ?>
                      <img src="../uploads/logos/<?php echo htmlspecialchars($r['logo']); ?>" alt="Logo" class="rounded-circle object-fit-cover shadow-sm" style="width: 44px; height: 44px;">
                    <?php else: ?>
                      <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">
                        <?php echo strtoupper(substr($r['restaurant_name'], 0, 2)); ?>
                      </div>
                    <?php endif; ?>
                    <div>
                      <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['restaurant_name']); ?></div>
                      <div class="text-muted small"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($r['city']) . ', ' . htmlspecialchars($r['state']); ?></div>
                    </div>
                  </div>
                </td>

                <td>
                  <div class="fw-medium"><?php echo htmlspecialchars($r['owner_name']); ?></div>
                  <div class="text-muted small"><?php echo htmlspecialchars($r['email']); ?> | <?php echo htmlspecialchars($r['phone']); ?></div>
                </td>

                <td>
                  <div class="font-monospace fw-bold text-dark small"><?php echo htmlspecialchars($r['license_number']); ?></div>
                  <div class="<?php echo $is_expired ? 'text-danger fw-bold' : 'text-muted'; ?> small">
                    Exp: <?php echo date('d M Y', strtotime($r['license_expiry_date'])); ?>
                    <?php if ($is_expired): ?><span class="badge bg-danger ms-1">EXPIRED</span><?php endif; ?>
                  </div>
                </td>

                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary px-2.5 py-1.5 fs-6 fw-bold rounded-pill font-monospace">
                      <?php echo $comm_rate; ?>%
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle p-1" style="width: 26px; height: 26px;" data-bs-toggle="modal" data-bs-target="#commissionModal<?php echo $r['id']; ?>" title="Edit Commission Rate">
                      <i class="fa-solid fa-pen" style="font-size: 11px;"></i>
                    </button>
                  </div>
                  <div class="text-muted small" style="font-size: 11px;">Per Order</div>
                </td>

                <td>
                  <?php if ($r['status'] === 'Approved'): ?>
                    <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i>Approved</span>
                  <?php elseif ($r['status'] === 'Pending'): ?>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i>Pending Review</span>
                  <?php elseif ($r['status'] === 'Rejected'): ?>
                    <span class="badge bg-danger px-3 py-2 rounded-pill" title="<?php echo htmlspecialchars($r['rejection_reason'] ?? ''); ?>"><i class="fa-solid fa-xmark me-1"></i>Rejected</span>
                  <?php elseif ($r['status'] === 'Suspended'): ?>
                    <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fa-solid fa-ban me-1"></i>Suspended</span>
                  <?php endif; ?>
                </td>

                <td class="text-end pe-4">
                  <div class="btn-group btn-group-sm">
                    <a href="verify-license.php?id=<?php echo $r['id']; ?>" class="btn btn-outline-primary" title="Verify License & Details">
                      <i class="fa-solid fa-magnifying-glass me-1"></i>Verify
                    </a>
                    
                    <?php if ($r['status'] === 'Pending' || $r['status'] === 'Rejected' || $r['status'] === 'Suspended'): ?>
                      <form method="POST" action="restaurants.php" class="d-inline" onsubmit="return confirm('Approve this restaurant?');">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="restaurant_id" value="<?php echo $r['id']; ?>">
                        <button type="submit" class="btn btn-success" title="Approve Restaurant">
                          <i class="fa-solid fa-check"></i>
                        </button>
                      </form>
                    <?php endif; ?>

                    <?php if ($r['status'] === 'Pending' || $r['status'] === 'Approved'): ?>
                      <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $r['id']; ?>" title="Reject">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    <?php endif; ?>

                    <?php if ($r['status'] === 'Approved'): ?>
                      <form method="POST" action="restaurants.php" class="d-inline" onsubmit="return confirm('Are you sure you want to suspend this restaurant?');">
                        <input type="hidden" name="action" value="suspend">
                        <input type="hidden" name="restaurant_id" value="<?php echo $r['id']; ?>">
                        <button type="submit" class="btn btn-outline-secondary" title="Suspend Restaurant">
                          <i class="fa-solid fa-ban"></i>
                        </button>
                      </form>
                    <?php endif; ?>

                    <form method="POST" action="restaurants.php" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently DELETE this restaurant and its owner account?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="restaurant_id" value="<?php echo $r['id']; ?>">
                      <button type="submit" class="btn btn-outline-danger" title="Delete Restaurant">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </div>

                  <!-- Commission Modal -->
                  <div class="modal fade text-start" id="commissionModal<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                      <div class="modal-content">
                        <form method="POST" action="restaurants.php">
                          <div class="modal-header">
                            <h6 class="modal-title fw-bold"><i class="fa-solid fa-percent me-2 text-primary"></i>Set Commission</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <input type="hidden" name="action" value="update_commission">
                            <input type="hidden" name="restaurant_id" value="<?php echo $r['id']; ?>">
                            <div class="mb-2">
                              <label class="form-label small fw-semibold">Commission Rate (%)</label>
                              <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control text-end fw-bold" value="<?php echo $comm_rate; ?>" required>
                                <span class="input-group-text">%</span>
                              </div>
                              <div class="form-text small text-muted">Platform fee deducted per order.</div>
                            </div>
                          </div>
                          <div class="modal-footer p-2">
                            <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Save</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  <!-- Reject Modal for this restaurant -->
                  <div class="modal fade text-start" id="rejectModal<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <form method="POST" action="restaurants.php">
                          <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Reject Restaurant</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <p>Rejecting application for: <strong><?php echo htmlspecialchars($r['restaurant_name']); ?></strong></p>
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="restaurant_id" value="<?php echo $r['id']; ?>">
                            <div class="mb-3">
                              <label for="rejection_reason_<?php echo $r['id']; ?>" class="form-label fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                              <textarea class="form-control" id="rejection_reason_<?php echo $r['id']; ?>" name="rejection_reason" rows="3" placeholder="e.g. Expired license document, illegible scan, mismatched business name..." required></textarea>
                              <div class="form-text text-muted">This reason will be visible to the restaurant owner so they can rectify the issue.</div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Rejection</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
