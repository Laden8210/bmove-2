<?php

require_once '../../config/config.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
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

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['vehicleid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Vehicle ID is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Status is required', 'http_code' => 400]);
    exit;
}

$vehicleid = trim($request_body['vehicleid']);
$status = trim($request_body['status']);

if (empty($vehicleid)) {
    echo json_encode(['status' => 'error', 'message' => 'Vehicle ID is required', 'http_code' => 400]);
    exit;
}
if (empty($status)) {
    echo json_encode(['status' => 'error', 'message' => 'Status is required', 'http_code' => 400]);
    exit;
}

$valid_statuses = ['available', 'in use', 'under maintenance', 'unavailable'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value', 'http_code' => 400]);
    exit;
}

// update only the status
$stmt = $conn->prepare("UPDATE vehicles SET status=? WHERE vehicleid=?");
$stmt->bind_param("ss", $status, $vehicleid);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Truck status updated successfully', 'http_code' => 200]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No changes made or truck not found', 'http_code' => 404]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update truck status', 'http_code' => 500]);
}
$stmt->close();
$conn->close();
