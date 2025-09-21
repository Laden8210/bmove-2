<?php

require_once '../../config/config.php';
require_once '../../function/PayMongoService.php';
require_once '../../function/UIDGenerator.php';

// Set session configuration BEFORE starting session
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

// Validate required fields
$requiredFields = ['booking_id', 'amount'];
foreach ($requiredFields as $field) {
    if (empty($request_body[$field])) {
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field", 'http_code' => 400]);
        exit;
    }
}

$booking_id = filter_var($request_body['booking_id'], FILTER_SANITIZE_STRING);
$amount = floatval($request_body['amount']);

try {
    // Get booking details
    $stmt = $conn->prepare("
        SELECT b.*, u.full_name, u.email_address, u.contact_number, v.name as vehicle_name
        FROM bookings b
        JOIN users u ON b.user_id = u.uid
        LEFT JOIN vehicles v ON b.vehicle_id = v.vehicleid
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Check if payment already exists
    $stmt = $conn->prepare("SELECT payment_id FROM payments WHERE booking_id = ? AND payment_status = 'paid'");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Payment already completed for this booking');
    }
    
    // Initialize PayMongo service
    $paymongo = new PayMongoService();
    
    // Prepare booking data
    $bookingData = [
        'booking_id' => $booking['booking_id'],
        'vehicle_name' => $booking['vehicle_name'],
        'pickup_location' => $booking['pickup_location'],
        'dropoff_location' => $booking['dropoff_location'],
        'date' => $booking['date'],
        'time' => $booking['time']
    ];
    
    // Prepare user data
    $userData = [
        'full_name' => $booking['full_name'],
        'email_address' => $booking['email_address'],
        'contact_number' => $booking['contact_number']
    ];
    
    // Create description
    $description = "Vehicle booking from {$booking['pickup_location']} to {$booking['dropoff_location']} on {$booking['date']} at {$booking['time']}";
    
    // Create checkout session
    $response = $paymongo->createCheckoutSession($bookingData, $userData, $amount, $description);
    
    if (isset($response['data']['id'])) {
        $checkoutSessionId = $response['data']['id'];
        $checkoutUrl = $response['data']['attributes']['checkout_url'];
        
        // Create payment record
        $payment_id = UIDGenerator::generateUUID();
        $stmt = $conn->prepare("
            INSERT INTO payments (
                payment_id, booking_id, user_id, amount_due, amount_received,
                payment_method, payment_status, gateway_reference, gateway_url,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $payment_method = 'paymongo';
        $payment_status = 'pending';
        $amount_received = 0;
        $user_id = $_SESSION['auth']['user_id'];
        
        $stmt->bind_param(
            "sssddsssss",
            $payment_id,
            $booking_id,
            $user_id,
            $amount,
            $amount_received,
            $payment_method,
            $payment_status,
            $checkoutSessionId,
            $checkoutUrl,
            $user_id
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment session created successfully',
                'checkout_url' => $checkoutUrl,
                'payment_id' => $payment_id,
                'checkout_session_id' => $checkoutSessionId,
                'http_code' => 200
            ]);
        } else {
            throw new Exception('Failed to create payment record: ' . $stmt->error);
        }
    } else {
        throw new Exception('Failed to create checkout session');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Payment processing failed',
        'details' => $e->getMessage(),
        'http_code' => 500
    ]);
}
