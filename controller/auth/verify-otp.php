<?php

require_once '../../config/config.php';
require_once '../../function/OTPGenerator.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

// Check that there is a pending OTP session
if (!isset($_SESSION['pending_otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'No pending OTP verification. Please log in first.', 'http_code' => 401]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($request_body['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'OTP code is required.', 'http_code' => 400]);
    exit;
}

$inputOtp = trim($request_body['otp']);

$otpGenerator = new OTPGenerator();

// Check if OTP has expired
if (isset($_SESSION['otp_expiry']) && time() > $_SESSION['otp_expiry']) {
    echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new code.', 'http_code' => 410]);
    exit;
}

// Validate the OTP
if ($otpGenerator->validateOTP($inputOtp)) {
    // OTP is correct — promote pending session to full auth
    $pending = $_SESSION['pending_otp'];

    $_SESSION['auth'] = [
        'user_id' => $pending['user_id'],
        'role' => $pending['role'],
        'ip_address' => $pending['ip_address'],
        'user_agent' => $pending['user_agent']
    ];

    // Clean up pending state
    unset($_SESSION['pending_otp']);
    $otpGenerator->resetOTP();

    echo json_encode([
        'status' => 'success',
        'message' => 'Verification successful.',
        'user' => [
            'uid' => $pending['user_id'],
            'email' => $pending['email'] ?? null,
            'role' => $pending['role']
        ],
        'http_code' => 200
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid OTP code. Please try again.',
        'http_code' => 401
    ]);
}

?>