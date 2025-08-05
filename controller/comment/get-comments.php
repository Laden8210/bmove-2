

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

if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Booking ID (booking_id) is required', 'http_code' => 400]);
    exit;
}

$booking_id = $_GET['booking_id'];



if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

$stmt = $conn->prepare("SELECT comments.*, users.full_name AS user_name FROM comments
 JOIN users ON comments.user_id = users.uid
 WHERE booking_id = ?");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No comments found for this booking', 'http_code' => 404]);
    exit;
}
$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode(['status' => 'success', 'message' => 'Comments retrieved successfully', 'data' => $comments, 'http_code' => 200]);
exit;