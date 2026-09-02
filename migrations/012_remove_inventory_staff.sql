-- 012_remove_inventory_staff.sql
-- Removes the 'inventory_staff' role entirely from the system.
-- The system only has three roles: admin, manager, cashier.
--
-- NOTE: Run AFTER confirming no active users are assigned the
-- inventory_staff role (existing rows would break under the new enum).

-- Step 1: Drop inventory_staff role-permission mappings (FKs cascade from roles,
-- but delete explicitly for clarity / in case of prefixed FK constraints).
DELETE rp FROM role_permissions rp
LEFT JOIN roles r ON r.id = rp.role_id
WHERE r.role_name = 'inventory_staff';

-- Step 2: Delete the role row itself.
DELETE FROM roles WHERE role_name = 'inventory_staff';

-- Step 3: Narrow the users.role enum to the three remaining roles.
-- Any users still holding inventory_staff must be migrated first; the ALTER
-- below will fail if real rows still reference it (safe guard).
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','manager','cashier','branch_owner') NOT NULL DEFAULT 'cashier';
