<?php
require_once 'config.php';
$err = '';
$role = $_POST['role'] ?? ($_GET['role'] ?? 'passenger');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? ''); $pass = $_POST['password'] ?? '';
    $vehicle = $_POST['vehicle_type'] ?? null; $plate = trim($_POST['plate_no'] ?? ''); $exp = trim($_POST['expertise'] ?? '');
    if (!in_array($role, ['passenger', 'driver'])) $err = 'Invalid role.';
    elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid name and email.';
    elseif (!preg_match('/^(09|\+639)\d{9}$/', $phone)) $err = 'Enter a valid PH mobile number (e.g. 09171234567).';
    elseif (strlen($pass) < 6) $err = 'Password must be at least 6 characters.';
    elseif ($role === 'driver' && (!in_array($vehicle, ['motorcycle', 'car', 'van']) || $plate === '')) $err = 'Drivers need a vehicle type and plate number.';
    elseif (q('SELECT id FROM users WHERE email=?', [$email])->fetch()) $err = 'Email is already registered.';
    else {
        q('INSERT INTO users (role,name,email,phone,password,vehicle_type,plate_no,expertise) VALUES (?,?,?,?,?,?,?,?)',
          [$role, $name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT),
           $role === 'driver' ? $vehicle : null, $role === 'driver' ? $plate : null, $role === 'driver' ? $exp : null]);
        $_SESSION['uid'] = $GLOBALS['pdo']->lastInsertId();
        header('Location: ' . ($role === 'driver' ? 'driver.php' : 'passenger.php')); exit;
    }
}
$title = 'Sign up - Safety Clutch'; $page = 'auth';
include 'header.php';
?>
<div class="card narrow">
  <h2>Create your account</h2>
  <?php if ($err): ?><div class="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post" id="reg">
    <label>I am a
      <select name="role" id="role">
        <option value="passenger" <?= $role === 'passenger' ? 'selected' : '' ?>>Passenger</option>
        <option value="driver" <?= $role === 'driver' ? 'selected' : '' ?>>Driver</option>
      </select></label>
    <label>Full name <input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
    <label>Email <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>Contact number <input name="phone" placeholder="09171234567" required value="<?= e($_POST['phone'] ?? '') ?>"></label>
    <label>Password <input type="password" name="password" minlength="6" required></label>
    <div id="driver-fields" class="<?= $role === 'driver' ? '' : 'hidden' ?>">
      <label>Vehicle type
        <select name="vehicle_type">
          <option value="motorcycle">Motorcycle</option><option value="car">Car</option><option value="van">Van</option>
        </select></label>
      <label>Plate number <input name="plate_no" value="<?= e($_POST['plate_no'] ?? '') ?>"></label>
      <label>Expertise <input name="expertise" placeholder="e.g. City driving, night driving, defensive driving" value="<?= e($_POST['expertise'] ?? '') ?>"></label>
    </div>
    <button class="btn block">Sign up</button>
  </form>
  <p class="muted">Already have an account? <a href="login.php">Login</a></p>
</div>
<?php include 'footer.php'; ?>
