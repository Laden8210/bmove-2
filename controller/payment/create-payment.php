<?php

require_once '../../config/config.php';
require_once '../../function/UIDGenerator.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', ($_SERVER['HTTP_HOST'] !== 'localhost') ? 1 : 0);
ini_set('session.use_strict_mode', 1);
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests allowed']);
    exit;
}

if (!isset($_SESSION['auth']['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON body']);
    exit;
}

// Validate required fields
$requiredFields = ['booking_id', 'user_id', 'amount_due', 'payment_method'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}



$checkPayment = $conn->prepare("SELECT payment_id FROM payments WHERE booking_id = ? AND payment_status = 'paid'");

$checkPayment->bind_param('s', $input['booking_id']);

$checkPayment->execute();

$result = $checkPayment->get_result();

if ($result->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payment already exists for this booking']);
    exit;
}

// Validate numeric values
if (!is_numeric($input['amount_due']) || $input['amount_due'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount due']);
    exit;
}

$amount_received = $input['amount_received'] ?? 0;
if (!is_numeric($amount_received) || $amount_received < 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount received']);
    exit;
}

// Prepare data
$payment_id = UIDGenerator::generateUUID();
$booking_id = $conn->real_escape_string($input['booking_id']);
$user_id = $conn->real_escape_string($input['user_id']);
$amount_due = (float)$input['amount_due'];
$amount_received = (float)$amount_received;
$payment_method = $conn->real_escape_string($input['payment_method']);

// Calculate payment status
if ($amount_received >= $amount_due) {
    $payment_status = 'paid';
} elseif ($amount_received > 0) {
    $payment_status = 'partial';
} else {
    $payment_status = 'pending';
}

// Additional fields
$notes = $input['notes'] ?? null;
$gateway_reference = $input['gateway_reference'] ?? null;
$gateway_url = $input['gateway_url'] ?? null;
$receipt_number = $input['receipt_number'] ?? null;
$paid_at = in_array($payment_status, ['paid', 'partial']) ? date('Y-m-d H:i:s') : null;
$created_by = $_SESSION['auth']['user_id'];
$updated_by = $created_by;

try {
    // Check if booking exists
    $checkBooking = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id = ?");
    $checkBooking->bind_param('s', $booking_id);
    $checkBooking->execute();
    if (!$checkBooking->get_result()->num_rows) {
        throw new Exception('Booking not found');
    }

    // Prepare SQL (remove change_amount from insert)
    $sql = "INSERT INTO payments (
        payment_id, booking_id, user_id, amount_due, amount_received, payment_method, payment_status,
        gateway_reference, gateway_url, receipt_number, paid_at, notes, created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL preparation failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'sssddsssssssss',
        $payment_id,
        $booking_id,
        $user_id,
        $amount_due,
        $amount_received,
        $payment_method,
        $payment_status,
        $gateway_reference,
        $gateway_url,
        $receipt_number,
        $paid_at,
        $notes,
        $created_by,
        $updated_by
    );

    if (!$stmt->execute()) {
        throw new Exception('Execution failed: ' . $stmt->error);
    }

    // Get generated change_amount from database
    $result = $conn->query("SELECT change_amount FROM payments WHERE payment_id = '$payment_id'");
    $change_amount = $result->fetch_assoc()['change_amount'];

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment recorded successfully',
        'payment_id' => $payment_id,
        'change_amount' => number_format($change_amount, 2),
        'http_code' => 200
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'details' => $e->getMessage(),
        'http_code' => 404
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($checkBooking)) $checkBooking->close();
    $conn->close();
}