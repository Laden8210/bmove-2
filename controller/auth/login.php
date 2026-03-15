<?php

require_once '../../config/config.php';
require_once '../../vendor/autoload.php';
require_once '../../function/OTPGenerator.php';
require_once '../../function/Mailer.php';

// Set session configuration BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost'); // Enable only in production
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

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['email']) || !isset($request_body['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required', 'http_code' => 400]);
    exit;
}

$email = $conn->real_escape_string($request_body['email']);
$password = $request_body['password'];

try {

    $stmt = $conn->prepare("SELECT uid, email_address, password, account_type, email_verified, contact_number FROM users WHERE email_address = ? or username = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password', 'http_code' => 401]);
        exit;
    }

    $user = $result->fetch_assoc();

    // Check if email is verified
    if (isset($user['email_verified']) && $user['email_verified'] == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Please verify your email before logging in. Check your inbox for the verification link.', 'http_code' => 403]);
        exit;
    }

    // if (!password_verify($password, $user['password'])) {
    //     echo json_encode(['status' => 'error', 'message' => 'Incorrect Password', 'http_code' => 401]);
    //     exit;
    // }

    session_regenerate_id(true);

    // --- 2FA: Partial auth — store pending state and send OTP via SMS ---
    $contactNumber = $user['contact_number'] ?? '';

    // Format phone number for SMS (ensure +63 prefix for PH numbers)
    $formattedPhone = $contactNumber;
    if (!empty($contactNumber)) {
        // Remove spaces, dashes, parentheses
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $contactNumber);
        // If starts with 0, replace with +63
        if (substr($cleanPhone, 0, 1) === '0') {
            $formattedPhone = '+63' . substr($cleanPhone, 1);
        } elseif (substr($cleanPhone, 0, 3) !== '+63') {
            $formattedPhone = '+63' . $cleanPhone;
        } else {
            $formattedPhone = $cleanPhone;
        }
    }

    // Store pending OTP session (NOT full auth yet)
    $_SESSION['pending_otp'] = [
        'user_id' => $user['uid'],
        'role' => $user['account_type'],
        'email' => $user['email_address'],
        'contact_number' => $formattedPhone,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Generate and send OTP
    $otpGenerator = new OTPGenerator();
    $otp = $otpGenerator->generateOTP();

    $userEmail = $user['email_address'] ?? '';
    $maskedEmail = '';
    if (!empty($userEmail)) {
        $parts = explode('@', $userEmail);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        $maskedEmail = $maskedName . '@' . $domain;

        $mailer = new Mailer();
        $mailResult = $mailer->sendOtp($userEmail, $otp);

        if (!$mailResult['success']) {
            error_log('OTP email send failed: ' . ($mailResult['error'] ?? 'Unknown error'));
        }
    }

    echo json_encode([
        'status' => 'otp_required',
        'message' => 'OTP sent to your registered email address.',
        'masked_contact' => $maskedEmail,
        'http_code' => 200
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'http_code' => 401
    ]);
    exit;
}

?>