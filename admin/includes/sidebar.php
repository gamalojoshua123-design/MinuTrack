<?php
// Single source of truth: reuse the shared sidebar for all roles.
// This ensures Owner, Manager, and Cashier sidebars are identical
// whether the user is on admin/*.php or any other page.
include __DIR__ . '/../../includes/sidebar.php';
