<?php

require_once '../../config/config.php';
require_once '../../function/UIDGenerator.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed', 'http_code' => 405]);
    exit;
}

// Check if there's a pending social registration
if (!isset($_SESSION['pending_social_registration'])) {
    echo json_encode(['status' => 'error', 'message' => 'No pending social registration found. Please try signing in with Google again.', 'http_code' => 400]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON', 'http_code' => 400]);
    exit;
}

$pending = $_SESSION['pending_social_registration'];
$email = $pending['email'];
$fullName = $pending['full_name'];
$socialProvider = $pending['social_provider'];
$socialProviderId = $pending['social_provider_id'];

// Validate username
$username = trim($request_body['username'] ?? '');
if (strlen($username) < 3 || strlen($username) > 20) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 20 characters', 'http_code' => 400]);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
    echo json_encode(['status' => 'error', 'message' => 'Username can only contain letters and numbers', 'http_code' => 400]);
    exit;
}

// Check if username is taken
$checkStmt = $conn->prepare("SELECT uid FROM users WHERE username = ?");
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username already taken', 'http_code' => 400]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Validate password
$password = $request_body['password'] ?? '';
$confirmPassword = $request_body['confirm_password'] ?? '';

if (strlen($password) < 8 || strlen($password) > 20) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be between 8 and 20 characters', 'http_code' => 400]);
    exit;
}
if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one uppercase letter', 'http_code' => 400]);
    exit;
}
if (!preg_match('/[a-z]/', $password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one lowercase letter', 'http_code' => 400]);
    exit;
}
if (!preg_match('/[0-9]/', $password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one number', 'http_code' => 400]);
    exit;
}
if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password must contain at least one special character', 'http_code' => 400]);
    exit;
}
if ($password !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match', 'http_code' => 400]);
    exit;
}

// Optional: contact number
$contactNumber = trim($request_body['contact_number'] ?? '');
if (!empty($contactNumber)) {
    if (!preg_match('/^09\d{9}$/', $contactNumber)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format. Must start with 09 followed by 9 digits.', 'http_code' => 400]);
        exit;
    }
    // Check if phone is taken
    $checkPhone = $conn->prepare("SELECT uid FROM users WHERE contact_number = ?");
    $checkPhone->bind_param("s", $contactNumber);
    $checkPhone->execute();
    if ($checkPhone->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number already taken', 'http_code' => 400]);
        $checkPhone->close();
        exit;
    }
    $checkPhone->close();
} else {
    $contactNumber = null;
}

// Check if email is already taken (race condition protection)
$checkEmail = $conn->prepare("SELECT uid FROM users WHERE email_address = ?");
$checkEmail->bind_param("s", $email);
$checkEmail->execute();
if ($checkEmail->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists. Please log in instead.', 'http_code' => 400]);
    $checkEmail->close();
    unset($_SESSION['pending_social_registration']);
    exit;
}
$checkEmail->close();

// Create the user
$uid = UIDGenerator::generateUUID();
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$accountType = 'customer';
$emailVerified = 1;

$insertStmt = $conn->prepare(
    "INSERT INTO users (uid, username, full_name, contact_number, email_address, password, account_type, email_verified, social_provider, social_provider_id) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$insertStmt->bind_param("sssssssiss", $uid, $username, $fullName, $contactNumber, $email, $hashedPassword, $accountType, $emailVerified, $socialProvider, $socialProviderId);

if (!$insertStmt->execute()) {
    error_log("Social registration INSERT failed: " . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Failed to create account: ' . $conn->error, 'http_code' => 500]);
    exit;
}
$insertStmt->close();

// Clear pending registration
unset($_SESSION['pending_social_registration']);

// Log the user in
session_regenerate_id(true);
$_SESSION['auth'] = [
    'user_id' => $uid,
    'role' => $accountType,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
];

echo json_encode([
    'status' => 'success',
    'message' => 'Account created and logged in successfully!',
    'user' => [
        'uid' => $uid,
        'email' => $email,
        'role' => $accountType
    ],
    'http_code' => 200
]);
