<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost'); // Enable only in production
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');


require '../../vendor/autoload.php';
require_once '../../config/config.php';
require_once '../../function/Mailer.php';
require_once '../../function/OTPGenerator.php';


$otpGenerator = new OTPGenerator();
$mailer = new Mailer();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);


if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}


if (!isset($request_body['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required', 'http_code' => 400]);
    exit;
}


$email = filter_var($request_body['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format', 'http_code' => 400]);
    exit;
}

if($otpGenerator->getOtpRequests() >= 5) {
    echo json_encode(['status' => 'error', 'message' => 'OTP request limit exceeded. Please try again later.', 'http_code' => 429]);
    exit;
}


$otp = $otpGenerator->generateOTP();

$result = $mailer->sendOtp($email, $otp);
if ($result['success']) {
    echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => $result['error'], 'http_code' => 500]);
}
