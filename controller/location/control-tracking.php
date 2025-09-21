<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
$required_fields = ['driver_id', 'booking_id', 'action'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}

$driver_id = filter_var($input['driver_id'], FILTER_SANITIZE_STRING);
$booking_id = filter_var($input['booking_id'], FILTER_SANITIZE_STRING);
$action = filter_var($input['action'], FILTER_SANITIZE_STRING);

// Validate action
$valid_actions = ['start', 'stop', 'pause', 'resume'];
if (!in_array($action, $valid_actions)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action. Allowed: ' . implode(', ', $valid_actions)]);
    exit;
}

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
    
    // Start transaction
    $conn->begin_transaction();
    
    switch ($action) {
        case 'start':
            // Check if tracking is already active
            $check_stmt = $conn->prepare("
                SELECT id FROM driver_tracking_sessions 
                WHERE driver_id = ? AND booking_id = ? AND session_status = 'active'
            ");
            $check_stmt->bind_param("ss", $driver_id, $booking_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception('Tracking session already active');
            }
            
            // Create new tracking session
            $start_stmt = $conn->prepare("
                INSERT INTO driver_tracking_sessions (driver_id, booking_id, session_status, started_at)
                VALUES (?, ?, 'active', NOW())
            ");
            $start_stmt->bind_param("ss", $driver_id, $booking_id);
            
            if (!$start_stmt->execute()) {
                throw new Exception('Failed to start tracking session');
            }
            
            $session_id = $conn->insert_id;
            
            // Enable GPS tracking for booking
            $enable_stmt = $conn->prepare("
                UPDATE bookings 
                SET driver_location_enabled = TRUE, tracking_session_id = ?
                WHERE booking_id = ?
            ");
            $enable_stmt->bind_param("is", $session_id, $booking_id);
            
            if (!$enable_stmt->execute()) {
                throw new Exception('Failed to enable GPS tracking');
            }
            
            $message = 'GPS tracking started successfully';
            break;
            
        case 'stop':
            // Stop tracking session
            $stop_stmt = $conn->prepare("
                UPDATE driver_tracking_sessions 
                SET session_status = 'stopped', stopped_at = NOW()
                WHERE driver_id = ? AND booking_id = ? AND session_status = 'active'
            ");
            $stop_stmt->bind_param("ss", $driver_id, $booking_id);
            
            if (!$stop_stmt->execute()) {
                throw new Exception('Failed to stop tracking session');
            }
            
            // Disable GPS tracking for booking
            $disable_stmt = $conn->prepare("
                UPDATE bookings 
                SET driver_location_enabled = FALSE, tracking_session_id = NULL
                WHERE booking_id = ?
            ");
            $disable_stmt->bind_param("s", $booking_id);
            
            if (!$disable_stmt->execute()) {
                throw new Exception('Failed to disable GPS tracking');
            }
            
            $message = 'GPS tracking stopped successfully';
            break;
            
        case 'pause':
            // Pause tracking session
            $pause_stmt = $conn->prepare("
                UPDATE driver_tracking_sessions 
                SET session_status = 'paused'
                WHERE driver_id = ? AND booking_id = ? AND session_status = 'active'
            ");
            $pause_stmt->bind_param("ss", $driver_id, $booking_id);
            
            if (!$pause_stmt->execute()) {
                throw new Exception('Failed to pause tracking session');
            }
            
            $message = 'GPS tracking paused successfully';
            break;
            
        case 'resume':
            // Resume tracking session
            $resume_stmt = $conn->prepare("
                UPDATE driver_tracking_sessions 
                SET session_status = 'active'
                WHERE driver_id = ? AND booking_id = ? AND session_status = 'paused'
            ");
            $resume_stmt->bind_param("ss", $driver_id, $booking_id);
            
            if (!$resume_stmt->execute()) {
                throw new Exception('Failed to resume tracking session');
            }
            
            $message = 'GPS tracking resumed successfully';
            break;
    }
    
    // Commit transaction
    $conn->commit();
    
    // Get updated session info
    $session_stmt = $conn->prepare("
        SELECT * FROM driver_tracking_sessions 
        WHERE driver_id = ? AND booking_id = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    $session_stmt->bind_param("ss", $driver_id, $booking_id);
    $session_stmt->execute();
    $session = $session_stmt->get_result()->fetch_assoc();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'data' => [
            'action' => $action,
            'driver_id' => $driver_id,
            'booking_id' => $booking_id,
            'session' => $session,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("GPS tracking control error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to control GPS tracking: ' . $e->getMessage()
    ]);
}
?>
