<?php
require_once 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($_SESSION['user_role'] === 'restaurant_owner') {
        header("Location: restaurant/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['user_address'] = $user['address'];
                
                $_SESSION['success'] = "Welcome back, " . htmlspecialchars($user['name']) . "!";
                
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($user['role'] === 'restaurant_owner') {
                    $r_stmt = $conn->prepare("SELECT id, restaurant_name, status FROM restaurants WHERE owner_id = ?");
                    $r_stmt->execute([$user['id']]);
                    $rest = $r_stmt->fetch();
                    if ($rest) {
                        $_SESSION['restaurant_id'] = $rest['id'];
                        $_SESSION['restaurant_name'] = $rest['restaurant_name'];
                        $_SESSION['restaurant_status'] = $rest['status'];
                    }
                    header("Location: restaurant/dashboard.php");
                } else {
                    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                        header("Location: cart.php");
                    } else {
                        header("Location: index.php");
                    }
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include_once 'includes/header.php';
?>

<div class="row justify-content-center my-5">
  <div class="col-md-5 col-lg-4">
    <div class="card shadow border-0 p-4" style="border-radius: 16px;">
      <div class="text-center mb-4">
        <i class="fa-solid fa-lock text-primary fs-1 mb-2"></i>
        <h2 class="fw-bold">Welcome Back</h2>
        <p class="text-muted">Log in to your account</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger border-0 shadow-sm-custom" role="alert">
          <i class="fa-solid fa-circle-exclamation me-2"></i>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" class="form-control rounded-pill px-3" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input type="password" class="form-control rounded-start-pill px-3" id="password" name="password" required>
            <button class="btn btn-outline-secondary rounded-end-pill px-3 border-start-0" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')" title="Show / Hide Password" style="border-color: #dee2e6;">
              <i class="fa-solid fa-eye text-muted" id="togglePasswordIcon"></i>
            </button>
          </div>
        </div>
        <div class="d-grid mt-4">
          <button type="submit" class="btn btn-primary rounded-pill py-2.5 fw-bold"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</button>
        </div>
      </form>

      <div class="text-center mt-4">
        <p class="mb-1 text-muted">Customer? <a href="register.php" class="text-primary fw-bold text-decoration-none">Sign Up Here</a></p>
        <p class="mb-0 text-muted">Restaurant Owner? <a href="restaurant-register.php" class="text-success fw-bold text-decoration-none">Register Restaurant</a></p>
      </div>
      
      <!-- Demo Credentials Dropdown / Quick Autofill -->
      <div class="mt-4 p-3 bg-light rounded-4 border" style="font-size: 0.85rem;">
        <label for="demoAccountSelect" class="form-label fw-bold text-dark d-flex align-items-center justify-content-between mb-2">
          <span><i class="fa-solid fa-key text-warning me-1"></i>Demo Login Credentials:</span>
          <span class="badge bg-primary-subtle text-primary border rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">Quick Fill</span>
        </label>
        
        <select id="demoAccountSelect" class="form-select form-select-sm rounded-pill mb-2 border shadow-xs" onchange="autoFillDemoAccount(this.value)">
          <option value="" selected>-- Select Demo Account to Auto-Fill --</option>
          <optgroup label="👤 Customer Account">
            <option value="user" data-email="darjidev2504@gmail.com" data-pass="dev1234">Customer: Dev Darji (darjidev2504@gmail.com)</option>
          </optgroup>
          <optgroup label="🍽️ Restaurant Owners">
            <option value="komo" data-email="KbK@gmail.com" data-pass="KbK1234">KOMO BY KAFFA (KbK@gmail.com)</option>
            <option value="camilio" data-email="CC@gmail.com" data-pass="CC1234">Camilio (CC@gmail.com)</option>
            <option value="sams" data-email="sam@gmail.com" data-pass="sam1234">Sam's Pizza (sam@gmail.com)</option>
          </optgroup>
          <optgroup label="🛡️ Administrator">
            <option value="admin" data-email="admin@1234.com" data-pass="admin123">Admin: System Admin (admin@1234.com)</option>
          </optgroup>
        </select>

        <div id="demoCredentialsDetails" class="text-start mt-2 pt-2 border-top">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <small class="text-muted fw-semibold">Credentials Reference:</small>
            <a href="javascript:void(0)" class="text-primary text-decoration-none small" onclick="toggleCredentialsList()" id="toggleCredsBtn">
              <i class="fa-solid fa-chevron-down me-1"></i>Show All
            </a>
          </div>
          
          <div id="credentialsList" class="d-none mt-2">
            <div class="p-2 bg-white rounded-3 border mb-1.5 small d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-info-subtle text-info-emphasis rounded-pill me-1">Customer</span>
                <strong>darjidev2504@gmail.com</strong>
              </div>
              <code class="text-dark bg-light px-2 py-0.5 rounded">dev1234</code>
            </div>
            <div class="p-2 bg-white rounded-3 border mb-1.5 small d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill me-1">KOMO BY KAFFA</span>
                <strong>KbK@gmail.com</strong>
              </div>
              <code class="text-dark bg-light px-2 py-0.5 rounded">KbK1234</code>
            </div>
            <div class="p-2 bg-white rounded-3 border mb-1.5 small d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-success-subtle text-success-emphasis rounded-pill me-1">Camilio</span>
                <strong>CC@gmail.com</strong>
              </div>
              <code class="text-dark bg-light px-2 py-0.5 rounded">CC1234</code>
            </div>
            <div class="p-2 bg-white rounded-3 border mb-1.5 small d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill me-1">Sam's Pizza</span>
                <strong>sam@gmail.com</strong>
              </div>
              <code class="text-dark bg-light px-2 py-0.5 rounded">sam1234</code>
            </div>
            <div class="p-2 bg-white rounded-3 border small d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-dark text-white rounded-pill me-1">Admin</span>
                <strong>admin@1234.com</strong>
              </div>
              <code class="text-dark bg-light px-2 py-0.5 rounded">admin123</code>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function autoFillDemoAccount(type) {
    const select = document.getElementById('demoAccountSelect');
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || !selectedOption.dataset.email) return;

    const emailInput = document.getElementById('email');
    const passInput = document.getElementById('password');

    emailInput.value = selectedOption.dataset.email;
    passInput.value = selectedOption.dataset.pass;

    // Highlight fields briefly for visual feedback
    emailInput.classList.add('is-valid');
    passInput.classList.add('is-valid');
    setTimeout(() => {
        emailInput.classList.remove('is-valid');
        passInput.classList.remove('is-valid');
    }, 1200);
}

function toggleCredentialsList() {
    const list = document.getElementById('credentialsList');
    const btn = document.getElementById('toggleCredsBtn');
    if (list.classList.contains('d-none')) {
        list.classList.remove('d-none');
        btn.innerHTML = '<i class="fa-solid fa-chevron-up me-1"></i>Hide';
    } else {
        list.classList.add('d-none');
        btn.innerHTML = '<i class="fa-solid fa-chevron-down me-1"></i>Show All';
    }
}

function togglePasswordVisibility(inputId = 'password', iconId = 'togglePasswordIcon') {
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
