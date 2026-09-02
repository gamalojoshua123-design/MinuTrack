# POS: inline branch picker for Owner without a branch view

## Problem
Owner (Allan) opens `cashier/pos.php` right after login, before using
Branches -> View. `getCurrentBranchId()` returns null, so after the earlier
honest-zero-stock fix every product renders "Out of Stock" with only a small
banner. This reads as broken ("Joshua's branch has no stock") even though
branch 2 has healthy stock (verified: 17-202 units per product).

## Goal
When POS loads with no branch context:
- Owner -> show an inline branch picker (buttons for each active branch).
  Clicking one goes to `admin/switch_branch.php?branch_id=N&redirect=../cashier/pos.php`,
  which reloads POS with that branch's live stock.
- Non-owner without an assigned branch -> keep the "ask owner to assign" notice.
- Product grid + search stay in the DOM but hidden when branchless, so the
  page's inline JS (which references #products-grid etc.) keeps working.

## Changes — all in `cashier/pos.php`

### 1. Load picker data (after the `$missing_branch` block, ~line 146)
```php
// Owner without a branch view: offer an inline branch picker instead of a
// grid of zeroed-out products.
$pos_branches = [];
if ($missing_branch && isOwner()) {
    try {
        $pos_branches = $pdo->query("
            SELECT id, branch_name
            FROM branches
            WHERE status = 'active'
            ORDER BY branch_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("POS branch picker query failed: " . $e->getMessage());
    }
}
```

### 2. Replace the current `$missing_branch` banner block (~lines 2227-2238)
- Owner: warning panel titled "Select a branch to open the POS" with one
  `<a class="btn btn-primary">` per active branch linking to
  `../admin/switch_branch.php?branch_id=<id>&redirect=<rawurlencode('../cashier/pos.php')>`
  (switch_branch.php's redirect regex already permits `../cashier/pos.php`).
  Empty-state text if no active branches exist.
- Non-owner: unchanged "account not assigned" message.

### 3. Hide catalog when branchless
Wrap `<div class="search-filters">` through the closing `</div>` of
`<div class="products-grid">` (ends line ~2316) in:
`<div <?php echo $missing_branch ? 'style="display:none"' : ''; ?>>` ... `</div>`
so element IDs remain for JS but nothing screams "Out of Stock".

## Verification
1. `php -l cashier/pos.php`.
2. CLI harness (temp dir): render-path simulation — owner w/o view returns
   `$pos_branches` = 4 branches; branch view=2 returns real stock numbers.
3. Manual: log in as Allan, open POS immediately -> picker appears; click
   "Minute Burger Cagayan de Oro" -> POS shows branch 2 stock (Minute Burger 77,
   Beef Shawarma 17, ...). Log in as Joshua -> direct stock, no picker.
