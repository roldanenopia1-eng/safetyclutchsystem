<?php require_once 'config.php'; $user = me(); $page = $page ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Safety Clutch System') ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body data-page="<?= e($page) ?>">
<header class="nav">
  <a class="brand" href="index.php"><span class="logo">⛨</span> Safety Clutch System</a>
  <nav>
    <?php if ($user): ?>
      <a href="<?= $user['role'] === 'driver' ? 'driver.php' : 'passenger.php' ?>">Dashboard</a>
      <span class="who"><?= e($user['name']) ?> (<?= e($user['role']) ?>)</span>
      <a class="btn small ghost" href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a class="btn small" href="register.php">Sign up</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
