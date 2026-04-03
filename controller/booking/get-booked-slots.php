<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['vehicle_id']) || !isset($_GET['date'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing vehicle_id or date']);
    exit;
}

$vehicle_id = $_GET['vehicle_id'];
$date = $_GET['date'];

try {
    // We fetch bookings for this vehicle on this date
    // We assume a booking takes a certain amount of time, but normally, bookings only have `booking_time` and `delivery_time` maybe?
    // Let's check what fields we have: booking_date, booking_time, (if there's estimated duration, we can use it, otherwise we'll just block out 1.5 hours per booking by default)
    $stmt = $conn->prepare("
        SELECT booking_time, status 
        FROM bookings 
        WHERE vehicle_id = ? 
        AND booking_date = ? 
        AND status NOT IN ('cancelled', 'declined', 'completed')
    ");
    $stmt->bind_param("ss", $vehicle_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = [
            'time' => $row['booking_time'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $booked_slots
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
