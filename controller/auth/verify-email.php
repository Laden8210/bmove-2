<?php
// Session and $conn are already provided by index.php
require_once __DIR__ . '/../../function/NotificationService.php';

// This endpoint handles GET requests from the email verification link
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

if (!isset($_GET['token']) || empty($_GET['token'])) {
    http_response_code(400);
    echo "Invalid verification link.";
    exit;
}

$token = filter_var($_GET['token'], FILTER_SANITIZE_STRING);

// Validate token format (should be 64 hex characters)
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    echo "Invalid verification token.";
    exit;
}

// Look up user with this verification token
$stmt = $conn->prepare("SELECT uid, email_address, full_name, contact_number FROM users WHERE verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(400);
    echo "Invalid or expired verification link. The token may have already been used.";
    exit;
}

$user = $result->fetch_assoc();

// Mark user as verified and clear the token
$update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE uid = ?");
$update->bind_param("s", $user['uid']);

if ($update->execute()) {
    // Send SMS Confirmation
    if (!empty($user['contact_number'])) {
        $notificationService = new NotificationService();
        $smsMessage = "Hi {$user['full_name']}, your registration with BMoveXpress is complete. Welcome!";
        $notificationService->sendSMS($user['contact_number'], $smsMessage);
    }

    // Redirect to login with success message
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = ($host === 'localhost') ? "$protocol://$host/bmove-v2" : "$protocol://$host";
    header("Location: $baseUrl/login?verified=1");
    exit;
} else {
    http_response_code(500);
    echo "An error occurred during verification. Please try again.";
}

$stmt->close();
$update->close();
$conn->close();
