<?php
require_once 'config.php';
header('Content-Type: application/json');

function out($d) { echo json_encode($d); exit; }
function fail($m, $code = 400) { http_response_code($code); out(['error' => $m]); }

$action = $_REQUEST['action'] ?? '';
$u = me();

// ---- SafeBot (works for guests too) ---------------------------------------
if ($action === 'chatbot') {
    $m = strtolower(trim($_POST['message'] ?? ''));
    $disc = ($u && $u['role'] === 'passenger') ? discount_pct($u['id']) : null;
    $r = "I'm SafeBot! I can help with booking, fares, discounts, payment, ratings, and contacting your driver. What would you like to know?";
    if (preg_match('/\b(hi|hello|hey|good (morning|afternoon|evening))\b/', $m)) $r = "Hello! 👋 I'm SafeBot. Ask me how to book a ride, about fares, discounts, payment, or driver ratings.";
    elseif (preg_match('/book|ride|travel|pick ?up/', $m)) $r = "To book: go to your passenger dashboard, enter pickup and drop-off, choose a vehicle and payment method, then press “Book now”. The first available online driver with that vehicle type will accept and come get you.";
    elseif (preg_match('/discount|promo|voucher|loyal/', $m)) $r = "Discounts depend on how often you ride: 5% after 5 completed trips, 10% after 10, and 15% after 20." . ($disc !== null ? " Your current discount is {$disc}%." : " Log in as a passenger to see yours.");
    elseif (preg_match('/gcash|cash|pay/', $m)) $r = "You can pay by cash (hand it to the driver) or GCash (send to the driver's contact number and enter the reference number when booking).";
    elseif (preg_match('/fare|price|cost|how much|rate per/', $m)) $r = "Fare = base + per-km rate: Motorcycle ₱30 + ₱8/km, Car ₱50 + ₱14/km, Van ₱80 + ₱20/km. Your loyalty discount is applied automatically.";
    elseif (preg_match('/rating|star|safe|review/', $m)) $r = "After every trip, passengers rate their driver from 1–5 stars based on how safely they drove. The average becomes the driver's safety badge, from “New Driver” up to “Elite Safe Driver” (4.5★+).";
    elseif (preg_match('/contact|call|phone|number|message|chat/', $m)) $r = "Once a driver accepts your booking, you'll see their contact number and a chat box on your dashboard. The driver also sees your number.";
    elseif (preg_match('/cancel/', $m)) $r = "You can cancel a booking from the “Current ride” panel while it's pending or before the trip starts.";
    elseif (preg_match('/driver|become|join|register|sign ?up/', $m)) $r = "To drive with us, sign up as a Driver with your vehicle type, plate number, and contact number. Go online to start receiving bookings.";
    elseif (preg_match('/thank/', $m)) $r = "You're welcome! Ride safe. 🚗";
    out(['reply' => $r]);
}

if (!$u) fail('Please log in.', 401);

