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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID (uid) is required', 'http_code' => 400]);
    exit;
}
$uid = $request_body['uid'];

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found', 'http_code' => 404]);
    exit;
}

// Prepare update fields
$fields = [];
$params = [];
$types = "";

// Username
if (isset($request_body['username'])) {
    $username = filter_var($request_body['username'], FILTER_SANITIZE_STRING);
    if (strlen($username) < 3 || strlen($username) > 20) {
        echo json_encode(['status' => 'error', 'message' => 'Username must be between 3 and 20 characters', 'http_code' => 400]);
        exit;
    }
    // Check uniqueness except for current user
    $check = $conn->prepare("SELECT uid FROM users WHERE username = ? AND uid != ?");
    $check->bind_param("ss", $username, $uid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already taken', 'http_code' => 400]);
        exit;
    }
    $check->close();
    $fields[] = "username = ?";
    $params[] = $username;
    $types .= "s";
}

// Full name
if (isset($request_body['full_name'])) {
    $full_name = filter_var($request_body['full_name'], FILTER_SANITIZE_STRING);
    if (strlen($full_name) < 3 || strlen($full_name) > 50) {
        echo json_encode(['status' => 'error', 'message' => 'Full name must be between 3 and 50 characters', 'http_code' => 400]);
        exit;
    }
    $fields[] = "full_name = ?";
    $params[] = $full_name;
    $types .= "s";
}

// Contact number
if (isset($request_body['contact_number'])) {
    $phone_number = filter_var($request_body['contact_number'], FILTER_SANITIZE_STRING);
    if (!preg_match('/^\+?[0-9]{10,15}$/', $phone_number)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format', 'http_code' => 400]);
        exit;
    }
    // Check uniqueness except for current user
    $check = $conn->prepare("SELECT uid FROM users WHERE contact_number = ? AND uid != ?");
    $check->bind_param("ss", $phone_number, $uid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number already taken', 'http_code' => 400]);
        exit;
    }
    $check->close();
    $fields[] = "contact_number = ?";
    $params[] = $phone_number;
    $types .= "s";
}

// Email address
if (isset($request_body['email_address'])) {
    $email = filter_var($request_body['email_address'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format', 'http_code' => 400]);
        exit;
    }
    // Check uniqueness except for current user
    $check = $conn->prepare("SELECT uid FROM users WHERE email_address = ? AND uid != ?");
    $check->bind_param("ss", $email, $uid);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already taken', 'http_code' => 400]);
        exit;
    }
    $check->close();
    $fields[] = "email_address = ?";
    $params[] = $email;
    $types .= "s";
}

// Password (optional)
if (isset($request_body['password'])) {
    $password = trim($request_body['password']);
    // Only validate if password is not empty
    if ($password !== '') {
        if (strlen($password) < 8 || strlen($password) > 20) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be between 8 and 20 characters', 'http_code' => 400]);
            exit;
        }
        if (!isset($request_body['confirm_password']) || $password !== $request_body['confirm_password']) {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match', 'http_code' => 400]);
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $fields[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }
}

// Account type
if (isset($request_body['account_type'])) {
    $account_type = filter_var($request_body['account_type'], FILTER_SANITIZE_STRING);
    $valid_account_types = ['admin', 'driver'];
    if (!in_array($account_type, $valid_account_types)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid account type', 'http_code' => 400]);
        exit;
    }
    $fields[] = "account_type = ?";
    $params[] = $account_type;
    $types .= "s";
}

if (empty($fields)) {
    echo json_encode(['status' => 'error', 'message' => 'No fields to update', 'http_code' => 400]);
    exit;
}

// Build query
$query = "UPDATE users SET " . implode(", ", $fields) . " WHERE uid = ?";
$params[] = $uid;
$types .= "s";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'User updated successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating user: ' . $conn->error, 'http_code' => 500]);
}
$stmt->close();
$conn->close();
