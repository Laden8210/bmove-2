<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');

require '../../vendor/autoload.php';
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

// Only admins can change statuses
if ($_SESSION['auth']['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action', 'http_code' => 403]);
    exit;
}

if (!isset($request_body['uid']) || empty($request_body['uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID (uid) is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['status']) || empty($request_body['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Status is required', 'http_code' => 400]);
    exit;
}

$uid = filter_var($request_body['uid'], FILTER_SANITIZE_STRING);
$status = filter_var($request_body['status'], FILTER_SANITIZE_STRING);

$valid_statuses = ['Active', 'Inactive', 'Archived'];

if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value', 'http_code' => 400]);
    exit;
}

// Don't allow an admin to archive themselves
if ($uid === $_SESSION['auth']['user_id'] && $status === 'Archived') {
    echo json_encode(['status' => 'error', 'message' => 'You cannot archive your own active session account', 'http_code' => 400]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE uid = ?");
$stmt->bind_param("ss", $status, $uid);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => "User account successfully marked as $status", 'http_code' => 200]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found or status is already the same', 'http_code' => 404]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update user status', 'http_code' => 500]);
}

$stmt->close();
$conn->close();
