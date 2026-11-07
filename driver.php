<?php
require_once 'config.php';
$user = require_role('driver');
$title = 'Driver dashboard - Safety Clutch'; $page = 'driver';
include 'header.php';
?>
<div class="two-col">
  <section>
    <div class="card">
      <h2>Driver dashboard</h2>
      <div class="profile">
        <div><b><?= e($user['name']) ?></b><br><span class="muted"><?= e(ucfirst($user['vehicle_type'])) ?> · <?= e($user['plate_no']) ?> · 📞 <?= e($user['phone']) ?></span></div>
        <div id="my-rating" class="rating-pill">—</div>
      </div>
      <?php if ($user['expertise']): ?><p class="muted small">Expertise: <?= e($user['expertise']) ?></p><?php endif; ?>
      <div class="stats">
        <div><span>Today</span><b id="earn-today">₱0.00</b></div>
        <div><span>Total earned</span><b id="earn-total">₱0.00</b></div>
        <div><span>Trips</span><b id="trips-done">0</b></div>
      </div>
      <button class="btn block" id="online-btn">Go online</button>
    </div>

    <div class="card" id="pending-card">
      <h3>Available bookings <span class="muted small">(<?= e($user['vehicle_type']) ?> requests)</span></h3>
      <div id="pending-list" class="muted">Go online to see passengers waiting for a ride.</div>
    </div>
  </section>

  <section>
    <div class="card" id="active-card">
      <h2>Current trip</h2>
      <div id="active-body" class="muted">No active trip.</div>
    </div>
    <div class="card hidden" id="chat-card">
      <h3>Chat with passenger</h3>
      <div id="chat-log" class="chat-log"></div>
      <form id="chat-form" class="chat-form"><input id="chat-input" maxlength="300" placeholder="Type a message…" autocomplete="off"><button class="btn small">Send</button></form>
    </div>
  </section>
</div>

<section class="card">
  <h2>Recent reviews</h2>
  <div id="reviews" class="muted">No reviews yet.</div>
</section>
<?php include 'footer.php'; ?>
