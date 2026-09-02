<?php
/**
 * Default role-permission matrix for the RBAC system.
 *
 * Keyed by role name (matches roles.role_name). Each value is the list of
 * permission names granted to that role. Used by:
 *  - migrations/005_rbac.php (seeding)
 *  - includes/rbac.php (fallback when DB role_permissions are unavailable)
 *  - admin/roles.php (management UI defaults)
 *
 * NOTE: The admin (System Owner) role always receives every permission.
 *
 * @return array<string, string[]>
 */
$catalog = require __DIR__ . '/permission_catalog.php';

return [
    // System Owner - full access to everything
    'admin' => array_keys($catalog),

    // Store Admin - all except roles/permissions, restore backup, settings
    'manager' => [
        'dashboard_view',
        'pos_access',
        'products_view',
        'products_manage',
        'inventory_view',
        'inventory_manage',
        'inventory_receive',
        'inventory_count',
        'inventory_reports',
        'inventory_stock_movements',
        'transactions_view',
        'reports_view',
        'users_view',
        'users_manage',
        'cashiers_view',
        'cashiers_manage',
        'branches_view',
        'branches_manage',
        'branch_comparison_view',
        'archive_view',
        'archive_restore',
        'ai_use',
        'backup_create',
        'backup_delete',
        'backup_download',
    ],

    // Cashier - POS and own transactions
    'cashier' => [
        'dashboard_view',
        'pos_access',
        'products_view',
        'inventory_view',
        'transactions_view',
    ],
];
