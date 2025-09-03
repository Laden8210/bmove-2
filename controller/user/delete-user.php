<?php
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');

// Check if the request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be DELETE', 'http_code' => 405]);
    exit;
}

// Retrieve the input data from the request body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'User ID is required', 'http_code' => 400]);
    exit;
}

$user_id = $input['uid'];

// Check if the user exists in the database
$stmt = $conn->prepare("SELECT * FROM users WHERE uid = ? AND is_deleted = 0");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found', 'http_code' => 404]);
    $conn->close();
    exit;
}


$delete_stmt = $conn->prepare("UPDATE users SET is_deleted = 1 WHERE uid = ?");
$delete_stmt->bind_param("s", $user_id);
$delete_stmt->execute();

if ($delete_stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully', 'http_code' => 200]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete user', 'http_code' => 500]);
}

$delete_stmt->close();
$conn->close();
?>
