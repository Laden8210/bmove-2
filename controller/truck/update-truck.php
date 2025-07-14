<?php

require_once '../../config/config.php';
require_once '../../function/UIDGenerator.php';

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

if (!isset($request_body['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Name is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['platenumber'])) {
    echo json_encode(['status' => 'error', 'message' => 'Plate number is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['totalcapacitykg'])) {
    echo json_encode(['status' => 'error', 'message' => 'Total capacity is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Status is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['baseprice'])) {
    echo json_encode(['status' => 'error', 'message' => 'Base price is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['rateperkm'])) {
    echo json_encode(['status' => 'error', 'message' => 'Rate per km is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Type is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['model'])) {
    echo json_encode(['status' => 'error', 'message' => 'Model is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['year'])) {
    echo json_encode(['status' => 'error', 'message' => 'Year is required', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['driver_uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Driver is required', 'http_code' => 400]);
    exit;
}

// Validate inputs
$vehicleid = trim($request_body['vehicleid']);
$name = trim($request_body['name']);
$platenumber = trim($request_body['platenumber']);
$totalcapacitykg = trim($request_body['totalcapacitykg']);
$status = trim($request_body['status']);
$baseprice = trim($request_body['baseprice']);
$rateperkm = trim($request_body['rateperkm']);
$type = trim($request_body['type']);
$model = trim($request_body['model']);
$year = trim($request_body['year']);
$driver_uid = trim($request_body['driver_uid']);

if (empty($vehicleid)) {
    echo json_encode(['status' => 'error', 'message' => 'Vehicle ID is required', 'http_code' => 400]);
    exit;
}
if (empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'Name is required', 'http_code' => 400]);
    exit;
}
if (empty($platenumber)) {
    echo json_encode(['status' => 'error', 'message' => 'Plate number is required', 'http_code' => 400]);
    exit;
}
if (empty($totalcapacitykg)) {
    echo json_encode(['status' => 'error', 'message' => 'Total capacity is required', 'http_code' => 400]);
    exit;
}
if (empty($status)) {
    echo json_encode(['status' => 'error', 'message' => 'Status is required', 'http_code' => 400]);
    exit;
}
if (empty($baseprice)) {
    echo json_encode(['status' => 'error', 'message' => 'Base price is required', 'http_code' => 400]);
    exit;
}
if (empty($rateperkm)) {
    echo json_encode(['status' => 'error', 'message' => 'Rate per km is required', 'http_code' => 400]);
    exit;
}
if (empty($type)) {
    echo json_encode(['status' => 'error', 'message' => 'Type is required', 'http_code' => 400]);
    exit;
}
if (empty($model)) {
    echo json_encode(['status' => 'error', 'message' => 'Model is required', 'http_code' => 400]);
    exit;
}
if (empty($year)) {
    echo json_encode(['status' => 'error', 'message' => 'Year is required', 'http_code' => 400]);
    exit;
}

if (!is_numeric($totalcapacitykg) || !is_numeric($baseprice) || !is_numeric($rateperkm) || !is_numeric($year)) {
    echo json_encode(['status' => 'error', 'message' => 'Total capacity, base price, rate per km, and year must be numeric', 'http_code' => 400]);
    exit;
}

if ($totalcapacitykg < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Total capacity must be greater than or equal to 0', 'http_code' => 400]);
    exit;
}

if ($baseprice < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Base price must be greater than or equal to 0', 'http_code' => 400]);
    exit;
}

if ($rateperkm < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Rate per km must be greater than or equal to 0', 'http_code' => 400]);
    exit;
}

if ($baseprice > 1000000) {
    echo json_encode(['status' => 'error', 'message' => 'Base price must be less than 1,000,000', 'http_code' => 400]);
    exit;
}

if ($rateperkm > 1000) {
    echo json_encode(['status' => 'error', 'message' => 'Rate per km must be less than 1,000', 'http_code' => 400]);
    exit;
}

if ($year < 1900 || $year > date("Y")) {
    echo json_encode(['status' => 'error', 'message' => 'Year must be between 1900 and the current year', 'http_code' => 400]);
    exit;
}

$valid_types = [
    "Flatbed", "Box Truck", "Refrigerated", "Tanker", "Dump Truck",
    "Car Carrier", "Lowboy", "Curtainside", "Logging Truck", "Livestock Truck",
    "Van", "Car", "Pickup", "Mini Truck", "Panel Truck", "Step Van",
    "Box Van", "Chiller Van", "Container Truck", "Other"];
if (!in_array($type, $valid_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Vehicle type', 'http_code' => 400]);
    exit;
}

// check name if it is already in use by another vehicle
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE name = ? AND vehicleid != ?");
$stmt->bind_param("ss", $name, $vehicleid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Truck with this name already exists', 'http_code' => 409]);
    exit;
}

// check plate number if it is already in use by another vehicle
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE platenumber = ? AND vehicleid != ?");
$stmt->bind_param("ss", $platenumber, $vehicleid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Truck with this plate number already exists', 'http_code' => 409]);
    exit;
}


$valid_statuses = ['available', 'in use', 'under maintenance', 'unavailable'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value', 'http_code' => 400]);
    exit;
}

// update the vehicle
$stmt = $conn->prepare("UPDATE vehicles SET name=?, platenumber=?, totalcapacitykg=?, status=?, baseprice=?, rateperkm=?, type=?, model=?, year=?, driver_uid=? WHERE vehicleid=?");
$stmt->bind_param("sssssdsssss", $name, $platenumber, $totalcapacitykg, $status, $baseprice, $rateperkm, $type, $model, $year, $driver_uid, $vehicleid);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Truck updated successfully', 'http_code' => 200]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No changes made or truck not found', 'http_code' => 404]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update truck', 'http_code' => 500]);
}
$stmt->close();
$conn->close();
