-- 009: Branch-scope inventory purchase orders
-- inventory_orders previously had no branch ownership; orders are created
-- from branch-scoped inventory (inventory/inventory.php "Create Order").

ALTER TABLE `inventory_orders`
    ADD COLUMN `branch_id` INT NULL DEFAULT NULL AFTER `supplier`,
    ADD CONSTRAINT `fk_inventory_orders_branch`
        FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);
