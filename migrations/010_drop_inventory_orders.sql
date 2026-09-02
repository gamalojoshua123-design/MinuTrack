-- 010: Remove unused inventory purchase-order tables.
-- The Create Order feature was removed from inventory/inventory.php;
-- these tables were never populated by any live code path.

DROP TABLE IF EXISTS `inventory_order_items`;
DROP TABLE IF EXISTS `inventory_orders`;
