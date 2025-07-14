<?php

require_once '../../config/config.php';
require_once '../../function/UIDGenerator.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost'); 
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



if (!isset($request_body['booking_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing booking_id field', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing status field', 'http_code' => 400]);
    exit;
}


if (!in_array($request_body['status'], ['confirmed', 'cancelled', 'completed'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value', 'http_code' => 400]);
    exit;
}


$booking_id = $request_body['booking_id'];

$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Booking not found', 'http_code' => 404]);
    exit;
}


$status = $request_body['status'];


if ($status === 'cancelled' || $status === 'completed') {
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND status = ?");
    $stmt->bind_param("ss", $booking_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Booking already in this status', 'http_code' => 400]);
        exit;
    }
}


if ($status === 'cancelled' && !isset($request_body['remarks'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing remarks field for cancelled status', 'http_code' => 400]);
    exit;
}



if ($status === 'cancelled') {
    $remarks = $request_body['remarks'];
    $stmt = $conn->prepare("UPDATE bookings SET status = ?, remarks = ? WHERE booking_id = ?");
    $stmt->bind_param("sss", $status, $remarks, $booking_id);
} else {
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
    $stmt->bind_param("ss", $status, $booking_id);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Booking updated successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update booking', 'http_code' => 500]);
}

$stmt->close();     