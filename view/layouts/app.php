<!DOCTYPE html>
<html lang="en">

<head>
    <?php

include 'view/components/head.php';
?>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php


$currentRoute = $_GET['request'] ?? 'home';


$hideTopBarRoutes = [
    'login',
    'register',
    'forgot-password',
    'confirm-otp',
    'reset-password',
    'home',
    '',
    'book',
    'customer-dashboard',
    'customer-bookings',
    'driver-dashboard',
    'create-booking',
    'about',
    'locations',
    'inquire',
    'payment-success',
    'payment-cancel',
    'track-driver',
    'verify-otp',
    'my-profile',
    'complete-profile',
    'qr'
];
if (!in_array($currentRoute, $hideTopBarRoutes)) {
    include 'view/components/header.php';
    include 'view/components/aside.php';
}
else {
    include 'view/components/home-nav.php';
}

echo '<main class="flex-grow-1 mb-5">';
include $content;
echo '</main>';

?>


    <?php
include 'view/components/script.php';
include 'view/components/footer.php';
?>

</body>

</html>