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

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

if ($_SESSION['auth']['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized action', 'http_code' => 403]);
    exit;
}

$vehicle_id = $_POST['vehicle_id'] ?? '';
$expense_date = $_POST['expense_date'] ?? '';
$expense_type = $_POST['expense_type'] ?? '';
$expense_category = $_POST['expense_category'] ?? 'admin';
$description = $_POST['description'] ?? '';
$amount = $_POST['amount'] ?? '';

if (empty($vehicle_id) || empty($expense_date) || empty($expense_type) || empty($amount)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields', 'http_code' => 400]);
    exit;
}

$vehicle_id = filter_var($vehicle_id, FILTER_SANITIZE_STRING);
$expense_date = filter_var($expense_date, FILTER_SANITIZE_STRING);
$expense_type = filter_var($expense_type, FILTER_SANITIZE_STRING);
$expense_category = filter_var($expense_category, FILTER_SANITIZE_STRING);
$description = filter_var($description, FILTER_SANITIZE_STRING);
$amount = floatval($amount);

$valid_types = ['Fuel', 'Maintenance', 'Miscellaneous'];
if (!in_array($expense_type, $valid_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid expense type', 'http_code' => 400]);
    exit;
}

$valid_categories = ['admin', 'driver'];
if (!in_array($expense_category, $valid_categories)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid expense category', 'http_code' => 400]);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Amount must be greater than zero', 'http_code' => 400]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO vehicle_expenses (vehicle_id, expense_date, expense_type, expense_category, description, amount) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssd", $vehicle_id, $expense_date, $expense_type, $expense_category, $description, $amount);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Expense added successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add expense: ' . $conn->error, 'http_code' => 500]);
}

$stmt->close();
$conn->close();
