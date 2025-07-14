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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be GET', 'http_code' => 405]);
    exit;
}

if (!isset($_GET['uid']) || empty($_GET['uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID (uid) is required', 'http_code' => 400]);
    exit;
}

$uid = $_GET['uid'];



if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}


$stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User not found', 'http_code' => 404]);
    exit;
}

$user = $result->fetch_assoc();



$stmt->close();
$conn->close();
echo json_encode(['status' => 'success', 'message' => 'User found', 'data' => $user, 'http_code' => 200]);
exit;
