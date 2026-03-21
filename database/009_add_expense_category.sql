-- Add expense_category column to distinguish admin vs driver expenses
ALTER TABLE vehicle_expenses ADD COLUMN expense_category ENUM('admin', 'driver') NOT NULL DEFAULT 'admin' AFTER expense_type;
