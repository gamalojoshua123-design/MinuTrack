<?php
/**
 * Permission catalog - single source of truth for the RBAC system.
 *
 * Keyed by canonical permission name. Used by:
 *  - migrations/005_rbac.php (seeding)
 *  - includes/rbac.php (runtime checks)
 *  - admin/roles.php (management UI)
 *
 * @return array<string, array{label:string, description:string, category:string}>
 */
return [
    // Dashboard
    'dashboard_view' => ['label' => 'Dashboard', 'description' => 'View dashboard and statistics', 'category' => 'Dashboard'],

    // POS
    'pos_access' => ['label' => 'POS Access', 'description' => 'Access the Point of Sale system', 'category' => 'POS'],

    // Products
    'products_view' => ['label' => 'View Products', 'description' => 'View product list and details', 'category' => 'Products'],
    'products_manage' => ['label' => 'Manage Products', 'description' => 'Add, edit, delete products and recipes', 'category' => 'Products'],

    // Inventory
    'inventory_view' => ['label' => 'View Inventory', 'description' => 'View inventory items and stock levels', 'category' => 'Inventory'],
    'inventory_manage' => ['label' => 'Manage Inventory', 'description' => 'Edit inventory items and post adjustments', 'category' => 'Inventory'],
    'inventory_receive' => ['label' => 'Stock Receiving', 'description' => 'Receive stock deliveries', 'category' => 'Inventory'],
    'inventory_count' => ['label' => 'Physical Count', 'description' => 'Perform physical inventory counts', 'category' => 'Inventory'],
    'inventory_reports' => ['label' => 'Inventory Reports', 'description' => 'View inventory reports', 'category' => 'Inventory'],
    'inventory_stock_movements' => ['label' => 'Stock Movement History', 'description' => 'View stock movement history', 'category' => 'Inventory'],

    // Transactions
    'transactions_view' => ['label' => 'View Transactions', 'description' => 'View transaction history', 'category' => 'Transactions'],

    // Reports
    'reports_view' => ['label' => 'View Reports', 'description' => 'View sales and financial reports', 'category' => 'Reports'],

    // Users
    'users_view' => ['label' => 'View Users', 'description' => 'View user list and details', 'category' => 'Users'],
    'users_manage' => ['label' => 'Manage Users', 'description' => 'Add, edit, delete users', 'category' => 'Users'],
    'users_roles_manage' => ['label' => 'Manage Roles & Permissions', 'description' => 'Edit roles and their permissions', 'category' => 'Users'],

    // Cashiers
    'cashiers_view' => ['label' => 'View Cashiers', 'description' => 'View cashier list and details', 'category' => 'Cashiers'],
    'cashiers_manage' => ['label' => 'Manage Cashiers', 'description' => 'Add, edit, delete cashiers', 'category' => 'Cashiers'],

    // Branches
    'branches_view' => ['label' => 'View Branches', 'description' => 'View branch list and details', 'category' => 'Branches'],
    'branches_manage' => ['label' => 'Manage Branches', 'description' => 'Add, edit, delete branches', 'category' => 'Branches'],

    // Branch Comparison
    'branch_comparison_view' => ['label' => 'Branch Comparison', 'description' => 'View branch-to-branch comparison', 'category' => 'Branch Comparison'],

    // Archive
    'archive_view' => ['label' => 'View Archive', 'description' => 'View and manage archived items', 'category' => 'Archive'],
    'archive_restore' => ['label' => 'Restore Archive', 'description' => 'Restore archived products and inventory', 'category' => 'Archive'],
    'archive_delete' => ['label' => 'Delete Archive', 'description' => 'Permanently delete archived products and inventory', 'category' => 'Archive'],

    // AI
    'ai_use' => ['label' => 'AI Assistant', 'description' => 'Use the AI assistant and analytics', 'category' => 'AI'],

    // Backup
    'backup_create' => ['label' => 'Create Backup', 'description' => 'Create database backups', 'category' => 'Backup'],
    'backup_restore' => ['label' => 'Restore Backup', 'description' => 'Restore database from backup', 'category' => 'Backup'],
    'backup_delete' => ['label' => 'Delete Backup', 'description' => 'Delete backup files', 'category' => 'Backup'],
    'backup_download' => ['label' => 'Download Backup', 'description' => 'Download backup files', 'category' => 'Backup'],

    // System
    'system_settings' => ['label' => 'System Settings', 'description' => 'Manage system settings', 'category' => 'System'],
];
