<?php
// seeder.php
require_once 'config/config.php';

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function randomDate($start_date, $end_date) {
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $val = rand($min, $max);
    return date('Y-m-d H:i:s', $val);
}

function randomPlate() {
    $letters = chr(rand(65,90)) . chr(rand(65,90)) . chr(rand(65,90));
    $numbers = rand(1000, 9999);
    return "$letters-$numbers";
}

$start_date = '2025-01-01 00:00:00'; // Past 3 months
$end_date = '2025-03-31 23:59:59';
$password_hash = password_hash('password123', PASSWORD_DEFAULT);

echo "Starting database seeding...\n";

// Clear existing seeded data to avoid unique constraints
echo "Clearing old data...\n";
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE vehicle_expenses");
$conn->query("TRUNCATE TABLE payments");
$conn->query("TRUNCATE TABLE bookings");

// Only delete the vehicles and users we created in the seeder, or just truncate 
// all dummy vehicles and generated users.
$conn->query("DELETE FROM vehicles");
$conn->query("DELETE FROM users WHERE account_type IN ('driver', 'customer')");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$filipino_first_names = ['Juan', 'Pedro', 'Maria', 'Jose', 'Andres', 'Antonio', 'Francisco', 'Manuel', 'Luis', 'Carmelita', 'Luzviminda', 'Teresita', 'Nene', 'Boy', 'Jun', 'Ricardo', 'Eduardo', 'Roberto', 'Ramon', 'Fernando', 'Carlos', 'Crisanto', 'Ernesto', 'Roderick', 'Rowena', 'Maricel', 'Jonalyn', 'Lorna', 'Jeric', 'Mark', 'Christian'];
$filipino_last_names = ['Dela Cruz', 'Garcia', 'Reyes', 'Ramos', 'Mendoza', 'Santos', 'Flores', 'Gonzales', 'Bautista', 'Villanueva', 'Fernandez', 'Cruz', 'Ocampo', 'Espiritu', 'Navarro', 'Torres', 'Perez', 'Aquino', 'Del Rosario', 'Soriano', 'Gomez', 'Tolentino', 'Salazar', 'Rivera', 'Santiago', 'Manalo', 'San Jose', 'Castro', 'Sison', 'Lopez'];

// 1. Seed 20 Drivers (15 Active, 5 Inactive)
$driver_ids = [];
for ($i = 1; $i <= 20; $i++) {
    $uid = generateUUID();
    $username = "driver_$i";
    $first_name = $filipino_first_names[array_rand($filipino_first_names)];
    $last_name = $filipino_last_names[array_rand($filipino_last_names)];
    $full_name = "$first_name $last_name";
    $contact = "09" . rand(100000000, 999999999);
    $email = "driver$i@example.com";
    $status = ($i <= 5) ? 'Inactive' : 'Active';
    $created_at = randomDate($start_date, $end_date);
    
    $stmt = $conn->prepare("INSERT INTO users (uid, username, full_name, contact_number, email_address, password, account_type, account_status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'driver', ?, ?)");
    $stmt->bind_param("ssssssss", $uid, $username, $full_name, $contact, $email, $password_hash, $status, $created_at);
    $stmt->execute();
    $driver_ids[] = $uid;
}
echo "Seeded 20 Drivers.\n";

// 2. Seed 15 Customers
$customer_ids = [];
for ($i = 1; $i <= 15; $i++) {
    $uid = generateUUID();
    $username = "customer_$i";
    $first_name = $filipino_first_names[array_rand($filipino_first_names)];
    $last_name = $filipino_last_names[array_rand($filipino_last_names)];
    $full_name = "$first_name $last_name";
    $contact = "09" . rand(100000000, 999999999);
    $email = "customer$i@example.com";
    $created_at = randomDate($start_date, $end_date);
    
    $stmt = $conn->prepare("INSERT INTO users (uid, username, full_name, contact_number, email_address, password, account_type, account_status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'customer', 'Active', ?)");
    $stmt->bind_param("sssssss", $uid, $username, $full_name, $contact, $email, $password_hash, $created_at);
    $stmt->execute();
    $customer_ids[] = $uid;
}
echo "Seeded 15 Customers.\n";

