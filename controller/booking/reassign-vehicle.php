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

// GET: Fetch available vehicles of the same type for a booking
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $booking_id = $_GET['booking_id'] ?? '';
    if (empty($booking_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing booking_id', 'http_code' => 400]);
        exit;
    }

    // Get current booking's vehicle type
    $stmt = $conn->prepare("
        SELECT b.vehicle_id, v.type AS vehicle_type, v.name AS current_vehicle_name
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.vehicleid
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found', 'http_code' => 404]);
        exit;
    }

    // Fetch available vehicles of the same type (excluding current one)
    $stmt = $conn->prepare("
        SELECT v.vehicleid, v.name, v.platenumber, v.type, v.model, v.year, v.status,
               v.baseprice, v.rateperkm, v.totalcapacitykg,
               u.full_name AS driver_name
        FROM vehicles v
        LEFT JOIN users u ON v.driver_uid = u.uid
        WHERE v.type = ? AND v.vehicleid != ? AND v.status = 'available'
        ORDER BY v.name ASC
    ");
    $stmt->bind_param("ss", $booking['vehicle_type'], $booking['vehicle_id']);
    $stmt->execute();
    $available = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'current_vehicle' => $booking['current_vehicle_name'],
            'vehicle_type' => $booking['vehicle_type'],
            'recommendations' => $available
        ]
    ]);
    exit;
}

// POST: Perform the reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_body = json_decode(file_get_contents('php://input'), true);

    $booking_id = $request_body['booking_id'] ?? '';
    $new_vehicle_id = $request_body['new_vehicle_id'] ?? '';

    if (empty($booking_id) || empty($new_vehicle_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields', 'http_code' => 400]);
        exit;
    }

    // Verify booking exists and is not completed/cancelled
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND status NOT IN ('completed', 'cancelled')");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or already completed/cancelled', 'http_code' => 404]);
        exit;
    }

    // Verify new vehicle exists and is available
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicleid = ? AND status = 'available'");
    $stmt->bind_param("s", $new_vehicle_id);
    $stmt->execute();
    $newVehicle = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$newVehicle) {
        echo json_encode(['status' => 'error', 'message' => 'Selected vehicle is not available', 'http_code' => 400]);
        exit;
    }

    // Verify same vehicle type
    $stmt = $conn->prepare("SELECT v.type FROM vehicles v JOIN bookings b ON b.vehicle_id = v.vehicleid WHERE b.booking_id = ?");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $currentType = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($currentType && $currentType['type'] !== $newVehicle['type']) {
        echo json_encode(['status' => 'error', 'message' => 'New vehicle must be of the same type', 'http_code' => 400]);
        exit;
    }

    // Enforce 20-minute preparation buffer on the new vehicle's schedule
    $bookingData = $conn->prepare("SELECT date, time FROM bookings WHERE booking_id = ?");
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
        $gap_stmt->bind_param("ssssi", $new_vehicle_id, $bk['date'], $booking_id, $requested_datetime, $gap_minutes);
        $gap_stmt->execute();
        if ($gap_stmt->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => "Cannot reassign: the selected vehicle has another booking within the {$gap_minutes}-minute preparation buffer.", 'http_code' => 409]);
            exit;
        }
        $gap_stmt->close();
    }

    // Perform reassignment
    $stmt = $conn->prepare("UPDATE bookings SET vehicle_id = ? WHERE booking_id = ?");
    $stmt->bind_param("ss", $new_vehicle_id, $booking_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Vehicle reassigned successfully', 'http_code' => 200]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reassign vehicle', 'http_code' => 500]);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method', 'http_code' => 405]);
