<?php
require __DIR__ . '/config/config.php';

$r = $conn->query('SELECT uid, username, email_address, contact_number, account_type, email_verified FROM users LIMIT 5');
while ($row = $r->fetch_assoc()) {
    echo json_encode($row) . PHP_EOL;
}
$conn->close();