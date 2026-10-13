<?php
include_once 'includes/header.php';

try {
    $customers = $conn->query("SELECT * FROM users WHERE role = 'customer' ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="fw-bold mb-0">Registered Customers</h2>
  <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
    Total: <?php echo count($customers); ?>
  </span>
</div>

<div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
  <h5 class="fw-bold mb-4">Customers Directory</h5>

  <?php if (count($customers) > 0): ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="text-secondary fw-semibold">
          <tr>
            <th scope="col" style="width: 5%;">ID</th>
            <th scope="col" style="width: 20%;">Name</th>
            <th scope="col" style="width: 25%;">Email</th>
            <th scope="col" style="width: 15%;">Phone</th>
            <th scope="col" style="width: 20%;">Address</th>
            <th scope="col" style="width: 15%;">Registered On</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $cust): ?>
            <tr>
              <td class="fw-bold">#<?php echo $cust['id']; ?></td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold text-uppercase me-2" style="width: 32px; height: 32px; font-size: 0.85rem;">
                    <?php echo substr($cust['name'], 0, 2); ?>
                  </div>
                  <span class="fw-semibold text-dark"><?php echo htmlspecialchars($cust['name']); ?></span>
                </div>
              </td>
              <td>
                <a href="mailto:<?php echo $cust['email']; ?>" class="text-decoration-none">
                  <?php echo htmlspecialchars($cust['email']); ?>
                </a>
              </td>
              <td class="text-secondary font-monospace"><?php echo htmlspecialchars($cust['phone']); ?></td>
              <td class="text-secondary text-sm"><?php echo htmlspecialchars($cust['address']); ?></td>
              <td><?php echo date('d M Y, h:i A', strtotime($cust['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="text-center py-4">
      <i class="fa-solid fa-users-slash text-muted fs-1 mb-2"></i>
      <p class="text-secondary mb-0">No registered customers found.</p>
    </div>
  <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
