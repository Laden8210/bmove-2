
<?php

require 'config/config.php';

try {

    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
    ini_set('session.use_strict_mode', 1);

    session_start();

    $baseUrl = ($_SERVER['HTTP_HOST'] === 'localhost')
        ? 'http://localhost/bmove-v2'
        : '';

    $requestUri = explode('?', $_SERVER['REQUEST_URI'])[0];
    $request = trim(str_replace('/bmove-v2/', '', $requestUri), '/');

    $request = preg_replace('/[^a-zA-Z0-9_-]/', '', $request);
    $request = $request ?: '';

    $routes = [
        'home' => ['file' => 'view/home/index.php', 'title' => 'Home', 'auth_required' => false, 'layout' => 'view/layouts/app.php'],
        '' => ['file' => 'view/home/index.php', 'title' => 'Home', 'auth_required' => false, 'layout' => 'view/layouts/app.php'],
        'register' => ['file' => 'view/auth/register.php', 'title' => 'Register', 'auth_required' => false, 'layout' => 'view/layouts/app.php'],
        'dashboard' => ['file' => 'view/users/dashboard.php', 'title' => 'Dashboard', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'login' => ['file' => 'view/auth/login.php', 'title' => 'Login', 'auth_required' => false, 'layout' => 'view/layouts/app.php'],
        'bookings' => ['file' => 'view/bookings/index.php', 'title' => 'Bookings', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'vehicle' => ['file' => 'view/vehicle/index.php', 'title' => 'Trucks', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'manage-user-account' => ['file' => 'view/users/manage-user-account.php', 'title' => 'Manage User Account', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'about-us' => ['file' => 'view/about-us/index.php', 'title' => 'About Us', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'book' => ['file' => 'view/bookings/book.php', 'title' => 'Book', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],

        'customer-dashboard' => ['file' => 'view/customer/dashboard.php', 'title' => 'Customer Dashboard', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'create-booking' => ['file' => 'view/home/index.php', 'title' => 'Create Booking', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'driver-dashboard' => ['file' => 'view/driver/dashboard.php', 'title' => 'Driver Dashboard', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
        'report' => ['file' => 'view/report/index.php', 'title' => 'Report', 'auth_required' => true, 'layout' => 'view/layouts/app.php'],
    ];



    if ($request === 'logout') {
        session_unset();
        session_destroy();
        header('Location: login');
        exit;
    }

    if (!isset($routes[$request])) {
        http_response_code(404);
        include 'view/error/404.php';
        exit;
    }


    if (isset($_SESSION['auth']) && $routes[$request]['auth_required']) {
        $current_ip = $_SERVER['REMOTE_ADDR'];
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'];

        if (
            $_SESSION['auth']['ip_address'] !== $current_ip ||
            $_SESSION['auth']['user_agent'] !== $current_user_agent
        ) {
            session_unset();
            session_destroy();
            header('Location: login?error=session_tampered');

            exit;
        }
    }

    $sql = "SELECT * FROM users WHERE uid = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['auth']['user_id']);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $full_name = $user['full_name'];

        $role = $user['account_type'];
    }




    if ($routes[$request]['auth_required'] && !isset($_SESSION['auth'])) {
        header('Location: login');
        exit;
    }

    echo $_SESSION['auth']['username'] ?? '';

    

    if ($request === 'dashboard') {
        if ($role === 'admin') {
            // Allow default dashboard
        } elseif ($role === 'customer') {
            header('Location: customer-dashboard');
            exit;
        } elseif ($role === 'driver') {
            header('Location: driver-dashboard');
            exit;
        }
    }

    // Restrict access to role-specific routes
    $roleRoutes = [
        'customer' => ['customer-dashboard', 'book', 'create-booking', ''],
        'driver' => ['driver-dashboard'],
        'admin' => ['dashboard', 'manage-user-account', 'vehicle', 'bookings', 'about-us', 'report'], 
    ];

    if(!empty($role)) {
           foreach ($roleRoutes as $roleKey => $allowedRoutes) {
        if ($role === $roleKey && !in_array($request, $allowedRoutes ) && $routes[$request]['auth_required']) {
            header('Location: ' . $allowedRoutes[0]);
            exit;
        }
    }
    }






    $content = __DIR__ . '/' . $routes[$request]['file'];
    $title = $routes[$request]['title'];

    require_once __DIR__ . '/' . $routes[$request]['layout'];
} catch (Exception $e) {
    error_log($e->getMessage());

    echo $e->getMessage();
    http_response_code(500);

    exit;
}
