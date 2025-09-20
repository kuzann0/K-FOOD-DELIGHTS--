-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Truncate tables in correct order
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;