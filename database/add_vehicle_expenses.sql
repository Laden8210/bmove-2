-- Run this script in phpMyAdmin or via MySQL command line against your database
-- It creates the vehicle_expenses table for the new Income & Expense Report feature

CREATE TABLE IF NOT EXISTS vehicle_expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id VARCHAR(50) NOT NULL,
    expense_date DATE NOT NULL,
    expense_type VARCHAR(50) NOT NULL,
    description TEXT,
    amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicleid) ON DELETE CASCADE
);
