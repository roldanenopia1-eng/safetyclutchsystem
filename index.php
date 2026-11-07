<?php
require_once 'config.php';
if ($u = me()) { header('Location: ' . ($u['role'] === 'driver' ? 'driver.php' : 'passenger.php')); exit; }
$title = 'Safety Clutch System'; $page = 'home';
include 'header.php';
?>
<section class="hero">
  <h1>Book a ride. A driver comes to you.</h1>
  <p>Safety Clutch System connects passengers with rated, verified drivers. Pay with GCash or cash and earn discounts the more you ride.</p>
  <div class="row">
    <a class="btn" href="register.php?role=passenger">I'm a passenger</a>
    <a class="btn ghost" href="register.php?role=driver">I'm a driver</a>
  </div>
</section>
<section class="grid3">
  <div class="card"><h3>📍 Instant booking</h3><p>Enter pickup and drop-off. The nearest online driver with your vehicle type accepts and picks you up.</p></div>
  <div class="card"><h3>⭐ Safe-driving ratings</h3><p>Every driver is rated after each trip. Ratings show their ability to drive safely.</p></div>
  <div class="card"><h3>🏷️ Loyalty discounts</h3><p>5% after 5 trips, 10% after 10, 15% after 20 completed trips.</p></div>
  <div class="card"><h3>📞 Contact each other</h3><p>Passenger and driver see each other's contact number once a booking is accepted, plus in-app chat.</p></div>
  <div class="card"><h3>💳 GCash or cash</h3><p>Choose how you pay for every trip.</p></div>
  <div class="card"><h3>🤖 SafeBot</h3><p>Ask the chatbot anytime about booking, fares, payment, or discounts.</p></div>
</section>
<?php include 'footer.php'; ?>
