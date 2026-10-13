<?php
include_once 'includes/header.php';

$errors = [];
$cat_id = 0;
$cat_name = '';
$cat_desc = '';
$is_edit = false;

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("DELETE FROM catagories WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success'] = "Catagory deleted successfully!";
        header("Location: catagories.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Could not delete catagory: " . $e->getMessage();
        header("Location: catagories.php");
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $cat_id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM catagories WHERE id = ?");
        $stmt->execute([$cat_id]);
        $catagory = $stmt->fetch();
        if ($catagory) {
            $cat_name = $catagory['name'];
            $cat_desc = $catagory['description'];
            $is_edit = true;
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching catagory details.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_catagory'])) {
    $cat_name = trim($_POST['name']);
    $cat_desc = trim($_POST['description']);
    $cat_id = intval($_POST['cat_id'] ?? 0);

    if (empty($cat_name)) {
        $errors[] = "Catagory name is required.";
    }

    if (empty($errors)) {
        try {
            if ($cat_id > 0) {
                
                $stmt = $conn->prepare("UPDATE catagories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$cat_name, $cat_desc, $cat_id]);
                $_SESSION['success'] = "Catagory updated successfully!";
            } else {
                
                $stmt = $conn->prepare("INSERT INTO catagories (name, description) VALUES (?, ?)");
                $stmt->execute([$cat_name, $cat_desc]);
                $_SESSION['success'] = "Catagory added successfully!";
            }
            header("Location: catagories.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error saving catagory: " . $e->getMessage();
        }
    }
}

try {
    $catagories = $conn->query("SELECT * FROM catagories ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="fw-bold mb-0">Manage Food Catagories</h2>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger border-0 shadow-sm-custom">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row">
  
  <div class="col-lg-4 mb-4">
    <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
      <h5 class="fw-bold mb-3"><?php echo $is_edit ? 'Edit Catagory' : 'Add New Catagory'; ?></h5>
      
      <form action="catagories.php" method="POST">
        <?php if ($is_edit): ?>
          <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
        <?php endif; ?>
        
        <div class="mb-3">
          <label for="name" class="form-label">Catagory Name</label>
          <input type="text" class="form-control rounded-pill px-3" id="name" name="name" value="<?php echo htmlspecialchars($cat_name); ?>" required>
        </div>
        
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control px-3" id="description" name="description" rows="4" style="border-radius: 12px;"><?php echo htmlspecialchars($cat_desc); ?></textarea>
        </div>

        <div class="d-grid mt-4 gap-2">
          <button type="submit" name="save_catagory" class="btn btn-primary rounded-pill py-2.5 fw-bold">
            <i class="fa-solid fa-save me-2"></i><?php echo $is_edit ? 'Update Catagory' : 'Save Catagory'; ?>
          </button>
          <?php if ($is_edit): ?>
            <a href="catagories.php" class="btn btn-outline-secondary rounded-pill py-2.5 fw-bold">Cancel Edit</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm-custom p-4" style="border-radius: 16px;">
      <h5 class="fw-bold mb-3">Existing Catagories</h5>
      
      <?php if (count($catagories) > 0): ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="text-secondary fw-semibold">
              <tr>
                <th scope="col" style="width: 10%;">ID</th>
                <th scope="col" style="width: 25%;">Name</th>
                <th scope="col" style="width: 45%;">Description</th>
                <th scope="col" style="width: 20%;" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($catagories as $cat): ?>
                <tr>
                  <td class="fw-bold">#<?php echo $cat['id']; ?></td>
                  <td class="fw-semibold text-dark"><?php echo htmlspecialchars($cat['name']); ?></td>
                  <td class="text-secondary"><?php echo htmlspecialchars($cat['description']); ?></td>
                  <td class="text-end">
                    <a href="catagories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-light border rounded-pill px-2.5 me-1" title="Edit">
                      <i class="fa-solid fa-pen-to-square text-primary"></i>
                    </a>
                    <a href="catagories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-light border rounded-pill px-2.5 confirm-delete" data-message="Warning: Deleting this catagory will delete all food items associated with it. Do you want to proceed?" title="Delete">
                      <i class="fa-solid fa-trash-can text-danger"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-4">
          <i class="fa-solid fa-folder-open text-muted fs-1 mb-2"></i>
          <p class="text-secondary mb-0">No catagories found. Create one using the form on the left.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once 'includes/footer.php'; ?>
