<?php

require_once '../../config/config.php';
require_once '../../function/UIDGenerator.php';
require_once '../../function/OTPGenerator.php';
require_once '../../function/SMSService.php';
require_once '../../function/Mailer.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'http_code' => 405]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON', 'http_code' => 400]);
    exit;
}

$provider = $request_body['provider'] ?? '';

if (!in_array($provider, ['google', 'facebook'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid provider', 'http_code' => 400]);
    exit;
}

$email = null;
$fullName = null;

try {
    if ($provider === 'google') {
        $idToken = $request_body['id_token'] ?? '';
        if (empty($idToken)) {
            echo json_encode(['status' => 'error', 'message' => 'Google token required', 'http_code' => 400]);
            exit;
        }

        // Verify Google ID token
        $googleClientId = getenv('GOOGLE_CLIENT_ID');
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Google token', 'http_code' => 401]);
            exit;
        }

        $payload = json_decode($response, true);
        if (!$payload || ($payload['aud'] ?? '') !== $googleClientId) {
            echo json_encode(['status' => 'error', 'message' => 'Token audience mismatch', 'http_code' => 401]);
            exit;
        }

        $email = $payload['email'] ?? null;
        $fullName = $payload['name'] ?? '';

    } elseif ($provider === 'facebook') {
        $accessToken = $request_body['access_token'] ?? '';
        if (empty($accessToken)) {
            echo json_encode(['status' => 'error', 'message' => 'Facebook access token required', 'http_code' => 400]);
            exit;
        }

        // Verify Facebook access token and get user data
        $url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . urlencode($accessToken);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Facebook token', 'http_code' => 401]);
            exit;
        }

        $payload = json_decode($response, true);
        $email = $payload['email'] ?? null;
        $fullName = $payload['name'] ?? '';
    }

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Could not retrieve email from ' . $provider . '. Please ensure your email is visible.', 'http_code' => 400]);
        exit;
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT uid, email_address, account_type, contact_number, full_name FROM users WHERE email_address = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // Auto-register as customer
        $uid = UIDGenerator::generateUUID();
        $username = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]) . rand(100, 999);
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $accountType = 'customer';
        $emailVerified = 1;

        $insertStmt = $conn->prepare("INSERT INTO users (uid, username, full_name, email_address, password, account_type, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssssssi", $uid, $username, $fullName, $email, $randomPassword, $accountType, $emailVerified);

        if (!$insertStmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create account', 'http_code' => 500]);
            exit;
        }
        $insertStmt->close();

        $user = [
            'uid' => $uid,
            'email_address' => $email,
            'account_type' => $accountType,
            'contact_number' => null,
            'full_name' => $fullName
        ];
    }

    session_regenerate_id(true);

    // For customer accounts with a phone number, require OTP
    if ($user['account_type'] === 'customer' && !empty($user['contact_number'])) {
        $otpGenerator = new OTPGenerator();
        $otp = $otpGenerator->generateOTP();

        $_SESSION['pending_otp'] = [
            'user_id' => $user['uid'],
            'role' => $user['account_type'],
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        $smsResult = ['success' => false];
        try {
            $smsService = new SMSService();
            $smsResult = $smsService->sendOTP($user['contact_number'], $otp);
        } catch (Exception $smsEx) {
            error_log("SMS OTP send failed: " . $smsEx->getMessage());
        }

        if (!$smsResult['success']) {
            $mailer = new Mailer();
            $mailer->sendOtp($user['email_address'], $otp);
        }

        $contact = $user['contact_number'];
        $masked = substr($contact, 0, 2) . str_repeat('*', max(0, strlen($contact) - 4)) . substr($contact, -2);

        echo json_encode([
            'status' => 'otp_required',
            'message' => 'OTP sent to your phone for verification.',
            'masked_contact' => $masked,
            'http_code' => 200
        ]);
    } else {
        // Direct login (no phone registered yet, or admin/driver)
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
    error_log("Social login error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed. Please try again.', 'http_code' => 500]);
}
