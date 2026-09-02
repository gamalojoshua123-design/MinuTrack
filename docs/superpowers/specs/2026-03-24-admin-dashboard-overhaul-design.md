# Admin Dashboard Overhaul — Design Spec

**Project:** Minute Burger POS System
**Scope:** Admin area only (`/admin/` files + shared includes/assets)
**Approach:** Rewrite admin files with security fixes, professional UI, and fully functional CRUD
**Root-level files (pos.php, login.php, etc.) remain untouched, except `ai_endpoint.php` which needs a minor CSRF header check update.**

---

## 0. Database Schema Changes

The following schema changes are required before implementation:

```sql
-- Add 'manager' to users role enum
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier';

-- Add user_id to inventory_history for audit tracking
ALTER TABLE inventory_history ADD COLUMN user_id INT NULL AFTER notes;
```

**Column naming:** The database uses `user_id` (varchar) as the login identifier. All admin code must use `user_id`, not `username`. The existing `admin/users.php` incorrectly references `username` — this will be fixed in the rewrite.

---

## 1. Security Fixes

### 1.1 Password Hashing
- Replace MD5 with `password_hash(PASSWORD_BCRYPT)` in `/admin/users.php` user creation and update
- **Known bug:** Existing `admin/users.php` lines 13 and 34 use `MD5()` in SQL for both INSERT and UPDATE — both must be replaced with PHP-side `password_hash()` before binding
- Keep existing bcrypt auto-upgrade logic in `auth.php` for legacy users

### 1.2 CSRF Protection
- New file: `includes/csrf.php`
- Generate token per session: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))`
- Embed hidden input in all forms: `<input type="hidden" name="csrf_token" value="...">`
- Send token in AJAX requests via header: `X-CSRF-Token`
- Validate on every POST request in admin pages before processing
- **Exception:** `ai_endpoint.php` (root-level) needs a minor update to accept and validate the `X-CSRF-Token` header, since `ai_chat.php` POSTs to it

### 1.3 Session Security
- Add `session_regenerate_id(true)` after successful login in `auth.php`
- Set cookie params in `admin/bootstrap.php` before `session_start()`: `httponly=true`, `samesite=Strict`

### 1.8 Manager Role Access
- Update `requireAdmin()` in root-level `bootstrap.php` to also allow `manager` role: `if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')`
- Alternatively, switch admin pages from `requireAdmin()` to permission-based checks via `requirePermission()` for granular control
- **Chosen approach:** Update `requireAdmin()` to accept managers, since managers should access the admin panel but with limited permissions enforced per-page

### 1.4 Input Validation
- Cast all `$_GET['id']` to `(int)` before database queries
- Whitelist allowed values for action parameters
- Sanitize string inputs with `trim()` and `htmlspecialchars()`
- Server-side validation on all form submissions (required fields, types, lengths)

### 1.5 File Upload Security
- Max file size: 2MB
- Allowed MIME types: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- Sanitize filename: `uniqid() . '.' . $ext`
- Validate file extension against whitelist: `jpg, jpeg, png, gif, webp`

### 1.6 Output Encoding
- Apply `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` on all user-facing output
- Create helper: `function e($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }`

### 1.7 Error Handling
- New file: `includes/error_handler.php`
- Log errors to `logs/error.log` with timestamp, file, line, message
- Display user-friendly error messages (no stack traces in production)
- Set `display_errors = 0` in admin bootstrap

---

## 2. UI/UX Design

