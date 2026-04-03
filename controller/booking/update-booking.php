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

// Enforce 20-minute preparation buffer when confirming a booking
if ($status === 'confirmed') {
    $bookingData = $conn->prepare("SELECT vehicle_id, date, time FROM bookings WHERE booking_id = ?");
    $bookingData->bind_param("s", $booking_id);
    $bookingData->execute();
    $bk = $bookingData->get_result()->fetch_assoc();
    $bookingData->close();

    if ($bk) {
        $gap_minutes = 20;
        $requested_datetime = $bk['date'] . ' ' . $bk['time'] . ':00';
        $gap_stmt = $conn->prepare("
            SELECT booking_id FROM bookings 
            WHERE vehicle_id = ? 
            AND date = ? 
            AND booking_id != ?
            AND status NOT IN ('completed', 'cancelled')
            AND ABS(TIMESTAMPDIFF(MINUTE, CONCAT(date, ' ', time, ':00'), ?)) < ?
        ");
        $gap_stmt->bind_param("ssssi", $bk['vehicle_id'], $bk['date'], $booking_id, $requested_datetime, $gap_minutes);
        $gap_stmt->execute();
        $gap_result = $gap_stmt->get_result();
        if ($gap_result->num_rows > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => "Cannot confirm: another booking on this vehicle is within the {$gap_minutes}-minute preparation buffer. Please reassign the vehicle or adjust the schedule.",
                'http_code' => 409
            ]);
            exit;
        }
        $gap_stmt->close();
    }
}


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


if ($status === 'cancelled') {
    $remarks = isset($request_body['remarks']) ? $request_body['remarks'] : null;
    $stmt = $conn->prepare("UPDATE bookings SET status = ?, remarks = ? WHERE booking_id = ?");
    $stmt->bind_param("sss", $status, $remarks, $booking_id);
} else {
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
    $stmt->bind_param("ss", $status, $booking_id);
}

if ($stmt->execute()) {
    // Send SMS & Email notification to customer
    $customer_stmt = $conn->prepare("SELECT u.contact_number, u.full_name, u.email_address FROM users u JOIN bookings b ON u.uid = b.user_id WHERE b.booking_id = ?");
    $customer_stmt->bind_param("s", $booking_id);
    $customer_stmt->execute();
    $customer = $customer_stmt->get_result()->fetch_assoc();
    $customer_stmt->close();

    if ($customer) {
        $notification = new NotificationService();
        $statusStr = ucfirst($status);
        
        $smsMessage = "BMove Express: Your booking #{$booking_id} has been {$status}.";
        if ($status === 'cancelled') {
            $smsMessage .= " If you have questions, please contact our support.";
        } else if ($status === 'confirmed') {
            $smsMessage .= " We will notify you once your driver is on the way.";
        }

        if (!empty($customer['contact_number'])) {
            $notification->sendSMS($customer['contact_number'], $smsMessage);
        }

        if (!empty($customer['email_address'])) {
            $emailBody = "<h3>Booking Status Update</h3>"
                . "<p>Dear {$customer['full_name']},</p>"
                . "<p>Your booking <strong>#{$booking_id}</strong> is now: <strong>{$statusStr}</strong>.</p>";
            
            if ($status === 'cancelled') {
                $emailBody .= "<p>We're sorry this booking was " . ($remarks ? "cancelled (Reason: {$remarks})" : "cancelled") . ". Feel free to book another vehicle at any time.</p>";
            } else if ($status === 'confirmed') {
                $emailBody .= "<p>Your vehicle is locked in. We will send you another update once your driver starts the trip!</p>";
            }

            $emailBody .= "<p>Thank you for choosing BMove Express!</p>";

            $notification->sendEmail(
                $customer['email_address'],
                "Booking Update: {$statusStr} - #{$booking_id}",
                $emailBody,
                $customer['full_name']
            );
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Booking updated successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update booking', 'http_code' => 500]);
}

$stmt->close();