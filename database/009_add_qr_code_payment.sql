-- Add qr_code to payment_method ENUM in bookings table
ALTER TABLE bookings MODIFY COLUMN payment_method ENUM('cash', 'gcash', 'maya', 'bank_transfer', 'paymongo', 'qr_code') NOT NULL DEFAULT 'cash';

-- Add qr_code to payment_method ENUM in payments table
ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'gcash', 'maya', 'bank_transfer', 'paymongo', 'qr_code') NOT NULL DEFAULT 'cash';