### 2.1 Theme
- Bootstrap 5 (CDN) with custom admin theme
- Color scheme: dark navy sidebar (#1e293b), red/orange accents (#dc3545 / #fd7e14) for Minute Burger branding
- Light content area (#f8f9fa background)
- Clean typography with system font stack

### 2.2 Layout
- **Sidebar (left, 250px):** Dark navy, Minute Burger logo at top, icon + text nav links, active state highlight, collapsible on mobile via hamburger
- **Top bar (fixed):** White background, breadcrumbs on left, user dropdown + notification bell on right
- **Content area:** Padded container with page title and action buttons

### 2.3 Components
- **Stats cards:** 4 cards in a row on dashboard with icon, value, label, trend indicator. Color-coded borders (green=revenue, blue=orders, orange=low stock, purple=cashiers)
- **Charts:** Chart.js via CDN. Line chart (7-day sales), bar chart (top 5 products), doughnut (categories)
- **Data tables:** Bootstrap-styled tables with search input, column sort, client-side pagination via JS (10/25/50 per page) — no server-side pagination needed given expected data volumes
- **Modals:** Bootstrap modals for add/edit forms. Consistent layout: title, form fields, cancel/save buttons
- **Toasts:** Bootstrap toast notifications for success/error feedback, auto-dismiss after 3 seconds
- **Badges:** Status badges (active=green, inactive=red, low stock=orange)
- **Buttons:** Primary (red), secondary (gray), success (green), danger (red outline) for delete

### 2.4 Responsive
- Sidebar collapses to overlay on screens < 768px
- Cards stack vertically on mobile
- Tables become horizontally scrollable on small screens

---

## 3. Page-by-Page Functionality

### 3.1 Dashboard (`dashboard.php`)
**Stats Cards:**
- Today's Sales (sum of orders today)
- Today's Orders (count of orders today)
- Low Stock Items (count from `inventory` table where `quantity < min_stock`)
- Active Cashiers (count of active cashier users)

**Charts:**
- 7-day sales trend (line chart, data from orders table grouped by date)
- Top 5 products by quantity sold (bar chart, from order_items)
- Category sales breakdown (doughnut chart)

**Tables:**
- Low stock alerts: item name, current qty, min stock, category — with "Restock" link to inventory
- Recent 10 transactions: order number, cashier, total, date/time

### 3.2 Products (`products.php`)
**List View:**
- Table: image thumbnail, name, price, category, stock, status, actions (edit/delete)
- Search by name
- Filter by category dropdown
- "Add Product" button opens modal

**Add/Edit Modal:**
- Fields: name (required), price (required, number), category (select), stock (number), status (active/inactive), image upload with preview
- Client + server validation
- AJAX submit, toast on success/error

**Delete:**
- Confirmation modal before delete
- Soft delete (set status=inactive) or hard delete with confirmation

### 3.3 Inventory (`inventory.php`)
**List View:**
- Table: item name, category, quantity, unit, min stock, supplier, location, expiry, status, actions
- Search by name
- Filter by category, status
- Color-code rows: red if expired, orange if expiring within 7 days, yellow if low stock

**Add/Edit Modal:**
- Fields: item name, category (select), quantity, unit (select: pcs/kg/liters/packs), min stock, supplier, location rack, location shelf, expiry date, status
- AJAX submit

**Stock Adjustment:**
- Quick adjust button per item: opens small modal with +/- quantity and reason field
- Logs adjustment to `inventory_history` table using existing columns: `inventory_id`, `item_name`, `previous_quantity`, `new_quantity`, `quantity_change`, `change_type` (enum), `notes` (reason text), `created_at`
- **Schema addition:** Add `user_id` column to `inventory_history` to track who made the adjustment (see Section 0)

**Batch Tracking:**
- View batches per inventory item
- Batch number, quantity, expiry date

### 3.4 Users (`users.php`)
**List View:**
- Table: full name, user ID, role, email, status, created date, actions
- Filter by role, status

**Add/Edit Modal:**
- Fields: full name, user_id (unique, used as login identifier — NOT `username`), password (bcrypt via PHP `password_hash()`), role (select: admin/cashier/manager), email, status
- Password field: required on add, optional on edit (leave blank to keep current)
- Permission checkboxes: dashboard_view, products_view, products_manage, inventory_view, inventory_manage, pos_access, transactions_view, reports_view, users_manage, archive_view

**Delete/Deactivate:**
- Soft delete — set status to inactive (replaces existing hard DELETE behavior)
- **Note:** Existing code does `DELETE FROM users` — changing to soft delete preserves referential integrity with `orders.cashier_id`
- Cannot deactivate own account

### 3.5 Cashiers (`cashiers.php`)
**Overview Cards:**
- Total cashiers, active today, total revenue today

**Per-Cashier Table:**
- Cashier name, total orders, total revenue, average order value, last active
- Click row to expand/view shift history

**Shift History (AJAX-loaded expandable rows):**
- Click a cashier row to expand and load shift history via AJAX
- Table: shift date, start time, end time, opening cash, closing cash, total sales, order count

### 3.6 Reports (`reports.php`)
**Date Range Picker:**
- Quick selects: Today, Yesterday, Last 7 Days, This Month, Last Month, Custom Range
- Start date + end date inputs

**Sales Summary:**
- Total revenue, total orders, average order value for selected period
- Daily sales line chart for the period

**Top Products:**
- Table: rank, product name, quantity sold, revenue — for selected period

**Cashier Performance:**
- Table: cashier name, orders, revenue, avg order value — for selected period

**Export:**
- "Export CSV" button for each report table
- Client-side CSV generation from table data

### 3.7 Product Ingredients (`product_ingredients.php`)
**List View:**
- Table: product name, number of ingredients mapped, actions
- Click to expand ingredient list

**Mapping Modal:**
- Select product, then add inventory items with quantity_used per unit of product
- Save maps to product_inventory_usage table
- AJAX submit

### 3.8 Inventory Recipes (`inventory_recipes.php`)
**Purpose:** Configure which inventory items are consumed when a product is sold.

**List View:**
- Table: product name, recipe item count, total cost per unit, actions
- Search by product name

**Recipe Editor Modal:**
- Select product at top
- Add/remove inventory items with quantity_used and unit
- Show calculated cost per product unit
- Save to `product_inventory_usage` table
- AJAX submit

**Note:** This page currently uses a different bootstrap path and layout — will be rewritten to use `admin/bootstrap.php` and the shared sidebar/header like all other admin pages.

### 3.9 AI Chat (`ai_chat.php`)
**Chat Interface:**
- Clean message bubbles (user on right, AI on left)
- Input field with send button at bottom
- Loading spinner during API call
- Error message display if API fails
- Message history within current session (JS array, not persisted)
- Suggested prompts: "Today's sales summary", "Low stock items", "Top selling products"

---

## 4. Error Handling & Logging

### 4.1 Error Handler (`includes/error_handler.php`)
- `set_error_handler()` and `set_exception_handler()` for uncaught errors
- Log format: `[2026-03-24 10:30:00] ERROR in /admin/products.php:45 — Message here`
- Log to `logs/error.log`
- In admin pages: show generic "Something went wrong" with option to retry

### 4.2 AJAX Response Format
All AJAX endpoints return consistent JSON:
```json
{
  "success": true|false,
  "message": "Human-readable message",
  "data": {}
}
```

### 4.3 Form Validation
- Client-side: HTML5 required/pattern attributes + JS validation before submit
- Server-side: validate all fields, return specific error messages per field
- Display inline error messages under form fields

### 4.4 Flash Messages
- `$_SESSION['flash'] = ['type' => 'success|error', 'message' => '...']`
- Display as toast on next page load, then clear from session

---

## 5. File Structure

### New Files
```
includes/csrf.php           — CSRF token generation and validation
includes/error_handler.php  — Centralized error handling and logging
logs/error.log              — Error log file (gitignored)
```

### Rewritten Files
```
admin/bootstrap.php         — Auth + CSRF + error handler initialization
admin/dashboard.php         — Full dashboard with charts and stats
admin/inventory.php         — Full CRUD with batch tracking
admin/products.php          — Full CRUD with image upload
admin/users.php             — Full CRUD with bcrypt and permissions
admin/cashiers.php          — Performance dashboard
admin/reports.php           — Reports with date range and export
admin/ai_chat.php           — Clean chat interface
admin/product_ingredients.php — Ingredient mapping
admin/inventory_recipes.php — Recipe management
admin/includes/header.php   — New top bar
admin/includes/sidebar.php  — New dark sidebar
assets/css/admin.css        — Full admin theme
assets/js/admin-dashboard.js — Charts, AJAX, toasts, validation
```

### Updated Files
```
includes/auth.php           — Add session_regenerate_id, fix bcrypt
includes/functions.php      — Add e() helper, formatMoney() helper, validation helpers, replace flash message format
includes/constants.php      — Add 'manager' to ROLES array
bootstrap.php (root-level)  — Update requireAdmin() to allow manager role
ai_endpoint.php             — Add CSRF token header validation (minor update)
```

**Note on bootstrap files:** There are TWO bootstrap files:
- `/bootstrap.php` (root-level) — contains `requireAdmin()`, needs manager role update
- `/admin/bootstrap.php` — admin-specific init, will be rewritten with CSRF + error handler + session cookie params

### Removed Files
```
admin/check_files.php       — Debug/diagnostic file that exposes file system info (security risk)
```

### Untouched Files
```
Root-level: pos.php, index.php, login.php, logout.php, admin.php,
            inventory.php, products.php, users.php, reports.php,
            archive.php, transactions.php, ai_helper.php, config.php
includes/: db_connect.php, database.php
```

### Database Migration
```
Run the ALTER TABLE from Section 0 before deploying the admin changes.
```

---

## 6. UI Migration Note

The existing admin uses custom CSS classes (`.stat-card`, `.admin-layout`, `.sidebar`, `.data-table`) and Boxicons. This overhaul is a **full migration to Bootstrap 5** — all HTML templates will be rewritten with Bootstrap classes (`container`, `row`, `col`, `table`, `btn`, `modal`, etc.). Boxicons will be replaced with Bootstrap Icons. The custom `admin.css` will be rewritten to contain only theme overrides on top of Bootstrap.

## 7. Dependencies (CDN)
- Bootstrap 5.3 CSS + JS
- Bootstrap Icons (replaces Boxicons)
- Chart.js 4.x (loaded on dashboard and reports pages)
- No additional PHP dependencies
