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

    // For customer accounts, require SMS OTP verification before granting access
    if ($user['account_type'] === 'customer') {
        require_once '../../function/SMSService.php';

        $otpGenerator = new OTPGenerator();
        $otp = $otpGenerator->generateOTP();

        // Store pending auth in session (not full auth yet)
        $_SESSION['pending_otp'] = [
            'user_id' => $user['uid'],
            'role' => $user['account_type'],
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        // Send OTP via SMS
        $smsResult = ['success' => false];
        try {
            $smsService = new SMSService();
            $smsResult = $smsService->sendOTP($user['contact_number'], $otp);
        } catch (Exception $smsEx) {
            error_log("SMS OTP send failed: " . $smsEx->getMessage());
        }

        // Fallback: also send OTP via email
        if (!$smsResult['success']) {
            $mailer->sendOtp($user['email_address'], $otp);
        }

        // Mask the contact number for display (e.g., 09***...***89)
        $contact = $user['contact_number'];
        $masked = substr($contact, 0, 2) . str_repeat('*', max(0, strlen($contact) - 4)) . substr($contact, -2);

        echo json_encode([
            'status' => 'otp_required',
            'message' => 'OTP verification required. A code has been sent to your phone.',
            'masked_contact' => $masked,
            'http_code' => 200
        ]);
    } else {
        // Admin and driver accounts: direct login without OTP
        $_SESSION['auth'] = [
            'user_id' => $user['uid'],
            'role' => $user['account_type'],
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => [
                'uid' => $user['uid'],
                'email' => $user['email_address'],
                'role' => $user['account_type']
            ],
            'http_code' => 200
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'http_code' => 401
    ]);
    exit;
}

?>