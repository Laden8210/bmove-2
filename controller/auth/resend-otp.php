<?php

require_once '../../config/config.php';
require_once '../../function/OTPGenerator.php';
require_once '../../function/SMSService.php';

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

// Must have a pending OTP session
if (!isset($_SESSION['pending_otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'No pending OTP verification. Please log in first.', 'http_code' => 401]);
    exit;
}

$otpGenerator = new OTPGenerator();

// Rate limit check
if ($otpGenerator->getOtpRequests() >= 5) {
    echo json_encode(['status' => 'error', 'message' => 'OTP request limit reached. Please try again later.', 'http_code' => 429]);
    exit;
}

// Generate new OTP
$otp = $otpGenerator->generateOTP();

$contactNumber = $_SESSION['pending_otp']['contact_number'] ?? '';

if (!empty($contactNumber)) {
    $smsService = new SMSService();
    $smsResult = $smsService->sendOTP($contactNumber, $otp);

    if (!$smsResult['success']) {
        error_log('SMS resend failed: ' . ($smsResult['error'] ?? 'Unknown error'));
        echo json_encode(['status' => 'error', 'message' => 'Failed to resend OTP. Please try again.', 'http_code' => 500]);
        exit;
    }
}

$maskedPhone = '';
if (!empty($contactNumber)) {
    $maskedPhone = str_repeat('*', max(0, strlen($contactNumber) - 4)) . substr($contactNumber, -4);
}

echo json_encode([
    'status' => 'success',
    'message' => 'A new OTP has been sent to your phone.',
    'masked_phone' => $maskedPhone,
    'http_code' => 200
]);

?>