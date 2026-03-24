<?php

require_once '../../config/config.php';
require_once '../../function/UIDGenerator.php';
require_once '../../function/NotificationService.php';

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



$booking_id = $request_body['booking_id'];

$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Booking not found', 'http_code' => 404]);
    exit;
}


// check if the booking is already completed

$booking = $result->fetch_assoc();
if ($booking['status'] === 'completed') {
    echo json_encode(['status' => 'error', 'message' => 'Booking already completed', 'http_code' => 400]);
    exit;
}

// check if the booking is already cancelled

if ($booking['status'] === 'cancelled') {
    echo json_encode(['status' => 'error', 'message' => 'Booking already cancelled', 'http_code' => 400]);
    exit;
}

// payment checking removed here as well

$status = 'completed';


$stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
$stmt->bind_param("ss", $status, $booking_id);


if ($stmt->execute()) {
    // Update vehicle status
    if (isset($booking['vehicle_id'])) {
        $veh_stmt = $conn->prepare("UPDATE vehicles SET status = 'available' WHERE vehicleid = ?");
        $veh_stmt->bind_param("s", $booking['vehicle_id']);
        $veh_stmt->execute();
    }

    // Send SMS notification to customer
    $customer_stmt = $conn->prepare("SELECT u.contact_number, u.full_name, u.email_address FROM users u WHERE u.uid = ?");
    $customer_stmt->bind_param("s", $booking['user_id']);
    $customer_stmt->execute();
    $customer = $customer_stmt->get_result()->fetch_assoc();
    $customer_stmt->close();

    if ($customer) {
        $notification = new NotificationService();
        $smsMessage = "BMove Express: Your trip for Booking #{$booking_id} has been completed! "
            . "Thank you for using our service. "
            . "Please rate your experience from your dashboard.";

        if (!empty($customer['contact_number'])) {
            $notification->sendSMS($customer['contact_number'], $smsMessage);
        }

        if (!empty($customer['email_address'])) {
            $notification->sendEmail(
                $customer['email_address'],
                "Trip Completed - Booking #{$booking_id}",
                "<h3>Your trip is complete!</h3>"
                . "<p>Dear {$customer['full_name']},</p>"
                . "<p>Your trip for <strong>Booking #{$booking_id}</strong> has been completed successfully.</p>"
                . "<p><strong>From:</strong> {$booking['pickup_location']}</p>"
                . "<p><strong>To:</strong> {$booking['dropoff_location']}</p>"
                . "<p>We'd love to hear about your experience. Please leave a rating and comment from your dashboard.</p>"
                . "<p>Thank you for choosing BMove Express!</p>",
                $customer['full_name']
            );
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Booking updated successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update booking', 'http_code' => 500]);
}

$stmt->close();
