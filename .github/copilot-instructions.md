# Copilot Instructions — Minute Burger POS

## Project Overview

**Minute Burger POS** is a PHP/MySQL point-of-sale and inventory management system. It runs standalone under XAMPP (no framework, no build step, no package manager) and is served at `http://localhost/minute1/`.

- **Stack**: PHP 7.4+, MySQL 5.7+, vanilla JS, custom CSS
- **Test/Lint**: None — verify changes in browser against local MySQL instance
- **Dependencies**: None (no Composer, no npm)

## Getting Started

### Setup

1. Start Apache + MySQL via XAMPP
2. Copy `.env.example` to `.env` and fill in:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (local MySQL connection)
   - `GROQ_API_KEY` (from https://console.groq.com/keys; used by the AI assistant)
3. Apply database schema: import `pos_system (2).sql` into MySQL, then run migration scripts in `migrations/` (mix of `.sql` files and PHP scripts like `005_rbac.php`)
4. Open `http://localhost/minute1/` in browser

### Verification

Since there's no test suite or linter, **verify all changes in the browser** against a live MySQL instance. After editing:

1. Check the relevant page loads without errors
2. Test the specific feature you modified
3. Verify UI layout/responsiveness on mobile (cashier pages especially)

## Architecture

### Entry Point Chain

Every page requires `bootstrap.php`, which orchestrates:

1. **`config.php`** — Loads `.env`, defines `DB_*` and `GROQ_API_KEY` constants, sets security headers (CSP, X-Frame-Options) and session hardening
2. **`includes/db_connect.php`** — Creates the shared `$pdo` PDO connection
3. **`includes/auth.php`** — Session and role helpers, requires `includes/rbac.php`
4. **`includes/functions.php`** — Shared utility functions

### Directory Reorganization & Legacy Shims

The codebase transitioned from flat root files to a feature-directory structure: `admin/`, `ai/`, `auth/`, `cashier/`, `inventory/`, `products/`, `reports/`, `users/`, `api/`, `tools/`.

**Most root-level files are redirect shims** (`admin.php` → `admin/`, `pos.php` → `cashier/`, etc.). **Do not add logic to these shims; edit the real file in its feature directory instead.**

**Exceptions (live code in root):**
- `transactions.php` — Genuine second implementation (near-identical to `cashier/transactions.php` but different styling). **Changes to transaction logic must be applied to both.**
- `includes/cashier_header.php` — Dead file; use `cashier/header.php` instead

**Parallel implementations:**
- Three separate reports UIs exist: `reports/reports.php` (branch-scoped, Excel export), `admin/reports.php` (Owner dashboard), `reports.php` (shim)

### RBAC (Role-Based Access Control)

Authorization is spread across three files in `includes/`:

- **`permission_catalog.php`** — Single source of truth for permission names (e.g., `pos_access`, `inventory_manage`, `reports_view`); returned as an array
- **`role_permission_matrix.php`** — Default mapping of role → permission names
- **`rbac.php`** — Runtime engine; `hasPermission()` checks `$_SESSION['permissions']` (populated at login)

**Roles:**
- `admin` (System Owner) — Full access; can assign any role
- `manager` (Admin) — Manage users and data
- `inventory_staff` — Inventory operations
- `cashier` — POS operations
- `branch_owner` — Branch-level admin

**Helpers in `includes/auth.php`:** `isOwner()`, `isAdmin()`, `isManager()`, `isCashier()`, `isInventoryStaff()`, `isBranchOwner()`

**When adding a feature:** Use `hasPermission()` or `requirePermission()` to gate it, not role checks. If adding a new permission, register it in `permission_catalog.php` and update the role matrix.

### Multi-Branch Model

Users belong to a branch (`$_SESSION['branch_id']`). Owners can "view as" a specific branch via `$_SESSION['branch_view_id']`/`branch_view_name` (set by `admin/switch_branch.php`).

**Always use `getCurrentBranchId()` and `getCurrentBranchName()`** from `includes/auth.php` instead of reading `$_SESSION['branch_id']` directly — these helpers account for the owner's view override.

### AI Assistant

- **Core:** `ai/ai_helper.php` (Groq API integration) + `ai/ai_endpoint.php` (chat endpoint)
- **Auth:** Gated behind `reports_view` permission
- **UI:** Floating chatbot widget in `admin/includes/ai_chatbot_widget.php` (admins only; not embedded per-page)
- **Config:** Requires `GROQ_API_KEY` in `.env`

## Key Conventions

### Security

**DO NOT re-set security headers in individual pages.** They are centralized in `config.php`:
- CSP policy (permits `'self'`, `fonts.googleapis.com`, `unpkg.com` only)
- X-Frame-Options
- Session cookie hardening (HttpOnly, Secure, SameSite)

**Environment variables:** `.env` is git-ignored. Never commit real keys; use `.env.example` as a template.

### Styling & Responsive Layout

**Do NOT add Bootstrap or another CSS framework.** The codebase already uses custom `.card`, `.btn`, `.modal`, `.form-control`, `.table`, `.badge`, `.alert` classes (~1,300 elements). Bootstrap would restyle them all.

**CSP blocks external stylesheets.** Only `'self'`, `fonts.googleapis.com`, and `unpkg.com` are permitted; verify any new CDN link won't be blocked.

**Use shared layout primitives** from `assets/css/layout.css` (prefixed `.l-` for layout, `.u-` for utility):

- `.l-app` — Viewport-locked shell (`100dvh` with `100vh` fallback; avoids iOS Safari height bug)
- `.l-fill` / `.l-fill--floor` / `.l-scroll` / `.l-pin` — Flex regions (grow, scroll, pin)
- `.l-split` — Main/side columns (stack on tablet; set side width with `--l-side` CSS var)
- `.l-grid` (with `--l-min`) and `.l-grid--fixed2/3/4` — Responsive tile grids
- `.l-stack` / `.l-cluster` — Vertical/horizontal grouping with consistent spacing
- `.l-table-wrap` — Wide tables scroll in their own box
- `.u-truncate`, `.u-nums`, `.u-nowrap`, `.u-hide-sm` — Common utilities

Imported at the top of `assets/css/admin.css`, so pages loading that CSS get the primitives automatically. **Exception:** `auth/login.php`, `auth/welcome.php`, `auth/unauthorized.php`, `cashier/receipt.php` do NOT load `admin.css` — add an explicit `<link>` if needed.

**Standard breakpoints:** `480 / 768 / 1024 / 1280px` (71 of ~91 existing media queries use these). Avoid off-by-one hacks like 600/767/900px.

### Common Flexbox Bug

**Recurring issue:** Flex items default to `min-width: auto` / `min-height: auto`, preventing them from shrinking below their content size.

- Caused POS cart item names to overlap quantity controls
- Caused cart item list to collapse behind a `flex-shrink: 0` sibling

**Solution:** Set `min-width: 0` / `min-height: 0` on flex children that need to shrink. See Section 7 of `layout.css` for rules of thumb.

### Touch Behavior

Touch-specific styles live in:
- `assets/css/cashier-touch.css` (cashier pages)
- Global block at end of `assets/css/admin.css`

**Critical:** Neutralize hover-lift effects (`transform: translateY(-Npx)`) under `@media (hover: none)`. On touchscreen, a tap applies `:hover` and **it stays applied**, making the control appear to jump and stick.

## File Structure

```
.
├── bootstrap.php              → Required by every page; orchestrates config, DB, auth, functions
├── config.php                 → Env loading, constants, security headers
├── index.php                  → Dashboard entry point
├── login.php, welcome.php     → Auth shims
│
├── admin/                     → Owner/system admin pages (dashboards, user mgmt, branch switching)
├── ai/                        → AI chat endpoint & helper
├── auth/                      → Login, welcome, auth flows
├── cashier/                   → POS terminal (cart, transactions, receipts)
├── inventory/                 → Stock management, counts, reports
├── products/                  → Product catalog, categories, suppliers
├── reports/                   → Analytics (branch-scoped; Owner views use admin/reports.php)
├── users/                     → User CRUD
├── api/                       → REST endpoints (AJAX)
├── tools/                     → Utility scripts (backups, etc.)
│
├── includes/                  → Shared code
│   ├── bootstrap.php          → Loading chain (imported by bootstrap.php at root)
│   ├── db_connect.php         → PDO connection
│   ├── auth.php               → Session/role helpers, login flow
│   ├── functions.php          → Shared utilities
│   ├── rbac.php               → Permission checking engine
│   ├── permission_catalog.php → All permission names (source of truth)
│   ├── role_permission_matrix.php → Default role→permission mapping
│   ├── sidebar.php            → Navigation
│   ├── header.php             → Page header/metadata
│   └── ...
│
├── assets/
│   ├── css/
│   │   ├── admin.css          → Main stylesheet (loads layout.css)
│   │   ├── layout.css         → .l-* and .u-* primitives
│   │   ├── cashier-touch.css  → Touch-specific rules
│   │   └── ...
│   ├── js/                    → Vanilla JS modules
│   └── img/
│
├── migrations/                → DB schema migrations (.sql files + .php runners)
├── docs/                      → Docs (if any)
├── pos_system (2).sql         → Full DB schema + seed data
│
├── .env.example               → Template (copy → .env, git-ignored)
└── .github/
    └── copilot-instructions.md → This file
```

## Common Workflows

### Adding a Feature Behind a Permission

1. Register permission in `includes/permission_catalog.php`
2. Add it to roles in `includes/role_permission_matrix.php`
3. In your feature code: `requirePermission('your_permission_name');` or check `hasPermission()`
4. Verify browser load and feature access for correct roles

### Editing Transaction Logic

**Both `transactions.php` (root) and `cashier/transactions.php` must be kept in sync** — they have identical core logic but different styling.

### Adding Styling

- **Global:** Edit `assets/css/admin.css` or add to `layout.css` (for reusable primitives)
- **Cashier pages:** Also update `assets/css/cashier-touch.css` for touch-friendly behavior
- Use layout primitives (`.l-*`, `.u-*`) from `layout.css`; avoid new media queries unless unavoidable
- Test responsiveness at `768px` (tablet) and `480px` (mobile/cashier)

### Connecting Frontend to Backend

- Create endpoint in `api/` folder
- Call from frontend via `fetch()` or AJAX
- Gate backend with `requirePermission()` or role checks
- Verify both in browser and with multiple user roles

## SQL & Database

- **File:** `pos_system (2).sql` (full schema + initial seed)
- **Migrations:** `migrations/` directory (`.sql` files + `*.php` runners)
- **Connection:** `$pdo` global (created in `includes/db_connect.php`)
- **Apply changes:** Either as `.sql` migration files or `.php` runner scripts (see existing `005_rbac.php` for pattern)

Use prepared statements with `$pdo->prepare()` and bound parameters to prevent SQL injection.

## Environment & Dependencies

- **PHP:** 7.4+
- **MySQL:** 5.7+
- **No Composer, npm, or build tooling** — files are served as-is
- **External CDNs:** Only `fonts.googleapis.com` and `unpkg.com` permitted by CSP
- **Groq API:** Optional; required for AI assistant (get key from https://console.groq.com/keys)

## Notes for Copilot

- **No linter/formatter** — follow existing code style by example
- **No test suite** — verify changes in browser
- **No framework** — vanilla PHP, standard PSP patterns (require/include, function-based)
- **Git-ignore .env** — never commit real keys
- **Existing CLAUDE.md** — Contains additional context on architecture and styling; refer to it for clarification
