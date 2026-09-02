# Permanent Branch Delete

## Decisions (user-confirmed)
- Deletes EVERYTHING tied to the branch including its orders/order items
  (sales history for that branch is lost permanently).
- Delete button always visible on every branch card.
- Owner-only action, typed-branch-name confirmation.

## Data model facts (verified)
- Tables with `branch_id`: inventory (FK), inventory_counts, inventory_movements,
  orders (FK), stock_receiving, users (FK).
- Child tables reached via ids: inventory_batches, inventory_history,
  inventory_log, restock_requests (item_id -> inventory.id),
  stock_receiving_items (receiving_id -> stock_receiving.id),
  order_items (order_id -> orders.id).
- `branch_users` table does not exist (getBranchIds() is dead code - ignore).
- cashier_shifts / x_reading_log have no branch column; they follow users, who
  are blocked from being assigned at delete time, so they are left untouched.

## Changes — all in `admin/branches.php`

### 1. Server-side: new POST action `delete` (inside the existing
rate-limit/CSRF block)
1. Owner-only guard: `if (!isOwner()) { $error = 'Only the System Owner can delete branches.'; }`
2. Load branch; 404-style error if missing.
3. Blockers (refuse with message):
   - active cashier shifts via users of the branch (same query as deactivate);
   - assigned users: `SELECT COUNT(*) FROM users WHERE branch_id = ?` > 0 ->
     "Reassign or unassign N user(s) first."
4. Impact counts for the audit log: inventory items, orders.
5. Single transaction, in order:
   - `$invIds` = SELECT id FROM inventory WHERE branch_id = ?
   - if $invIds non-empty (placeholder list):
     DELETE restock_requests WHERE item_id IN (...)
     DELETE inventory_log WHERE item_id IN (...)
     DELETE inventory_history WHERE inventory_id IN (...)
     DELETE inventory_movements WHERE inventory_id IN (...)
     DELETE inventory_batches WHERE inventory_id IN (...)
   - DELETE inventory WHERE branch_id = ?
   - DELETE inventory_counts WHERE branch_id = ?
   - DELETE stock_receiving_items WHERE receiving_id IN (SELECT id FROM stock_receiving WHERE branch_id = ?)
   - DELETE stock_receiving WHERE branch_id = ?
   - DELETE order_items WHERE order_id IN (SELECT id FROM orders WHERE branch_id = ?)
   - DELETE orders WHERE branch_id = ?
   - DELETE branches WHERE id = ?
   - commit; on PDOException roll back + generic error (FK surprise safety net).
6. If `$_SESSION['branch_view_id'] == $branch_id` unset branch_view_id/name.
7. `auditLog('deleted', 'branches', 'branch', $branch_id, 'success', "Permanently deleted branch '{$name}' ({$invCount} inventory items, {$orderCount} orders)")`.

### 2. Frontend
- Per-branch impact arrays computed next to the existing stats queries:
  `$user_counts`, `$order_counts` (GROUP BY branch_id), exposed to JS via
  json_encode alongside `inventoryCounts`.
- Add red **Delete** button to `.branch-actions` on every card:
  `onclick="return openDeleteModal(bid, 'name')"` -> always returns false.
- New modal `#deleteBranchModal` (mirrors deactivateModal markup):
  - warning text listing live impact numbers (users / inventory items / orders);
  - input "Type the branch name to confirm" — Delete button stays disabled
    until value === branch name;
  - form POST action=delete + csrfField() + branch_id.
- JS: openDeleteModal/closeDeleteModal, Escape + overlay-click close,
  input listener toggling disabled state.

### 3. Safety notes
- Deactivate-style blockers reused so no active shift survives.
- Orders deletion means admin reports/transactions lose that branch's sales
  permanently — stated verbatim in the modal.
- After delete the page re-renders from the branches query, so the card disappears.

## Verification
1. `php -l admin/branches.php`.
2. Dry-run the exact DELETE sequence in a transaction + ROLLBACK via mysql CLI
   against a throwaway branch created+populated by SQL, to prove FK order works.
3. Browser: create scratch branch, assign nothing, delete it (typed name);
   verify card gone, rows gone, audit log entry written.
