<?php

require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
require_once '../../function/OTPGenerator.php';
require_once '../../function/Mailer.php';
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
if (!isset($_SESSION['pending_otp']) || !isset($_SESSION['pending_otp']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No pending OTP verification. Please log in first.', 'http_code' => 401]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);
$method = $request_body['method'] ?? 'email';

$otpGenerator = new OTPGenerator();

// Rate limit check
if ($otpGenerator->getOtpRequests() >= 5) {
    echo json_encode(['status' => 'error', 'message' => 'OTP request limit reached. Please try again later.', 'http_code' => 429]);
    exit;
}

// Fetch user details
$user_id = $_SESSION['pending_otp']['user_id'];
$stmt = $conn->prepare("SELECT email_address, contact_number FROM users WHERE uid = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    exit;
}

$user = $result->fetch_assoc();
$userEmail = $user['email_address'];
$userPhone = $user['contact_number'];

// Generate new OTP
$otp = $otpGenerator->generateOTP();

$maskedContact = '';

if ($method === 'sms') {
    $smsResult = ['success' => false];
    try {
        $smsService = new SMSService();
        $smsResult = $smsService->sendOTP($userPhone, $otp);
    } catch (Exception $smsEx) {
        error_log("SMS OTP resend failed: " . $smsEx->getMessage());
    }

    if (!$smsResult['success']) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send SMS. Please try again or use Email.', 'http_code' => 500]);
        exit;
    }

    $maskedContact = substr($userPhone, 0, 2) . str_repeat('*', max(0, strlen($userPhone) - 4)) . substr($userPhone, -2);
    $msg = "A new OTP has been sent via SMS.";
    $contactTarget = $userPhone;
} else {
    // Default to email
    if (!empty($userEmail)) {
        $mailer = new Mailer();
        $mailResult = $mailer->sendOtp($userEmail, $otp);

        if (!$mailResult['success']) {
            error_log('OTP email resend failed: ' . ($mailResult['error'] ?? 'Unknown error'));
            echo json_encode(['status' => 'error', 'message' => 'Failed to resend OTP via email. Please try again.', 'http_code' => 500]);
            exit;
        }
        
        $parts = explode('@', $userEmail);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        $maskedContact = $maskedName . '@' . $domain;
    }
    $msg = "A new OTP has been sent to your email.";
    $contactTarget = $userEmail;
}

// Log to database
try {
    $stmtLog = $conn->prepare("INSERT INTO otp_logs (user_id, contact_target, method, otp_code, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
    $stmtLog->bind_param("ssss", $user_id, $contactTarget, $method, $otp);
    $stmtLog->execute();
} catch (Exception $e) {
    error_log("Failed to log resend OTP: " . $e->getMessage());
}

echo json_encode([
    'status' => 'success',
    'message' => $msg,
    'masked_contact' => $maskedContact,
    'http_code' => 200
]);

?>