<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required_fields = ['driver_id', 'booking_id', 'latitude', 'longitude'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}

$driver_id = filter_var($input['driver_id'], FILTER_SANITIZE_STRING);
$booking_id = filter_var($input['booking_id'], FILTER_SANITIZE_STRING);
$latitude = filter_var($input['latitude'], FILTER_VALIDATE_FLOAT);
$longitude = filter_var($input['longitude'], FILTER_VALIDATE_FLOAT);

// Validate coordinates
if ($latitude === false || $longitude === false) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid coordinates']);
    exit;
}

// Validate coordinate ranges
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Coordinates out of valid range']);
    exit;
}

// Optional fields
$accuracy = isset($input['accuracy']) ? filter_var($input['accuracy'], FILTER_VALIDATE_FLOAT) : null;
$speed = isset($input['speed']) ? filter_var($input['speed'], FILTER_VALIDATE_FLOAT) : null;
$heading = isset($input['heading']) ? filter_var($input['heading'], FILTER_VALIDATE_FLOAT) : null;
$altitude = isset($input['altitude']) ? filter_var($input['altitude'], FILTER_VALIDATE_FLOAT) : null;

try {
    // Check if driver is authorized for this booking
    $auth_stmt = $conn->prepare("
        SELECT b.*, u.account_type 
        FROM bookings b 
        JOIN users u ON b.user_id = u.uid 
        WHERE b.booking_id = ? AND b.status IN ('confirmed', 'in_transit')
    ");
    $auth_stmt->bind_param("s", $booking_id);
    $auth_stmt->execute();
    $booking = $auth_stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or not active']);
        exit;
    }
    
    // Check if GPS tracking is enabled for this booking
    if (!$booking['driver_location_enabled']) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'GPS tracking not enabled for this booking']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert current location
    $location_stmt = $conn->prepare("
        INSERT INTO driver_locations (driver_id, booking_id, latitude, longitude, accuracy, speed, heading, altitude)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $location_stmt->bind_param("ssdddddd", $driver_id, $booking_id, $latitude, $longitude, $accuracy, $speed, $heading, $altitude);
    
    if (!$location_stmt->execute()) {
        throw new Exception('Failed to insert location data');
    }
    
    // Update booking with latest location
    $update_stmt = $conn->prepare("
        UPDATE bookings 
        SET last_driver_lat = ?, last_driver_lng = ?, last_location_update = NOW()
        WHERE booking_id = ?
    ");
    $update_stmt->bind_param("dds", $latitude, $longitude, $booking_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update booking location');
    }
    
    // Update tracking session
    $session_stmt = $conn->prepare("
        UPDATE driver_tracking_sessions 
        SET last_location_update = NOW()
        WHERE driver_id = ? AND booking_id = ? AND session_status = 'active'
    ");
    $session_stmt->bind_param("ss", $driver_id, $booking_id);
    $session_stmt->execute();
    
    // Insert into location history (for analytics)
    $history_stmt = $conn->prepare("
        INSERT INTO location_history (driver_id, booking_id, latitude, longitude, accuracy, speed, heading, altitude)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $history_stmt->bind_param("ssdddddd", $driver_id, $booking_id, $latitude, $longitude, $accuracy, $speed, $heading, $altitude);
    $history_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Location updated successfully',
        'data' => [
            'driver_id' => $driver_id,
            'booking_id' => $booking_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("Location update error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update location: ' . $e->getMessage()
    ]);
}
?>
