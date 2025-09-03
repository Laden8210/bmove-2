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
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST']);
    exit;
}

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}


$name = trim($_POST['name'] ?? '');
$platenumber = trim($_POST['platenumber'] ?? '');
$totalcapacitykg = trim($_POST['totalcapacitykg'] ?? '');
$status = trim($_POST['status'] ?? '');
$baseprice = trim($_POST['baseprice'] ?? '');
$rateperkm = trim($_POST['rateperkm'] ?? '');
$type = trim($_POST['type'] ?? '');
$model = trim($_POST['model'] ?? '');
$year = trim($_POST['year'] ?? '');
$driver_uid = trim($_POST['driver_uid'] ?? '');


if (!$name || !$platenumber || !$totalcapacitykg || !$status || !$baseprice || !$rateperkm || !$type || !$model || !$year || !$driver_uid) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}


if (!is_numeric($totalcapacitykg) || !is_numeric($baseprice) || !is_numeric($rateperkm) || !is_numeric($year)) {
    echo json_encode(['status' => 'error', 'message' => 'Numeric fields must be valid numbers']);
    exit;
}


if ($year < 1900 || $year > date("Y")) {
    echo json_encode(['status' => 'error', 'message' => 'Year must be between 1900 and the current year']);
    exit;
}


$stmt = $conn->prepare("SELECT * FROM vehicles WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Truck name already exists']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM vehicles WHERE platenumber = ?");
$stmt->bind_param("s", $platenumber);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Plate number already exists']);
    exit;
}


$imagePath = null;
if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../uploads/vehicles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $ext = pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('vehicle_', true) . '.' . strtolower($ext);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $targetPath)) {
        $imagePath = 'uploads/vehicles/' . $filename; 
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
        exit;
    }
}

$uid = UIDGenerator::generateUUID();
$stmt = $conn->prepare("INSERT INTO vehicles (vehicleid, name, platenumber, totalcapacitykg, status, baseprice, rateperkm, type, model, year, driver_uid, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssissssssss", $uid, $name, $platenumber, $totalcapacitykg, $status, $baseprice, $rateperkm, $type, $model, $year, $driver_uid, $filename);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Truck created successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create truck']);
}

$stmt->close();
$conn->close();
