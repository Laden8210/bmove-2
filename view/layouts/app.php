<!DOCTYPE html>
<html lang="en">

<head>
    <?php

    include 'view/components/head.php';
    ?>
</head>

<body>

    <?php


    $currentRoute = $_GET['request'] ?? 'home';


    $hideTopBarRoutes = ['login', 'register', 'forgot-password', 'confirm-otp', 'reset-password', 'home', '', 'book', 'customer-dashboard', 'customer-bookings', 'driver-dashboard', 'create-booking', 'about', 'locations', 'inquire'];
    if (!in_array($currentRoute, $hideTopBarRoutes)) {
        include 'view/components/header.php';
        include 'view/components/aside.php';
    }else {
        include 'view/components/home-nav.php';
    }

    include $content;

    ?>


    <?php
    include 'view/components/script.php';
    include 'view/components/footer.php';
    ?>

</body>

</html>