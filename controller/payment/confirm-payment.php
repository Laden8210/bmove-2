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

// Validate required fields only booking id

$requiredFields = ['booking_id'];

foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}

// update payment  status of  payment 

$updatePayment = $conn->prepare("UPDATE payments SET payment_status = 'paid' WHERE booking_id = ?");
$updatePayment->bind_param('s', $input['booking_id']);

if (!$updatePayment->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update payment status']);
    exit;
}else{
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Payment status updated successfully', 'http_code' => 200]);
    exit;
}