// 3. Seed 20 Vehicles
// Requirements: 5 vehicles same type but different plates
$vehicle_ids = [];
$types = ['Box Truck', 'Flatbed', 'Refrigerated', 'Van'];
$truck_brands = ['Isuzu', 'Mitsubishi Fuso', 'Hino', 'Toyota', 'Nissan', 'Hyundai', 'Suzuki', 'Foton'];
$assigned_drivers = []; // Keep track to assign uniquely if needed, or just random
for ($i = 1; $i <= 20; $i++) {
    $vid = generateUUID();
    $type = $i <= 5 ? 'Box Truck' : $types[array_rand($types)]; // 5 same type
    $brand = $truck_brands[array_rand($truck_brands)];
    $name = "$brand $type $i";
    $model = "$brand Series " . rand(1, 9) . "00";
    $plate = randomPlate();
    $capacity = rand(1000, 5000);
    $status = 'available';
    $baseprice = rand(1000, 3000);
    $rateperkm = rand(10, 50);
    $year = rand(2015, 2024);
    $date_added = randomDate($start_date, $end_date);
    $driver_uid = $driver_ids[array_rand($driver_ids)];
    
    $stmt = $conn->prepare("INSERT INTO vehicles (vehicleid, name, platenumber, totalcapacitykg, status, baseprice, rateperkm, type, model, year, date_added, driver_uid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisddssiss", $vid, $name, $plate, $capacity, $status, $baseprice, $rateperkm, $type, $model, $year, $date_added, $driver_uid);
    $stmt->execute();
    $vehicle_ids[] = $vid;
}
echo "Seeded 20 Vehicles.\n";

// 4. Seed 15 Bookings
$booking_ids = [];
$locations = [
    'Balanga City, Bataan', 
    'Dinalupihan, Bataan', 
    'Orani, Bataan', 
    'Morong, Bataan', 
    'Mariveles, Bataan', 
    'Orion, Bataan', 
    'Limay, Bataan', 
    'Abucay, Bataan', 
    'Hermosa, Bataan', 
    'Samal, Bataan', 
    'Pilar, Bataan', 
    'Bagac, Bataan'
];
for ($i = 1; $i <= 15; $i++) {
    $bid = generateUUID();
    $user_id = $customer_ids[array_rand($customer_ids)];
    $vehicle_id = $vehicle_ids[array_rand($vehicle_ids)];
    $pickup = $locations[array_rand($locations)];
    $dropoff = $locations[array_rand($locations)];
    while ($dropoff == $pickup) { $dropoff = $locations[array_rand($locations)]; }
    
    // Booking date and time
    $created_at = randomDate($start_date, $end_date); // Request date
    $b_date = date('Y-m-d', strtotime($created_at . ' + ' . rand(1, 10) . ' days'));
    $b_time = date('H:i:00', rand(28800, 64800)); // 8 AM to 6 PM
    
    $distance = rand(5, 50) + (rand(0, 99) / 100);
    $price = rand(2000, 10000) + (rand(0, 99) / 100);
    $weight = rand(100, 500);
    $items = rand(1, 10);
    $b_statuses = ['completed', 'pending', 'in_progress'];
    $b_status = $b_statuses[array_rand($b_statuses)];
    $payment_method = 'cash';
    
    $stmt = $conn->prepare("INSERT INTO bookings (booking_id, user_id, vehicle_id, pickup_location, dropoff_location, date, time, total_distance, total_price, total_weight, items_count, status, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssddiisss", $bid, $user_id, $vehicle_id, $pickup, $dropoff, $b_date, $b_time, $distance, $price, $weight, $items, $b_status, $payment_method, $created_at);
    $stmt->execute();
    $booking_ids[] = $bid;
    
    // Create payment record for each booking to show up in revenue report
    $pid = generateUUID();
    $p_status = $b_status == 'completed' ? 'paid' : ($b_status == 'pending' ? 'pending' : 'paid');
    $received = $p_status == 'paid' ? $price : 0;
    $paid_at = $p_status == 'paid' ? randomDate($created_at, $end_date) : null;
    
    $stmt_pay = $conn->prepare("INSERT INTO payments (payment_id, booking_id, user_id, amount_due, amount_received, payment_method, payment_status, paid_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_pay->bind_param("sssddssss", $pid, $bid, $user_id, $price, $received, $payment_method, $p_status, $paid_at, $created_at);
    $stmt_pay->execute();
}
echo "Seeded 15 Bookings & Payments.\n";

// 5. Seed some expenses for Reports
for ($i = 0; $i < 20; $i++) {
    $vehicle_id = $vehicle_ids[array_rand($vehicle_ids)];
    $expense_types = ['Fuel', 'Maintenance', 'Insurance', 'Toll Fees', 'Others'];
    $expense_type = $expense_types[array_rand($expense_types)];
    $expense_date = randomDate($start_date, $end_date);
    $desc = "Random $expense_type expense";
    $amount = rand(500, 5000);
    
    $stmt = $conn->prepare("INSERT INTO vehicle_expenses (vehicle_id, expense_date, expense_type, description, amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $vehicle_id, $expense_date, $expense_type, $desc, $amount);
    $stmt->execute();
}
echo "Seeded 20 Vehicle Expenses.\n";

echo "Seeding completed successfully!\n";
?>