try {
switch ($action) {

case 'quote': {
    $km = (float)($_GET['distance'] ?? 0);
    $v = $_GET['vehicle'] ?? 'motorcycle';
    if (!isset(RATES[$v])) fail('Invalid vehicle.');
    $fare = fare_calc($v, $km);
    $pct = discount_pct($u['id']);
    $disc = round($fare * $pct / 100, 2);
    out(['fare' => $fare, 'discount_pct' => $pct, 'discount' => $disc, 'total' => round($fare - $disc, 2)]);
}

case 'book': {
    if ($u['role'] !== 'passenger') fail('Only passengers can book.');
    if (q("SELECT id FROM bookings WHERE passenger_id=? AND status IN ('pending','accepted','ongoing')", [$u['id']])->fetch())
        fail('You already have an active ride.');
    $pickup = trim($_POST['pickup'] ?? ''); $drop = trim($_POST['dropoff'] ?? '');
    $km = (float)($_POST['distance'] ?? 0); $v = $_POST['vehicle'] ?? '';
    $pay = $_POST['payment'] ?? ''; $ref = trim($_POST['gcash_ref'] ?? '');
    if ($pickup === '' || $drop === '') fail('Enter pickup and drop-off.');
    if ($km <= 0 || $km > 200) fail('Distance must be between 1 and 200 km.');
    if (!isset(RATES[$v])) fail('Choose a vehicle type.');
    if (!in_array($pay, ['cash', 'gcash'])) fail('Choose a payment method.');
    if ($pay === 'gcash' && !preg_match('/^\d{8,20}$/', $ref)) fail('Enter a valid GCash reference number (digits only).');
    $fare = fare_calc($v, $km); $pct = discount_pct($u['id']);
    $total = round($fare - $fare * $pct / 100, 2);
    q('INSERT INTO bookings (passenger_id,pickup,dropoff,distance_km,vehicle_type,fare,discount_pct,total,payment_method,gcash_ref) VALUES (?,?,?,?,?,?,?,?,?,?)',
      [$u['id'], $pickup, $drop, $km, $v, $fare, $pct, $total, $pay, $pay === 'gcash' ? $ref : null]);
    out(['ok' => true]);
}

case 'passenger_state': {
    if ($u['role'] !== 'passenger') fail('Forbidden.', 403);
    $b = q("SELECT b.*, d.name dname, d.phone dphone, d.plate_no dplate, d.vehicle_type dvehicle, d.expertise dexp
            FROM bookings b LEFT JOIN users d ON d.id=b.driver_id
            WHERE b.passenger_id=? AND b.status IN ('pending','accepted','ongoing') ORDER BY b.id DESC LIMIT 1", [$u['id']])->fetch();
    if ($b && $b['driver_id']) $b['driver_stats'] = driver_stats($b['driver_id']);
    $unrated = q("SELECT b.id, d.name dname FROM bookings b JOIN users d ON d.id=b.driver_id
                  LEFT JOIN ratings r ON r.booking_id=b.id
                  WHERE b.passenger_id=? AND b.status='completed' AND r.id IS NULL ORDER BY b.id DESC LIMIT 1", [$u['id']])->fetch();
    $hist = q("SELECT b.id,b.pickup,b.dropoff,b.total,b.payment_method,b.status,d.name dname FROM bookings b
               LEFT JOIN users d ON d.id=b.driver_id WHERE b.passenger_id=? ORDER BY b.id DESC LIMIT 10", [$u['id']])->fetchAll();
    $done = (int)q("SELECT COUNT(*) FROM bookings WHERE passenger_id=? AND status='completed'", [$u['id']])->fetchColumn();
    out(['booking' => $b ?: null, 'unrated' => $unrated ?: null, 'history' => $hist, 'discount_pct' => discount_pct($u['id']), 'completed' => $done]);
}

case 'cancel': {
    $id = (int)($_POST['id'] ?? 0);
    $n = q("UPDATE bookings SET status='cancelled' WHERE id=? AND passenger_id=? AND status IN ('pending','accepted')", [$id, $u['id']])->rowCount();
    if (!$n) fail('This ride can no longer be cancelled.');
    out(['ok' => true]);
}

case 'rate': {
    $id = (int)($_POST['id'] ?? 0); $stars = (int)($_POST['stars'] ?? 0);
    $comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 200);
    if ($stars < 1 || $stars > 5) fail('Pick 1 to 5 stars.');
    $b = q("SELECT * FROM bookings WHERE id=? AND passenger_id=? AND status='completed'", [$id, $u['id']])->fetch();
    if (!$b) fail('Trip not found.');
    if (q('SELECT id FROM ratings WHERE booking_id=?', [$id])->fetch()) fail('Already rated.');
    q('INSERT INTO ratings (booking_id,driver_id,passenger_id,stars,comment) VALUES (?,?,?,?,?)', [$id, $b['driver_id'], $u['id'], $stars, $comment]);
    out(['ok' => true]);
}

// ---------------- Driver ----------------
case 'driver_state': {
    if ($u['role'] !== 'driver') fail('Forbidden.', 403);
    $active = q("SELECT b.*, p.name pname, p.phone pphone FROM bookings b JOIN users p ON p.id=b.passenger_id
                 WHERE b.driver_id=? AND b.status IN ('accepted','ongoing') ORDER BY b.id DESC LIMIT 1", [$u['id']])->fetch();
    $pending = [];
    if ($u['is_online'] && !$active) {
        $pending = q("SELECT b.id,b.pickup,b.dropoff,b.distance_km,b.total,b.payment_method,p.name pname
                      FROM bookings b JOIN users p ON p.id=b.passenger_id
                      WHERE b.status='pending' AND b.vehicle_type=? ORDER BY b.id ASC LIMIT 10", [$u['vehicle_type']])->fetchAll();
    }
    $earn = q("SELECT COALESCE(SUM(total),0) t, COUNT(*) n, COALESCE(SUM(CASE WHEN DATE(updated_at)=CURDATE() THEN total END),0) today
               FROM bookings WHERE driver_id=? AND status='completed'", [$u['id']])->fetch();
    $reviews = q("SELECT r.stars, r.comment, p.name pname FROM ratings r JOIN users p ON p.id=r.passenger_id
                  WHERE r.driver_id=? ORDER BY r.id DESC LIMIT 5", [$u['id']])->fetchAll();
    out(['online' => (int)$u['is_online'], 'active' => $active ?: null, 'pending' => $pending,
         'earn' => $earn, 'stats' => driver_stats($u['id']), 'reviews' => $reviews]);
}

case 'toggle_online': {
    if ($u['role'] !== 'driver') fail('Forbidden.', 403);
    $new = $u['is_online'] ? 0 : 1;
    q('UPDATE users SET is_online=? WHERE id=?', [$new, $u['id']]);
    out(['online' => $new]);
}

case 'accept': {
    if ($u['role'] !== 'driver') fail('Forbidden.', 403);
    if (!$u['is_online']) fail('Go online first.');
    if (q("SELECT id FROM bookings WHERE driver_id=? AND status IN ('accepted','ongoing')", [$u['id']])->fetch()) fail('Finish your current trip first.');
    $id = (int)($_POST['id'] ?? 0);
    $n = q("UPDATE bookings SET driver_id=?, status='accepted' WHERE id=? AND status='pending' AND vehicle_type=?", [$u['id'], $id, $u['vehicle_type']])->rowCount();
    if (!$n) fail('Another driver already took this booking.');
    out(['ok' => true]);
}

case 'start': {
    $id = (int)($_POST['id'] ?? 0);
    $n = q("UPDATE bookings SET status='ongoing' WHERE id=? AND driver_id=? AND status='accepted'", [$id, $u['id']])->rowCount();
    if (!$n) fail('Cannot start this trip.');
    out(['ok' => true]);
}

case 'complete': {
    $id = (int)($_POST['id'] ?? 0);
    $n = q("UPDATE bookings SET status='completed' WHERE id=? AND driver_id=? AND status='ongoing'", [$id, $u['id']])->rowCount();
    if (!$n) fail('Cannot complete this trip.');
    out(['ok' => true]);
}

// ---------------- Chat (passenger <-> driver) ----------------
case 'messages': {
    $id = (int)($_GET['booking'] ?? 0); $since = (int)($_GET['since'] ?? 0);
    $b = q('SELECT passenger_id, driver_id FROM bookings WHERE id=?', [$id])->fetch();
    if (!$b || !in_array($u['id'], [$b['passenger_id'], $b['driver_id']])) fail('Forbidden.', 403);
    $rows = q('SELECT id, sender_id, body, created_at FROM messages WHERE booking_id=? AND id>? ORDER BY id ASC', [$id, $since])->fetchAll();
    foreach ($rows as &$r) $r['mine'] = ((int)$r['sender_id'] === (int)$u['id']);
    out(['messages' => $rows]);
}

case 'send_message': {
    $id = (int)($_POST['booking'] ?? 0); $body = mb_substr(trim($_POST['body'] ?? ''), 0, 300);
    $b = q("SELECT passenger_id, driver_id, status FROM bookings WHERE id=?", [$id])->fetch();
    if (!$b || !in_array($u['id'], [$b['passenger_id'], $b['driver_id']])) fail('Forbidden.', 403);
    if (!in_array($b['status'], ['accepted', 'ongoing'])) fail('Chat is open only during an active trip.');
    if ($body === '') fail('Type a message.');
    q('INSERT INTO messages (booking_id,sender_id,body) VALUES (?,?,?)', [$id, $u['id'], $body]);
    out(['ok' => true]);
}

default: fail('Unknown action.');
}
} catch (Throwable $ex) {
    fail('Server error.', 500);
}
