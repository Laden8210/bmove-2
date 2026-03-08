<?php
// seeder.php

function generateUUID()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function randomDate($start_date, $end_date)
{
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $val = rand($min, $max);
    return date('Y-m-d H:i:s', $val);
}

function randomPlate()
{
    $letters = chr(rand(65, 90)) . chr(rand(65, 90)) . chr(rand(65, 90));
    $numbers = rand(1000, 9999);
    return "$letters-$numbers";
}

// Function to safely escape strings for raw SQL without a DB connection
function esc($string)
{
    if ($string === null)
        return 'NULL';
    $escaped = str_replace("'", "''", $string);
    return "'" . $escaped . "'";
}

$start_date = '2026-01-01 00:00:00'; // Jan 1 2026
$end_date = date('Y-m-d H:i:s'); // Today's date
$password_hash = password_hash('password123', PASSWORD_DEFAULT);

$sql_file_path = 'seed.sql';
$sql_file = fopen($sql_file_path, 'w');

if (!$sql_file) {
    die("Unable to open file $sql_file_path for writing.\n");
}

function writeSql($file, $query)
{
    fwrite($file, $query . ";\n");
}

echo "Generating SQL script...\n";

writeSql($sql_file, "-- Generated Seed Data");
writeSql($sql_file, "SET FOREIGN_KEY_CHECKS = 0");
writeSql($sql_file, "TRUNCATE TABLE vehicle_expenses");
writeSql($sql_file, "TRUNCATE TABLE payments");
writeSql($sql_file, "TRUNCATE TABLE bookings");
writeSql($sql_file, "DELETE FROM vehicles");
writeSql($sql_file, "DELETE FROM users WHERE account_type IN ('driver', 'customer')");
writeSql($sql_file, "SET FOREIGN_KEY_CHECKS = 1");

$filipino_first_names = ['Juan', 'Pedro', 'Maria', 'Jose', 'Andres', 'Antonio', 'Francisco', 'Manuel', 'Luis', 'Carmelita', 'Luzviminda', 'Teresita', 'Nene', 'Boy', 'Jun', 'Ricardo', 'Eduardo', 'Roberto', 'Ramon', 'Fernando', 'Carlos', 'Crisanto', 'Ernesto', 'Roderick', 'Rowena', 'Maricel', 'Jonalyn', 'Lorna', 'Jeric', 'Mark', 'Christian'];
$filipino_last_names = ['Dela Cruz', 'Garcia', 'Reyes', 'Ramos', 'Mendoza', 'Santos', 'Flores', 'Gonzales', 'Bautista', 'Villanueva', 'Fernandez', 'Cruz', 'Ocampo', 'Espiritu', 'Navarro', 'Torres', 'Perez', 'Aquino', 'Del Rosario', 'Soriano', 'Gomez', 'Tolentino', 'Salazar', 'Rivera', 'Santiago', 'Manalo', 'San Jose', 'Castro', 'Sison', 'Lopez'];

// 1. Seed 20 Drivers (15 Active, 5 Inactive)
$driver_ids = [];
writeSql($sql_file, "\n-- Drivers");
for ($i = 1; $i <= 20; $i++) {
    $uid = generateUUID();
    $username = "driver_$i";
    $first_name = $filipino_first_names[array_rand($filipino_first_names)];
    $last_name = $filipino_last_names[array_rand($filipino_last_names)];
    $full_name = "$first_name $last_name";
    $contact = "09" . rand(10000000, 99999999);
    $email = "driver$i@example.com";
    $status = ($i <= 5) ? 'Inactive' : 'Active';
    $created_at = randomDate($start_date, $end_date);

    $query = sprintf(
        "INSERT INTO users (uid, username, full_name, contact_number, email_address, password, account_type, account_status, created_at, email_verified) VALUES (%s, %s, %s, %s, %s, %s, 'driver', %s, %s, 1)",
        esc($uid),
        esc($username),
        esc($full_name),
        esc($contact),
        esc($email),
        esc($password_hash),
        esc($status),
        esc($created_at)
    );
    writeSql($sql_file, $query);
    $driver_ids[] = $uid;
}
echo "Generated 20 Drivers.\n";

