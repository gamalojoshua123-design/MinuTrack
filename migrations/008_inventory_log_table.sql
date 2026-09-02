-- Migration 008: inventory_log table
-- Previously auto-created on every request inside get_item_history.php
-- (DDL-per-request); moved here as a one-time migration.

CREATE TABLE IF NOT EXISTS inventory_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    previous_quantity INT NOT NULL,
    quantity_added INT NOT NULL,
    new_quantity INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    update_date DATETIME NOT NULL,
    INDEX (item_id),
    INDEX (update_date)
);
