<?php
// includes/permissions_quick.php
// Quick permission check functions for templates

/**
 * Check if user can access a page
 */
function pageAccessible($permission) {
    return hasPermission($permission);
}

/**
 * Render element only if user has permission
 */
function renderIf($permission, $content) {
    if (hasPermission($permission)) {
        echo $content;
    }
}

/**
 * Get permission description
 */
function getPermissionLabel($permission) {
    $permissions = getSystemPermissions();
    $entry = $permissions[$permission] ?? null;
    if (is_array($entry)) {
        return $entry['label'] ?? $permission;
    }
    return $entry ?? $permission;
}
?>