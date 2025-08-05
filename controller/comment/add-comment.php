

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

if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $_POST = is_array($input) ? $input : [];
}

if (!isset($_POST['booking_id']) || empty($_POST['booking_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Booking ID (booking_id) is required', 'http_code' => 400]);
    exit;
}

$booking_id = $_POST['booking_id'];
$comment_rating = isset($_POST['comment_rating']) ? $_POST['comment_rating'] : null;
$comment_text = isset($_POST['comment_text']) ? $_POST['comment_text'] : null;



if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

$comment_id = UIDGenerator::generateUUID();

$stmt = $conn->prepare("INSERT INTO comments (comment_id, booking_id, user_id, comment, comment_rating) VALUES (?, ?, ?, ?, ?)");
$user_id = $_SESSION['auth']['user_id'];
$stmt->bind_param("ssssi", $comment_id, $booking_id, $user_id, $comment_text, $comment_rating);
if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add comment: ' . $stmt->error, 'http_code' => 500]);
    exit;
}
$stmt->close();
$conn->close();
echo json_encode(['status' => 'success', 'message' => 'Comment added successfully', 'http_code' => 200]);
exit;