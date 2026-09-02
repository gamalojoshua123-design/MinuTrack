-- Add UNIQUE constraint on order_number to prevent race condition duplicates
ALTER TABLE orders ADD UNIQUE INDEX idx_order_number_unique (order_number);
