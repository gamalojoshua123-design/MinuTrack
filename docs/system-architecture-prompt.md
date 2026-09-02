# System Architecture Prompt — Minute Burger POS

> Paste the block below into any AI assistant to give it full architectural context for this codebase, then follow it with your task.

---

## System Context Prompt

You are working inside **Minute Burger POS**, a PHP 8+/MySQL point-of-sale and inventory management system. It is a plain procedural PHP application — **no framework, no Composer, no npm, no build step** — served under XAMPP at `http://localhost/minute1/`.

### How to run / verify
- Start Apache + MySQL in XAMPP.
- Root `.env` file holds `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `GROQ_API_KEY` (used by the AI assistant). Copy `.env.example` → `.env`. `.env` is git-ignored; never commit real keys.
- Schema + seed live in `pos_system (2).sql`, applied together with `migrations/` (mixed `.sql` files and one-off `.php` runner scripts, e.g. `005_rbac.php` seeds the permission catalog).
- There is **no test suite, linter, or build tooling** and no package manager. Verify changes by exercising pages in a browser against local MySQL.

### Request → response flow (entry point chain)
Every page does `require bootstrap.php`, which pulls in, in order:
1. `config.php` — loads `.env` (custom `mb_load_env_file()`, real env > .env > defaults), defines DB/app constants, sets security headers (CSP etc.) and session hardening. **Never re-set security headers ad hoc in a page.**
2. `includes/db_connect.php` — creates the shared `$pdo` PDO connection.
3. `includes/auth.php` — session/role/branch helpers; requires `includes/rbac.php`.
4. `includes/functions.php` — shared helpers (`sanitizeInput`, `formatCurrency`, CSRF token helpers, rate limiting, online-status).

### Directory layout (feature-based, not flat)
- `admin/` — Owner dashboard, users, roles, branches, branch comparison, global inventory/products, switch_branch, AI chat.
- `cashier/` — POS (`pos.php`), shifts (`start_shift.php`), X/Z readings, transactions, receipt, cashier inventory.
- `inventory/` — inventory management, counts, view, suppliers (`inventory_suppliers`).
- `products/`, `reports/`, `users/`, `ai/`, `api/` (item history, heartbeat), `auth/` (login/logout/unauthorized/welcome), `tools/` (backup, archive, auto-backup download).
- `assets/` — CSS (`layout.css`, `admin.css`, `cashier-touch.css`), JS (Chart.js).

### Legacy shims — IMPORTANT
Most original root-level files (`admin.php`, `pos.php`, `products.php`, `ai_endpoint.php`, `cashier_inventory.php`, `inventory_view.php`, `unauthorized.php`, etc.) are now **short `header('Location: …')` redirect shims** into the feature directories. **Do not add real logic to these root shims — edit the file in its feature directory instead.**
- `reports.php` is a role-aware shim: Owner → `admin/reports.php`, everyone else → `reports/reports.php` (mirrors `$reports_url` in `includes/sidebar.php`).

Two exceptions that are **NOT shims** and hold live code:
- `transactions.php` (root) — a genuine second transaction implementation linked from `includes/sidebar.php`, near-identical logic to `cashier/transactions.php` but with its own styling. **Transaction behavior changes usually need applying to both.**
- `includes/cashier_header.php` — dead file, nothing includes it (live one is `cashier/header.php`).

There are **three parallel reports implementations** — `reports/reports.php` (branch-scoped, has Excel export), `admin/reports.php` (Owner dashboard), and the root shim.

### RBAC (core authorization model)
Spread across three `includes/` files:
- `permission_catalog.php` — **single source of truth** for permission names (e.g. `pos_access`, `inventory_manage`, `reports_view`), returned as an array keyed by permission name.
- `role_permission_matrix.php` — default role → permission list mapping.
- `rbac.php` — runtime engine; `hasPermission()` checks `$_SESSION['permissions']` (populated at login); `getAssignableRoles()` limits which roles a user may grant.

Roles (helpers in `includes/auth.php`): `admin` (System Owner, full access, can assign any role), `manager` (Admin), `inventory_staff`, `cashier`, `branch_owner` (checked via `isOwner()`, `isManager()`, `isCashier()`, `isInventoryStaff()`, `isBranchOwner()`). Non-owner roles can only assign the `cashier` role. **Gate features through `hasPermission()`/`requirePermission()`, not direct role checks.**

### Multi-branch model
Users belong to a branch (`$_SESSION['branch_id']`); `admin` can additionally "view as" a branch via `$_SESSION['branch_view_id']`/`branch_view_name` (set by `admin/switch_branch.php`). **Always use `getCurrentBranchId()` / `getCurrentBranchName()` from `includes/auth.php`** rather than reading `$_SESSION['branch_id']` directly — they account for the owner's branch-view override and fall back to a DB lookup.

### Database (key tables, grouped by domain)
- **Commerce**: `orders`, `order_items`, `sales_history`, `customers`, `cash_drop_log`
- **Inventory**: `inventory`, `inventory_batches`, `inventory_categories`, `inventory_counts`, `inventory_history`, `inventory_log`, `inventory_movements`, `inventory_orders` (+ `inventory_order_items`), `restock_requests`, `stock_receiving` (+ `_items`), `suppliers`, `ingredient_templates`, `cashier_inventory_counts`
- **Products/bom**: `products`, `product_ingredients`, `product_inventory_usage`
- **Identity/reporting**: `branches`, `users`, `roles`, `permission_logs`, `cashier_shifts`, `x_reading_log`, `z_reading_log`, `inventory_alerts`

### AI assistant
`ai/ai_helper.php` + `ai/ai_endpoint.php` implement a chat endpoint backed by the Groq API (`GROQ_API_KEY`), gated behind the `reports_view` permission. Exposed to admins as a floating chatbot widget (`admin/includes/ai_chatbot_widget.php`), not embedded per-page.

### Styling & responsive rules — Do NOT Break These
- **Never add Bootstrap or another CSS framework.** The codebase already uses `.card`, `.btn`, `.modal`, `.form-control`, `.table`, `.row`, `.container`, `.badge`, `.alert` with its own meanings (≈1,300 elements) — Bootstrap would restyle everything, and the CSP in `config.php` blocks CDN styles anyway (styles only allowed from `'self'`, `fonts.googleapis.com`, `unpkg.com`).
- Use the shared primitives from `assets/css/layout.css` (auto-imported via `admin.css`): `.l-app` (viewport-locked shell), `.l-fill*`, `.l-scroll`, `.l-pin`, `.l-split`, `.l-grid` (`--l-min`, `--l-grid--fixed2/3/4`), `.l-stack`, `.l-cluster`, `.l-table-wrap`, plus `u-` utilities (`.u-truncate`, `.u-nums`, `.u-nowrap`, `.u-hide-sm`).
- Pages NOT loading `admin.css` (need explicit `<link>` for primitives): `auth/login.php`, `auth/welcome.php`, `auth/unauthorized.php`, `cashier/receipt.php`.
- Standard breakpoints: **480 / 768 / 1024 / 1280px** (strays like 767/769/600/900 are off-by-one hacks — migrate them).
- The recurring CSS bug class is the flexbox `min-width/min-height: auto` default (a flex item refuses to shrink below content size) — see Section 7 of `layout.css`.
- Hover-lift effects (`transform: translateY(-Npx)` on `:hover`) must be neutralized under `@media (hover: none)` — on touchscreens `:hover` sticks.

### How to make change correctly
1. Locate the real implementation in a feature directory, never a root shim (except the two noted exceptions).
2. When a change touches transactions, x/z readings, reports, or inventory, check for parallel implementations (root vs `cashier/`, `reports/` vs `admin/`) and apply to all that share the behavior.
3. Use parameterized PDO queries, route DB writes through transaction + triggers where the schema expects it, keep CSRF tokens on state-changing forms, and respect RBAC permission gates.
4. Reuse `layout.css` primitives instead of writing new per-page media queries; verify responsive behavior at the four standard breakpoints and on touch.