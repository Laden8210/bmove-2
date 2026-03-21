<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');

require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}

$action = $request_body['action'] ?? '';
$password = $request_body['password'] ?? '';

if (!in_array($action, ['deactivate', 'delete'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action', 'http_code' => 400]);
    exit;
}

if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password is required for confirmation', 'http_code' => 400]);
    exit;
}

$user_id = $_SESSION['auth']['user_id'];

// Fetch user and verify password
$stmt = $conn->prepare("SELECT password, account_type FROM users WHERE uid = ? AND is_deleted = 0");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found', 'http_code' => 404]);
    exit;
}

// Only customers can self-service deactivate/delete
if ($user['account_type'] !== 'customer') {
    echo json_encode(['status' => 'error', 'message' => 'This action is only available for customer accounts', 'http_code' => 403]);
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Incorrect password', 'http_code' => 401]);
    exit;
}

// Check for active bookings (in_transit or confirmed)
$stmt = $conn->prepare("SELECT COUNT(*) as active_count FROM bookings WHERE user_id = ? AND status IN ('confirmed', 'in_transit', 'pending')");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$active = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($active['active_count'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Cannot proceed. You have active or pending bookings. Please complete or cancel them first.', 'http_code' => 400]);
    exit;
}

if ($action === 'deactivate') {
    $stmt = $conn->prepare("UPDATE users SET account_status = 'Inactive' WHERE uid = ?");
    $stmt->bind_param("s", $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => 'Your account has been deactivated. Contact support to reactivate.', 'http_code' => 200]);
    } else {
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Failed to deactivate account', 'http_code' => 500]);
    }
} elseif ($action === 'delete') {
    $stmt = $conn->prepare("UPDATE users SET is_deleted = 1, account_status = 'Inactive' WHERE uid = ?");
    $stmt->bind_param("s", $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => 'Your account has been permanently deleted.', 'http_code' => 200]);
    } else {
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete account', 'http_code' => 500]);
    }
}

$conn->close();
