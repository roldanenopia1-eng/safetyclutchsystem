<?php
require_once 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = q('SELECT * FROM users WHERE email=?', [strtolower(trim($_POST['email'] ?? ''))])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        header('Location: ' . ($u['role'] === 'driver' ? 'driver.php' : 'passenger.php')); exit;
    }
    $err = 'Wrong email or password.';
}
$title = 'Login - Safety Clutch'; $page = 'auth';
include 'header.php';
?>
<div class="card narrow">
  <h2>Welcome back</h2>
  <?php if ($err): ?><div class="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn block">Login</button>
  </form>
  <p class="muted">No account yet? <a href="register.php">Sign up</a></p>
</div>
<?php include 'footer.php'; ?>
