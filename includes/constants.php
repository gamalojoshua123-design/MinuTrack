<?php
define('PAGE_TITLES', [
    'dashboard' => 'Dashboard Overview',
    'inventory' => 'Inventory Management',
    'products' => 'Product Management',
    'users' => 'User Management',
    'cashiers' => 'Cashier Management',
    'reports' => 'Sales Reports',
    'branch_comparison' => 'Branch Sales Comparison',
    'branches' => 'Branch Management',
    'ai_chat' => 'AI Assistant'
]);

define('CATEGORIES', [
    'BIG TIME Burgers' => ['name' => 'BIG TIME Burgers', 'emoji' => '🍔', 'order' => 1],
    'MinuteBurgers' => ['name' => 'MinuteBurgers', 'emoji' => '🍔', 'order' => 2],
    'Hotdogs' => ['name' => 'Hotdogs', 'emoji' => '🌭', 'order' => 3],
    'Drinks' => ['name' => 'Drinks', 'emoji' => '🥤', 'order' => 4],
    'Add-ons' => ['name' => 'Add-ons', 'emoji' => '🍟', 'order' => 5]
]);

define('ROLES', [
    'admin' => 'Owner',
    'manager' => 'Manager',
    'cashier' => 'Cashier',
]);
define('STATUSES', ['active' => 'Active', 'inactive' => 'Inactive']);
define('LOW_STOCK_THRESHOLD', 10);
define('DEFAULT_MIN_STOCK', 10);
?>