<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$name = $email = $phone = $address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($address)) $errors[] = "Delivery address is required.";
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        try {
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email is already registered.";
            } else {
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                
                $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, 'customer')");
                $insert_stmt->execute([$name, $email, $hashed_password, $phone, $address]);

                $_SESSION['success'] = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

include_once 'includes/header.php';
?>

<div class="row justify-content-center my-5">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow border-0 p-4" style="border-radius: 16px;">
      <div class="text-center mb-4">
        <i class="fa-solid fa-user-plus text-primary fs-1 mb-2"></i>
        <h2 class="fw-bold">Create Account</h2>
        <p class="text-muted">Register to start ordering food online</p>
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

      <form action="register.php" method="POST" id="registerForm" class="needs-validation" novalidate>
        <div class="mb-3">
          <label for="name" class="form-label font-weight-bold">Full Name</label>
          <input type="text" class="form-control rounded-pill px-3" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" class="form-control rounded-pill px-3" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="mb-3">
          <label for="phone" class="form-label">Phone Number</label>
          <input type="text" class="form-control rounded-pill px-3" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="e.g. 9876543210" required>
        </div>
        <div class="mb-3">
          <label for="address" class="form-label">Delivery Address</label>
          <textarea class="form-control px-3" id="address" name="address" rows="3" style="border-radius: 12px;" required><?php echo htmlspecialchars($address); ?></textarea>
        </div>
        <div class="mb-3 row">
          <div class="col-md-6 mb-3 mb-md-0">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control rounded-start-pill px-3" id="password" name="password" required>
              <button class="btn btn-outline-secondary rounded-end-pill px-3 border-start-0" type="button" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')" title="Show / Hide Password" style="border-color: #dee2e6;">
                <i class="fa-solid fa-eye text-muted" id="togglePasswordIcon"></i>
              </button>
            </div>
          </div>
          <div class="col-md-6">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <div class="input-group">
              <input type="password" class="form-control rounded-start-pill px-3" id="confirm_password" name="confirm_password" required>
              <button class="btn btn-outline-secondary rounded-end-pill px-3 border-start-0" type="button" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPasswordIcon')" title="Show / Hide Password" style="border-color: #dee2e6;">
                <i class="fa-solid fa-eye text-muted" id="toggleConfirmPasswordIcon"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="d-grid mt-4">
          <button type="submit" class="btn btn-primary rounded-pill py-2.5 fw-bold"><i class="fa-solid fa-paper-plane me-2"></i>Sign Up</button>
        </div>
      </form>

      <div class="text-center mt-4">
        <p class="mb-0 text-muted">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login Here</a></p>
      </div>
    </div>
  </div>
</div>

<script>
function togglePasswordVisibility(inputId, iconId) {
    const pwdInput = document.getElementById(inputId);
    const pwdIcon = document.getElementById(iconId);
    if (!pwdInput) return;

    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        if (pwdIcon) {
            pwdIcon.classList.remove('fa-eye');
            pwdIcon.classList.add('fa-eye-slash');
        }
    } else {
        pwdInput.type = 'password';
        if (pwdIcon) {
            pwdIcon.classList.remove('fa-eye-slash');
            pwdIcon.classList.add('fa-eye');
        }
    }
}
</script>

<?php include_once 'includes/footer.php'; ?>
