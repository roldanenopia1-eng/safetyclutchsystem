<?php
require_once 'config.php';
$user = require_role('passenger');
$title = 'Book a ride - Safety Clutch'; $page = 'passenger';
include 'header.php';
?>
<div class="two-col">
  <section class="card" id="book-card">
    <h2>Book a ride</h2>
    <div class="badge-line">Your discount: <strong id="my-discount">0%</strong> <span class="muted" id="trip-count"></span></div>
    <form id="book-form">
      <label>Pickup location <input name="pickup" required placeholder="e.g. Divisoria, Cagayan de Oro"></label>
      <label>Drop-off location <input name="dropoff" required placeholder="e.g. SM CDO Downtown Premier"></label>
      <label>Estimated distance (km) <input type="number" name="distance" id="distance" min="1" max="200" step="0.5" value="5" required></label>

      <div class="label">Vehicle type</div>
      <div class="vehicles">
        <label class="vcard"><input type="radio" name="vehicle" value="motorcycle" checked><span>🏍️<b>Motorcycle</b><small>₱30 + ₱8/km</small></span></label>
        <label class="vcard"><input type="radio" name="vehicle" value="car"><span>🚗<b>Car</b><small>₱50 + ₱14/km</small></span></label>
        <label class="vcard"><input type="radio" name="vehicle" value="van"><span>🚐<b>Van</b><small>₱80 + ₱20/km</small></span></label>
      </div>

      <div class="label">Payment method</div>
      <div class="vehicles two">
        <label class="vcard"><input type="radio" name="payment" value="cash" checked><span>💵<b>Cash</b><small>Pay the driver</small></span></label>
        <label class="vcard"><input type="radio" name="payment" value="gcash"><span>📱<b>GCash</b><small>Send to driver</small></span></label>
      </div>
      <label id="gcash-wrap" class="hidden">GCash reference no. <input name="gcash_ref" id="gcash_ref" maxlength="30" placeholder="13-digit reference number"></label>

      <div class="quote">
        <div><span>Fare</span><b id="q-fare">₱0.00</b></div>
        <div><span>Discount</span><b id="q-disc">-₱0.00</b></div>
        <div class="total"><span>Total</span><b id="q-total">₱0.00</b></div>
      </div>
      <div id="book-msg" class="alert hidden"></div>
      <button class="btn block" id="book-btn">Book now</button>
    </form>
  </section>

  <section>
    <div class="card" id="ride-card">
      <h2>Current ride</h2>
      <div id="ride-body" class="muted">No active ride. Book one to get picked up!</div>
    </div>
    <div class="card hidden" id="chat-card">
      <h3>Chat with your driver</h3>
      <div id="chat-log" class="chat-log"></div>
      <form id="chat-form" class="chat-form"><input id="chat-input" maxlength="300" placeholder="Type a message…" autocomplete="off"><button class="btn small">Send</button></form>
    </div>
  </section>
</div>

<section class="card">
  <h2>Trip history</h2>
  <div class="table-wrap"><table id="history"><thead><tr><th>#</th><th>Route</th><th>Driver</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead><tbody></tbody></table></div>
</section>

<!-- Rating modal -->
<div id="rate-modal" class="modal hidden">
  <div class="modal-box">
    <h3>Rate your driver</h3>
    <p id="rate-driver" class="muted"></p>
    <div id="star-pick" class="star-pick"><span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span></div>
    <p class="muted small">Rate how safely the driver drove.</p>
    <input id="rate-comment" maxlength="200" placeholder="Optional comment">
    <button class="btn block" id="rate-submit">Submit rating</button>
  </div>
</div>
<?php include 'footer.php'; ?>
