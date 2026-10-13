<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
} else {
    header("Location: ../login.php");
}
exit();
?>
