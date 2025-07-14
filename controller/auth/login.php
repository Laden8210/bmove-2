<?php

require_once '../../config/config.php';

// Set session configuration BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost'); // Enable only in production
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

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}

if (!isset($request_body['email']) || !isset($request_body['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required', 'http_code' => 400]);
    exit;
}

$email = $conn->real_escape_string($request_body['email']);
$password = $request_body['password'];

try {


    $stmt = $conn->prepare("SELECT uid, email_address, password, account_type FROM users WHERE email_address = ? or username = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password', 'http_code' => 401]);
        exit;

    }

    $user = $result->fetch_assoc();
    
    // if (!password_verify($password, $user['password'])) {
    //     echo json_encode(['status' => 'error', 'message' => 'Incorrect Password', 'http_code' => 401]);
    //     exit;
    // }
    session_regenerate_id(true);
    

    $_SESSION['auth'] = [
        'user_id' => $user['uid'],
        'role' => $user['account_type'],
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user' => [
            'uid' => $user['uid'],
            'email' => $user['email_address'],
            'role' => $user['account_type']
        ],
        'http_code' => 200
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'http_code' => 401
    ]);
    exit;
}

?>