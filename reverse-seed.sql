-- Generated Reverse Seed Data (Teardown);
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE vehicle_expenses;
TRUNCATE TABLE payments;
TRUNCATE TABLE bookings;
DELETE FROM vehicles;
DELETE FROM users WHERE account_type IN ('driver', 'customer');
SET FOREIGN_KEY_CHECKS = 1;
