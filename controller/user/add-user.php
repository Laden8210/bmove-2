<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');


require '../../vendor/autoload.php';
require_once '../../config/config.php';


require_once '../../function/UIDGenerator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);


if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}


// user name,phone number, full name, email, password, confirm password, account type
if (!isset($request_body['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Username is required', 'http_code' => 400]);
    exit;
}
$username = filter_var($request_body['username'], FILTER_SANITIZE_STRING);
if (strlen($username) < 3 || strlen($username) > 20) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 20 characters', 'http_code' => 400]);
    exit;
}


// CHECK IF USERNAME IS ALREADY TAKEN

$check_username = $conn->prepare("SELECT * FROM users WHERE username = ?");
$check_username->bind_param("s", $username);
$check_username->execute();

$result_username = $check_username->get_result();
if ($result_username->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username already taken', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['full_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Full name is required', 'http_code' => 400]);
    exit;
}

$full_name = filter_var($request_body['full_name'], FILTER_SANITIZE_STRING);

if (strlen($full_name) < 3 || strlen($full_name) > 50) {
    echo json_encode(['status' => 'error', 'message' => 'Full name must be between 3 and 50 characters', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['contact_number'])) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number is required', 'http_code' => 400]);
    exit;
}

$phone_number = filter_var($request_body['contact_number'], FILTER_SANITIZE_STRING);

if (!preg_match('/^\+?[0-9]{10,15}$/', $phone_number)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format', 'http_code' => 400]);
    exit;
}

// CHECK IF PHONE NUMBER IS ALREADY TAKEN

$check_phone_number = $conn->prepare("SELECT * FROM users WHERE contact_number = ?");

$check_phone_number->bind_param("s", $phone_number);

$check_phone_number->execute();

$result_phone_number = $check_phone_number->get_result();

if ($result_phone_number->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Phone number already taken', 'http_code' => 400]);
    exit;
}



if (!isset($request_body['email_address'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required', 'http_code' => 400]);
    exit;
}

$email = filter_var($request_body['email_address'], FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format', 'http_code' => 400]);
    exit;
}


// CHECK IF EMAIL IS ALREADY TAKEN

$check_email = $conn->prepare("SELECT * FROM users WHERE email_address = ?");

$check_email->bind_param("s", $email);

$check_email->execute();

$result_email = $check_email->get_result();

if ($result_email->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already taken', 'http_code' => 400]);
    exit;
}


if (!isset($request_body['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Password is required', 'http_code' => 400]);
    exit;
}

$password = filter_var($request_body['password'], FILTER_SANITIZE_STRING);

if (strlen($password) < 8 || strlen($password) > 20) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be between 8 and 20 characters', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['confirm_password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Confirm password is required', 'http_code' => 400]);
    exit;
}

$confirm_password = filter_var($request_body['confirm_password'], FILTER_SANITIZE_STRING);

if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match', 'http_code' => 400]);
    exit;
}

// account type 

if (!isset($request_body['account_type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Account type is required', 'http_code' => 400]);
    exit;
}

$account_type = filter_var($request_body['account_type'], FILTER_SANITIZE_STRING);

$valid_account_types = ['admin', 'driver'];

if (!in_array($account_type, $valid_account_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid account type', 'http_code' => 400]);
    exit;
}



// HASH PASSWORD
$hashed_password = password_hash($password, PASSWORD_BCRYPT);


// INSERT USER INTO DATABASE

$uid = UIDGenerator::generateUUID();
$insert_user = $conn->prepare("INSERT INTO users (uid, username, full_name, contact_number, email_address, password, account_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
$insert_user->bind_param("sssssss", $uid, $username, $full_name, $phone_number, $email, $hashed_password, $account_type);
if (!$insert_user) {
    echo json_encode(['status' => 'error', 'message' => 'Error preparing statement: ' . $conn->error, 'http_code' => 500]);
    exit;
}


if ($insert_user->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'User registered successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error registering user: ' . $conn->error, 'http_code' => 500]);
}
$insert_user->close();
$conn->close();