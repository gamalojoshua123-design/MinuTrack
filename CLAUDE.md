# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Minute Burger POS — a PHP/MySQL point-of-sale and inventory management system, run under XAMPP (no framework, no build step, no package manager). Served at `http://localhost/minute1/`.

## Running the app

- Start Apache + MySQL via XAMPP.
- Copy `.env.example` to `.env` and fill in `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `GROQ_API_KEY` (used by the AI assistant). `.env` is git-ignored; never commit real keys.
- Database schema/seed: `pos_system (2).sql`, applied on top of/alongside the `migrations/` directory (mix of `.sql` files and one-off `.php` migration runner scripts, e.g. `005_rbac.php` seeds the permission catalog into the DB).
- There is no test suite, linter, or build tooling in this repo (no composer.json/package.json). Verify changes by exercising the pages in a browser against a local MySQL instance.

## Architecture

**Entry point chain**: every page requires `bootstrap.php`, which pulls in, in order: `config.php` (loads `.env`, defines DB/app constants, sets security headers and session hardening), `includes/db_connect.php` (creates the shared `$pdo` PDO connection), `includes/auth.php` (session/role helpers, requires `includes/rbac.php`), and `includes/functions.php` (shared helpers).

**Directory reorganization + legacy shims**: the app was reorganized from flat root-level PHP files into feature directories — `admin/`, `ai/`, `auth/`, `cashier/`, `inventory/`, `products/`, `reports/`, `users/`, `api/`, `tools/`. Most original root-level files (`admin.php`, `pos.php`, `products.php`, `ai_endpoint.php`, `cashier_inventory.php`, `inventory_view.php`, `unauthorized.php`, `reports.php`, etc.) are now short `header('Location: ...')` redirect shims into the new directories — **do not add real logic to these root files; edit the file in its feature directory instead.** Note `reports.php` is a role-aware shim (Owner → `admin/reports.php`, everyone else → `reports/reports.php`), mirroring `$reports_url` in `includes/sidebar.php`.

Two exceptions that are NOT shims and hold real, live code:
- `transactions.php` (root) — a genuine second implementation, linked from `includes/sidebar.php`; near-identical in logic to `cashier/transactions.php` but with its own styling. Changes to transaction behavior usually need applying to **both**.
- `includes/cashier_header.php` — dead file, nothing includes it (the live one is `cashier/header.php`).

There are also three parallel reports implementations — `reports/reports.php` (branch-scoped, has Excel export), `admin/reports.php` (Owner dashboard), and the root shim above.

**RBAC (role-based access control)**: this is the core authorization model, spread across three files in `includes/`:
- `permission_catalog.php` — single source of truth for permission names (e.g. `pos_access`, `inventory_manage`, `reports_view`), returned as an array keyed by permission name.
- `role_permission_matrix.php` — default mapping of role → list of permission names.
- `rbac.php` — runtime engine; `hasPermission()` checks the current user's granted permissions, which live in `$_SESSION['permissions']` (populated at login).

Roles (checked via `includes/auth.php` helpers `isOwner()`/`isAdmin()`, `isManager()`, `isCashier()`, `isInventoryStaff()`, `isBranchOwner()`) are `admin` (System Owner — full access, can assign any role), `manager` (Admin), `inventory_staff`, `cashier`, `branch_owner`. Non-owner roles can only assign the `cashier` role to new users (`getAssignableRoles()` in `rbac.php`). Permission checks should go through `hasPermission()`/`requirePermission()`, not direct role checks, when gating a feature.

**Multi-branch model**: users belong to a branch (`$_SESSION['branch_id']`); `admin` (owner) can additionally "view as" a specific branch via `$_SESSION['branch_view_id']`/`branch_view_name` (set by `admin/switch_branch.php`). Use `getCurrentBranchId()` / `getCurrentBranchName()` from `includes/auth.php` rather than reading `$_SESSION['branch_id']` directly, since it accounts for the owner's branch-view override and falls back to a DB lookup.

**AI assistant**: `ai/ai_helper.php` + `ai/ai_endpoint.php` implement a chat endpoint backed by the Groq API (`GROQ_API_KEY`), gated behind the `reports_view` permission. Exposed to admins as a floating chatbot widget (`admin/includes/ai_chatbot_widget.php`), not embedded per-page.

**Security headers/session config** are centralized in `config.php` (CSP, X-Frame-Options, session cookie hardening) — don't re-set these ad hoc in individual pages.

## Styling & responsive layout

**Do not add Bootstrap or another CSS framework.** The codebase already uses `.card` (259 usages), `.btn` (241), `.modal` (153), `.form-control` (113), `.table` (104), `.row`, `.container`, `.badge`, `.alert` and more with its own meanings — Bootstrap defines all of them and would restyle ~1,300 elements. The CSP in `config.php` also permits styles only from `'self'`, `fonts.googleapis.com` and `unpkg.com`, so a CDN link would be blocked.

**Use the shared primitives instead of writing new per-page media queries.** `assets/css/layout.css` provides `l-` (layout) and `u-` (utility) classes — prefixes chosen because nothing else in the codebase uses them:

- `.l-app` — viewport-locked shell (`100dvh` with `100vh` fallback; iOS Safari reports `100vh` taller than the visible area)
- `.l-fill` / `.l-fill--floor` / `.l-scroll` / `.l-pin` — flex regions that grow, scroll internally, or must never be squeezed
- `.l-split` — main/side columns that stack on tablet (`--l-side` sets the side width)
- `.l-grid` (`--l-min`) and `.l-grid--fixed2/3/4` — responsive tile grids
- `.l-stack` / `.l-cluster` — consistent vertical/horizontal groupings
- `.l-table-wrap` — wide tables scroll in their own box
- `.u-truncate`, `.u-nums`, `.u-nowrap`, `.u-hide-sm` — common utilities

It is `@import`ed from the top of `assets/css/admin.css`, so every page loading `admin.css` (~30) gets it automatically. `auth/login.php`, `auth/welcome.php`, `auth/unauthorized.php` and `cashier/receipt.php` do **not** load `admin.css` — add an explicit `<link>` if you need the primitives there.

**Standard breakpoints: 480 / 768 / 1024 / 1280px.** The codebase already leans on these (71 of ~91 existing queries); strays like 767/769/600/900px are off-by-one hacks worth migrating.

**The recurring bug in this codebase is the flexbox `min-width/min-height: auto` default** — a flex item refuses to shrink below its content size. It caused a POS cart name to overlap the quantity controls, and separately caused the cart item list to collapse to zero height behind a `flex-shrink: 0` sibling. Section 7 of `layout.css` documents the rules of thumb.

**Touch behaviour** lives in `assets/css/cashier-touch.css` (cashier pages) plus a global block at the end of `admin.css`. Critically, hover-lift effects (`transform: translateY(-Npx)` on `:hover`) must be neutralized under `@media (hover: none)` — on a touchscreen a tap applies `:hover` and it *stays* applied, so the control appears to jump up and stick.
