<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get booking ID from query parameters
$booking_id = isset($_GET['booking_id']) ? filter_var($_GET['booking_id'], FILTER_SANITIZE_STRING) : '';

if (empty($booking_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing booking_id parameter']);
    exit;
}

try {
    // Get the latest driver location for the booking
    $stmt = $conn->prepare("
        SELECT 
            dl.driver_id,
            dl.booking_id,
            dl.latitude,
            dl.longitude,
            dl.accuracy,
            dl.speed,
            dl.heading,
            dl.altitude,
            dl.timestamp,
            u.full_name as driver_name,
            u.contact_number as driver_phone,
            b.pickup_location,
            b.dropoff_location,
            b.status as booking_status,
            b.last_location_update,
            dts.session_status,
            dts.started_at,
            dts.total_distance
        FROM driver_locations dl
        JOIN users u ON dl.driver_id = u.uid
        JOIN bookings b ON dl.booking_id = b.booking_id
        LEFT JOIN driver_tracking_sessions dts ON dl.driver_id = dts.driver_id 
            AND dl.booking_id = dts.booking_id 
            AND dts.session_status = 'active'
        WHERE dl.booking_id = ?
        ORDER BY dl.timestamp DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'No location data found for this booking']);
        exit;
    }
    
    $location = $result->fetch_assoc();
    
    // Check if location is recent (within last 5 minutes)
    $last_update = new DateTime($location['timestamp']);
    $now = new DateTime();
    $diff = $now->diff($last_update);
    $minutes_ago = $diff->i + ($diff->h * 60) + ($diff->d * 24 * 60);
    
    $is_recent = $minutes_ago <= 5;
    
    // Get location history for the last hour (for route display)
    $history_stmt = $conn->prepare("
        SELECT latitude, longitude, recorded_at as timestamp
        FROM location_history
        WHERE booking_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY recorded_at ASC
    ");
    $history_stmt->bind_param("s", $booking_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    
    $location_history = [];
    while ($row = $history_result->fetch_assoc()) {
        $location_history[] = [
            'lat' => floatval($row['latitude']),
            'lng' => floatval($row['longitude']),
            'timestamp' => $row['timestamp']
        ];
    }
    
    // Return location data
    echo json_encode([
        'status' => 'success',
        'data' => [
            'driver' => [
                'id' => $location['driver_id'],
                'name' => $location['driver_name'],
                'phone' => $location['driver_phone']
            ],
            'booking' => [
                'id' => $location['booking_id'],
                'status' => $location['booking_status'],
                'pickup_location' => $location['pickup_location'],
                'dropoff_location' => $location['dropoff_location']
            ],
            'location' => [
                'latitude' => floatval($location['latitude']),
                'longitude' => floatval($location['longitude']),
                'accuracy' => $location['accuracy'] ? floatval($location['accuracy']) : null,
                'speed' => $location['speed'] ? floatval($location['speed']) : null,
                'heading' => $location['heading'] ? floatval($location['heading']) : null,
                'altitude' => $location['altitude'] ? floatval($location['altitude']) : null,
                'timestamp' => $location['timestamp'],
                'is_recent' => $is_recent
            ],
            'tracking' => [
                'session_status' => $location['session_status'],
                'started_at' => $location['started_at'],
                'total_distance' => $location['total_distance'] ? floatval($location['total_distance']) : 0,
                'last_update' => $location['last_location_update']
            ],
            'route_history' => $location_history
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get location error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to get location data: ' . $e->getMessage()
    ]);
}
?>
