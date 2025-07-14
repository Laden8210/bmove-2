<?php

require_once '../../config/config.php';
require_once '../../function/UIDGenerator.php';

// Set session configuration BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost'); // Enable only in production
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}


if (!isset($request_body['vehicle_id']) || $request_body['vehicle_id'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: vehicle_id',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['pickup_location']) || $request_body['pickup_location'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: pickup_location',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['pickup_lat']) || $request_body['pickup_lat'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: pickup_lat',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['pickup_lng']) || $request_body['pickup_lng'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: pickup_lng',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['dropoff_location']) || $request_body['dropoff_location'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: dropoff_location',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['dropoff_lat']) || $request_body['dropoff_lat'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: dropoff_lat',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['dropoff_lng']) || $request_body['dropoff_lng'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: dropoff_lng',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['date']) || $request_body['date'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: date',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['time']) || $request_body['time'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: time',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['total_distance']) || $request_body['total_distance'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: total_distance',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['total_price']) || $request_body['total_price'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: total_price',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['total_weight']) || $request_body['total_weight'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: total_weight',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['items_count']) || $request_body['items_count'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: items_count',
        'http_code' => 400
    ]);
    exit;
}
if (!isset($request_body['payment_method']) || $request_body['payment_method'] === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required field: payment_method',
        'http_code' => 400
    ]);
    exit;
}

$booking_id = UIDGenerator::generateUUID();


$status = 'pending';



// validate each field each field
$vehicle_id = filter_var($request_body['vehicle_id'], FILTER_SANITIZE_STRING);
$pickup_location = filter_var($request_body['pickup_location'], FILTER_SANITIZE_STRING);
$pickup_lat = filter_var($request_body['pickup_lat'], FILTER_SANITIZE_STRING);
$pickup_lng = filter_var($request_body['pickup_lng'], FILTER_SANITIZE_STRING);
$dropoff_location = filter_var($request_body['dropoff_location'], FILTER_SANITIZE_STRING);
$dropoff_lat = filter_var($request_body['dropoff_lat'], FILTER_SANITIZE_STRING);
$dropoff_lng = filter_var($request_body['dropoff_lng'], FILTER_SANITIZE_STRING);
$date = filter_var($request_body['date'], FILTER_SANITIZE_STRING);
$time = filter_var($request_body['time'], FILTER_SANITIZE_STRING);
$total_distance = filter_var($request_body['total_distance'], FILTER_SANITIZE_STRING);
$total_price = filter_var($request_body['total_price'], FILTER_SANITIZE_STRING);
$total_weight = filter_var($request_body['total_weight'], FILTER_SANITIZE_STRING);
$items_count = filter_var($request_body['items_count'], FILTER_SANITIZE_STRING);
$payment_method = filter_var($request_body['payment_method'], FILTER_SANITIZE_STRING);
$notes = isset($request_body['notes']) ? filter_var($request_body['notes'], FILTER_SANITIZE_STRING) : '';
$user_id = $_SESSION['auth']['user_id'];

// check if empty each field
if (empty($vehicle_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a car first',
        'http_code' => 400
    ]);
    exit;
}

if (empty($pickup_location)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a pickup location',
        'http_code' => 400
    ]);
    exit;
}
if (empty($pickup_lat)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a pickup location',
        'http_code' => 400
    ]);
    exit;
}
if (empty($pickup_lng)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a pickup location',
        'http_code' => 400
    ]);
    exit;
}
if (empty($dropoff_location)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a dropoff location',
        'http_code' => 400
    ]);
    exit;
}
if (empty($dropoff_lat)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a dropoff location',
        'http_code' => 400
    ]);
    exit;
}
if (empty($dropoff_lng)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a dropoff location',
        'http_code' => 400
    ]);
    exit;
}
if (empty($date)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a date',
        'http_code' => 400
    ]);
    exit;
}
if (empty($time)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a time',
        'http_code' => 400
    ]);
    exit;
}
if (empty($total_distance)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a distance',
        'http_code' => 400
    ]);
    exit;
}
if (empty($total_price)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a price',
        'http_code' => 400
    ]);
    exit;
}
if (empty($total_weight)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a weight',
        'http_code' => 400
    ]);
    exit;
}
if (empty($items_count)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a count of items',
        'http_code' => 400
    ]);
    exit;
}
if (empty($payment_method)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please select a payment method',
        'http_code' => 400
    ]);
    exit;
}
if (empty($user_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login first',
        'http_code' => 400
    ]);
    exit;
}
if (empty($notes)) {
    $notes = '';
}

$vehicle_stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicleid = ? AND status = 'available'");
$vehicle_stmt->bind_param("s", $vehicle_id);
$vehicle_stmt->execute();
$vehicle_result = $vehicle_stmt->get_result();
$vehicle = $vehicle_result->fetch_assoc();

if (!$vehicle) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Selected vehicle is not available',
        'http_code' => 400
    ]);
    exit;
}
// Check if the vehicle is already booked for the selected date and time


$booking_stmt = $conn->prepare("SELECT * FROM bookings WHERE vehicle_id = ? AND date = ? AND time = ? AND status != 'completed'");
$booking_stmt->bind_param("sss", $vehicle_id, $date, $time);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();
if ($booking) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Selected vehicle is already booked for the selected date and time',
        'http_code' => 400
    ]);
    exit;
}



$stmt = $conn->prepare("
    INSERT INTO bookings (
        booking_id, user_id, vehicle_id, pickup_location, pickup_lat, pickup_lng,
        dropoff_location, dropoff_lat, dropoff_lng, date, time, total_distance,
        total_price, total_weight, items_count, status, payment_method, notes
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
");

$stmt->bind_param(
    "ssssssssssssssssss",
    $booking_id,
    $user_id,
    $vehicle_id,
    $pickup_location,
    $pickup_lat,
    $pickup_lng,
    $dropoff_location,
    $dropoff_lat,
    $dropoff_lng,
    $date,
    $time,
    $total_distance,
    $total_price,
    $total_weight,
    $items_count,
    $status,
    $payment_method,
    $notes
);



if ($stmt->execute()) {



    $payment_id = UIDGenerator::generateUUID();
    $payment_status = 'pending';
    $stmt = $conn->prepare("
        INSERT INTO payments (
            payment_id, booking_id, user_id, amount_due, amount_received,
            payment_method, payment_status, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $conn->error,
            'http_code' => 500
        ]);
        exit;
    }

    $amount_received = 0;

    $stmt->bind_param(
        "ssssssss",
        $payment_id,
        $booking_id,
        $user_id,
        $total_price,
        $amount_received,
        $payment_method,
        $payment_status,
        $user_id
    );

    if ($stmt->execute()) {
        // Payment record created successfully
        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $stmt->error,
            'http_code' => 500
        ]);
        exit;
    }


    echo json_encode([
        'status' => 'success',
        'message' => 'Booking created successfully',
        'booking_id' => $booking_id,
        'http_code' => 200
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $stmt->error,
        'http_code' => 500
    ]);
}
