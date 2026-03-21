<?php

require_once '../../config/config.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

if ($_SESSION['auth']['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized', 'http_code' => 403]);
    exit;
}

// Stats
$revenue = $bookings = $activeRentals = $totalVehicles = 0;
$vehicleStatus = ['available' => 0, 'in use' => 0, 'under maintenance' => 0, 'unavailable' => 0];

$result = $conn->query("SELECT SUM(amount_received) AS total_revenue FROM payments WHERE payment_status = 'paid'");
if ($result && $row = $result->fetch_assoc()) {
    $revenue = $row['total_revenue'] ?? 0;
}

// Admin gross profit
$totalExpensesAll = 0;
$expResult = $conn->query("SELECT SUM(amount) AS total_expenses FROM vehicle_expenses");
if ($expResult && $expRow = $expResult->fetch_assoc()) {
    $totalExpensesAll = $expRow['total_expenses'] ?? 0;
}
$netProfitAll = $revenue - $totalExpensesAll;
$adminGrossProfit = $netProfitAll * 0.60;

$result = $conn->query("SELECT COUNT(*) AS total_bookings FROM bookings");
if ($result && $row = $result->fetch_assoc()) {
    $bookings = $row['total_bookings'] ?? 0;
}

$result = $conn->query("SELECT COUNT(*) AS active_rentals FROM bookings WHERE status = 'in_progress'");
if ($result && $row = $result->fetch_assoc()) {
    $activeRentals = $row['active_rentals'] ?? 0;
}

$result = $conn->query("SELECT COUNT(*) AS total_vehicles FROM vehicles WHERE status != 'Inactive'");
if ($result && $row = $result->fetch_assoc()) {
    $totalVehicles = $row['total_vehicles'] ?? 0;
}

$result = $conn->query("SELECT status, COUNT(*) AS count FROM vehicles GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicleStatus[$row['status']] = $row['count'];
    }
}

// Recent Bookings with real data
$recentBookings = [];
$result = $conn->query("
    SELECT b.booking_id, u.full_name, v.name AS vehicle_name, b.date, b.time, b.status,
           b.total_distance, b.total_price
    FROM bookings b
    JOIN users u ON b.user_id = u.uid
    LEFT JOIN vehicles v ON b.vehicle_id = v.vehicleid
    ORDER BY b.created_at DESC
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
}

$conn->close();

echo json_encode([
    'status' => 'success',
    'data' => [
        'bookings' => $bookings,
        'activeRentals' => $activeRentals,
        'totalVehicles' => $totalVehicles,
        'adminGrossProfit' => $adminGrossProfit,
        'vehicleStatus' => $vehicleStatus,
        'recentBookings' => $recentBookings
    ]
]);