// 2. Seed 15 Customers
$customer_ids = [];
writeSql($sql_file, "\n-- Customers");
for ($i = 1; $i <= 15; $i++) {
    $uid = generateUUID();
    $username = "customer_$i";
    $first_name = $filipino_first_names[array_rand($filipino_first_names)];
    $last_name = $filipino_last_names[array_rand($filipino_last_names)];
    $full_name = "$first_name $last_name";
    $contact = "09" . rand(10000000, 99999999);
    $email = "customer$i@example.com";
    $created_at = randomDate($start_date, $end_date);

    $query = sprintf(
        "INSERT INTO users (uid, username, full_name, contact_number, email_address, password, account_type, account_status, created_at, email_verified) VALUES (%s, %s, %s, %s, %s, %s, 'customer', 'Active', %s, 1)",
        esc($uid),
        esc($username),
        esc($full_name),
        esc($contact),
        esc($email),
        esc($password_hash),
        esc($created_at)
    );
    writeSql($sql_file, $query);
    $customer_ids[] = $uid;
}
echo "Generated 15 Customers.\n";

// 3. Seed 20 Vehicles
$vehicle_ids = [];
$vehicle_profiles = [
    ['brand' => 'Foton', 'type' => 'Flatbed', 'image' => 'Foton Flatbed.jpg'],
    ['brand' => 'Hino', 'type' => 'Box Truck', 'image' => 'Hino Box Truck.jpg'],
    ['brand' => 'Hyundai', 'type' => 'Box Truck', 'image' => 'Hyundai Box Truck.jpg'],
    ['brand' => 'Hyundai', 'type' => 'Refrigerated', 'image' => 'Hyundai Refrigerated.jpeg'],
    ['brand' => 'Hyundai', 'type' => 'Van', 'image' => 'Hyundai Van.jpg'],
    ['brand' => 'Isuzu', 'type' => 'Box Truck', 'image' => 'Isuzu Box Truck 3.jpeg'],
    ['brand' => 'Isuzu', 'type' => 'Flatbed', 'image' => 'Isuzu Flatbed.jpg'],
    ['brand' => 'Isuzu', 'type' => 'Van', 'image' => 'Isuzu Van.avif'],
    ['brand' => 'Nissan', 'type' => 'Flatbed', 'image' => 'Nissan Flatbed.jpg'],
    ['brand' => 'Nissan', 'type' => 'Refrigerated', 'image' => 'Nissan Refrigerated.jpg'],
    ['brand' => 'Suzuki', 'type' => 'Van', 'image' => 'Suzuki Van.jpg'],
    ['brand' => 'Toyota', 'type' => 'Box Truck', 'image' => 'Toyota Box Truck 1.jpg'],
    ['brand' => 'Toyota', 'type' => 'Flatbed', 'image' => 'Toyota Flatbed.jpg']
];
writeSql($sql_file, "\n-- Vehicles");
for ($i = 1; $i <= 20; $i++) {
    $vid = generateUUID();
    $profile = $vehicle_profiles[array_rand($vehicle_profiles)];
    $brand = $profile['brand'];
    $type = $profile['type'];
    $image_filename = $profile['image'];
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

    $query = sprintf(
        "INSERT INTO vehicles (vehicleid, name, platenumber, totalcapacitykg, status, baseprice, rateperkm, type, model, year, date_added, driver_uid, image_path) VALUES (%s, %s, %s, %d, %s, %f, %f, %s, %s, %d, %s, %s, %s)",
        esc($vid),
        esc($name),
        esc($plate),
        $capacity,
        esc($status),
        $baseprice,
        $rateperkm,
        esc($type),
        esc($model),
        $year,
        esc($date_added),
        esc($driver_uid),
        esc($image_filename)
    );
    writeSql($sql_file, $query);
    $vehicle_ids[] = $vid;
}
echo "Generated 20 Vehicles.\n";

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
writeSql($sql_file, "\n-- Bookings and Payments");
for ($i = 1; $i <= 15; $i++) {
    $bid = generateUUID();
    $user_id = $customer_ids[array_rand($customer_ids)];
    $vehicle_id = $vehicle_ids[array_rand($vehicle_ids)];
    $pickup = $locations[array_rand($locations)];
    $dropoff = $locations[array_rand($locations)];
    while ($dropoff == $pickup) {
        $dropoff = $locations[array_rand($locations)];
    }

    $created_at = randomDate($start_date, $end_date);
    $b_date = date('Y-m-d', strtotime($created_at . ' + ' . rand(1, 10) . ' days'));
    $b_time = date('H:i:00', rand(28800, 64800));

    $distance = rand(5, 50) + (rand(0, 99) / 100);
    $price = rand(2000, 10000) + (rand(0, 99) / 100);
    $weight = rand(100, 500);
    $items = rand(1, 10);
    $b_statuses = ['completed', 'pending', 'in_progress'];
    $b_status = $b_statuses[array_rand($b_statuses)];
    $payment_method = 'cash';

    $query_b = sprintf(
        "INSERT INTO bookings (booking_id, user_id, vehicle_id, pickup_location, dropoff_location, date, time, total_distance, total_price, total_weight, items_count, status, payment_method, created_at) VALUES (%s, %s, %s, %s, %s, %s, %s, %f, %f, %d, %d, %s, %s, %s)",
        esc($bid),
        esc($user_id),
        esc($vehicle_id),
        esc($pickup),
        esc($dropoff),
        esc($b_date),
        esc($b_time),
        $distance,
        $price,
        $weight,
        $items,
        esc($b_status),
        esc($payment_method),
        esc($created_at)
    );
    writeSql($sql_file, $query_b);
    $booking_ids[] = $bid;

    $pid = generateUUID();
    $p_status = $b_status == 'completed' ? 'paid' : ($b_status == 'pending' ? 'pending' : 'paid');
    $received = $p_status == 'paid' ? $price : 0;
    $paid_at = $p_status == 'paid' ? randomDate($created_at, $end_date) : null;

    $query_p = sprintf(
        "INSERT INTO payments (payment_id, booking_id, user_id, amount_due, amount_received, payment_method, payment_status, paid_at, created_at) VALUES (%s, %s, %s, %f, %f, %s, %s, %s, %s)",
        esc($pid),
        esc($bid),
        esc($user_id),
        $price,
        $received,
        esc($payment_method),
        esc($p_status),
        esc($paid_at),
        esc($created_at)
    );
    writeSql($sql_file, $query_p);
}
echo "Generated 15 Bookings & Payments.\n";

// 5. Seed some expenses for Reports
writeSql($sql_file, "\n-- Vehicle Expenses");
for ($i = 0; $i < 20; $i++) {
    $vehicle_id = $vehicle_ids[array_rand($vehicle_ids)];
    $expense_types = ['Fuel', 'Maintenance', 'Insurance', 'Toll Fees', 'Others'];
    $expense_type = $expense_types[array_rand($expense_types)];
    $expense_date = randomDate($start_date, $end_date);
    $desc = "Random $expense_type expense";
    $amount = rand(500, 5000);

    $query_e = sprintf(
        "INSERT INTO vehicle_expenses (vehicle_id, expense_date, expense_type, description, amount) VALUES (%s, %s, %s, %s, %f)",
        esc($vehicle_id),
        esc($expense_date),
        esc($expense_type),
        esc($desc),
        $amount
    );
    writeSql($sql_file, $query_e);
}
echo "Generated 20 Vehicle Expenses.\n";

fclose($sql_file);
echo "SQL script successfully generated at: $sql_file_path\n";
?>