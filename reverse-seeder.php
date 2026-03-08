<?php
// reverse-seeder.php

require_once __DIR__ . '/config/config.php';

echo "Connecting to the database to reverse seed data...\n";

// Disable foreign key checks to safely truncate and delete related tables
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$queries = [
    "TRUNCATE TABLE vehicle_expenses",
    "TRUNCATE TABLE payments",
    "TRUNCATE TABLE bookings",
    "DELETE FROM vehicles",
    "DELETE FROM users WHERE account_type IN ('driver', 'customer')"
];

$success = true;

foreach ($queries as $query) {
    echo "Executing: $query\n";
    if ($conn->query($query) === TRUE) {
        echo "  -> Success\n";
    } else {
        echo "  -> Error: " . $conn->error . "\n";
        $success = false;
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

if ($success) {
    echo "\nAll seeded data has been successfully reversed from the database.\n";
} else {
    echo "\nSome errors occurred while reversing the data. Please check the output above.\n";
}

$conn->close();

?>