<?php
// Safety Clutch System - shared config & helpers
session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'safety_clutch';
$DB_USER = 'root';   // XAMPP default
$DB_PASS = '';       // XAMPP default (empty)

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    die('Database connection failed. Start MySQL in XAMPP and import database.sql. (' . htmlspecialchars($ex->getMessage()) . ')');
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function q($sql, $params = []) {
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st;
}

function me() {
    if (empty($_SESSION['uid'])) return null;
    return q('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null;
}

function require_role($role) {
    $u = me();
    if (!$u || $u['role'] !== $role) { header('Location: login.php'); exit; }
    return $u;
}

// ---- Business rules -------------------------------------------------------
const RATES = [ // base fare, per km
    'motorcycle' => [30, 8],
    'car'        => [50, 14],
    'van'        => [80, 20],
];

function fare_calc($vehicle, $km) {
    if (!isset(RATES[$vehicle])) return 0;
    [$base, $perKm] = RATES[$vehicle];
    return round($base + $perKm * max(0, $km), 2);
}

// Loyalty discount based on how many trips the passenger has completed
function discount_pct($passengerId) {
    $c = (int) q("SELECT COUNT(*) FROM bookings WHERE passenger_id=? AND status='completed'", [$passengerId])->fetchColumn();
    if ($c >= 20) return 15;
    if ($c >= 10) return 10;
    if ($c >= 5)  return 5;
    return 0;
}

function driver_stats($driverId) {
    $r = q('SELECT COUNT(*) n, AVG(stars) avg FROM ratings WHERE driver_id=?', [$driverId])->fetch();
    $avg = $r['n'] > 0 ? round((float)$r['avg'], 1) : null;
    return ['avg' => $avg, 'count' => (int)$r['n'], 'label' => rating_label($avg)];
}

// Rating symbolizes the driver's ability to drive safely
function rating_label($avg) {
    if ($avg === null) return 'New Driver';
    if ($avg >= 4.5) return 'Elite Safe Driver';
    if ($avg >= 4.0) return 'Safe Driver';
    if ($avg >= 3.0) return 'Good Driver';
    return 'Needs Improvement';
}
