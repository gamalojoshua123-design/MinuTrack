# Branch Owner Dashboard & Multi-Branch Management — Design Spec

**Date:** 2026-03-24
**Project:** Minute Burger POS System
**Tech Stack:** PHP 8.2 / MySQL / Vanilla JS / Chart.js / Custom CSS

---

## 1. Overview

Add multi-branch support to the Minute Burger POS system. Introduces a new "Branch Owner" role with a dedicated dashboard showing sales, products sold, delivery tracking, inventory (read-only), and staff performance per branch — with Chart.js visualizations and a branch comparison mode. Admin gains branch management capabilities (create/edit branches, assign owners).

## 2. Architecture: Branch-Scoped Database

Single database with `branch_id` foreign key added to existing tables. All queries filter by branch. Existing data migrates to a default "Jasaan Branch" (id=1).

### 2.1 New Tables

#### `branches`
| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | e.g., "Jasaan Branch" |
| address | VARCHAR(255) | Branch location |
| phone | VARCHAR(20) | Contact number |
| status | ENUM('active','inactive') | Soft delete via deactivation |
| created_at | TIMESTAMP | Default CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | On update CURRENT_TIMESTAMP |

#### `branch_users`
| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT | Primary key |
| branch_id | INT | FK → branches.id |
| user_id | INT | FK → users.id |
| created_at | TIMESTAMP | Default CURRENT_TIMESTAMP |

Unique constraint on (branch_id, user_id).

### 2.2 Existing Tables — Add `branch_id` Column

All get `INT NOT NULL DEFAULT 1` with FK → branches.id:

- `orders`
- `products`
- `product_ingredients` (recipes are per-branch since products are per-branch)
- `product_inventory_usage` (usage tracking per-branch)
- `inventory`
- `inventory_deliveries`
- `inventory_batches`
- `inventory_movements`
- `inventory_history`
- `inventory_alerts`
- `inventory_categories`
- `inventory_orders`
- `inventory_order_items`
- `inventory_log`
- `cashier_shifts`
- `sales_history`
- `x_reading_log`
- `z_reading_log`
- `cash_drop_log`
- `restock_requests`

### 2.3 Migration Strategy

1. Create `branches` table
2. Insert default branch: `INSERT INTO branches (id, name, address, phone, status) VALUES (1, 'Jasaan Branch', 'Jasaan, Misamis Oriental', '', 'active')`
3. Add `branch_id` column to each table with `DEFAULT 1`
4. Add foreign key constraints
5. Create `branch_users` table
6. **ALTER `users.role` ENUM** — current DB only has `ENUM('admin','cashier')`. Must change to: `ALTER TABLE users MODIFY COLUMN role ENUM('admin','cashier','manager','branch_owner') DEFAULT 'cashier'`
7. **Add `received_date` column to `inventory_deliveries`** — currently only has `expected_date` and `created_at`. Add `received_date DATE NULL` for tracking actual delivery receipt.
8. **Add `order_date` column to `inventory_deliveries`** — if not already present, add `order_date DATE NULL` for tracking when the order was placed.
9. Update existing queries to include `branch_id` filtering

## 3. Role: Branch Owner

### 3.1 Role Definition

A new fourth role added to the system alongside Admin, Manager, and Cashier.

**Permissions:**
- `branch_dashboard_view` — access dashboard
- `branch_sales_view` — view sales data and charts
- `branch_products_view` — view products sold and performance
- `branch_delivery_view` — view delivery tracking and status
- `branch_inventory_view` — view inventory levels (read-only)
- `branch_staff_view` — view cashier/staff performance

**Restrictions:**
- Cannot access admin panel
- Cannot access POS
- Cannot manage users, products, or inventory
- Can only see data for assigned branches

### 3.2 Authentication & Routing

Login flow in `login.php`:
1. Authenticate user (existing flow)
2. Check role (full routing table for all 4 roles):
   - Admin → `admin.php`
   - Manager → `admin.php` (same panel, limited permissions)
   - Cashier → `pos.php`
   - **Branch Owner → `branch_owner/dashboard.php`**
3. Session stores: `user_id`, `role`, `branch_ids[]` (array of assigned branch IDs)

Note: Current `login.php` only handles admin vs non-admin. Must be updated to a full role-based conditional.

### 3.3 Access Control

`branch_owner/bootstrap.php` — loaded on every branch owner page:
- Verify session is active and role is `branch_owner`
- Load assigned branch IDs into session
- Provide helper: `get_branch_filter($pdo)` returns a WHERE clause scoping queries to assigned branches
- Redirect unauthorized access to login

## 4. Branch Owner Dashboard

### 4.1 Layout Structure

```
┌─────────────────────────────────────────────────────┐
│ Top Bar: Logo | "Branch Owner Panel" | Branch Dropdown | User │
├──────────┬──────────────────────────────────────────┤
│ Sidebar  │ Branch Tabs: [Jasaan] [Cagayan] [Compare]│
│          │                                          │
│ Dashboard│ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐    │
│ Sales    │ │Today │ │Prod. │ │Avg   │ │Low   │    │
│ Products │ │Sales │ │Sold  │ │Deliv.│ │Stock │    │
│ Deliver. │ └──────┘ └──────┘ └──────┘ └──────┘    │
│ Inventory│                                          │
│ Staff    │ ┌─────────────────┐ ┌─────────────┐     │
│          │ │ Sales Trend     │ │ Top Products│     │
│ Logout   │ │ (Line Chart)    │ │ (Pie Chart) │     │
│          │ └─────────────────┘ └─────────────┘     │
│          │                                          │
│          │ ┌─────────────────┐ ┌─────────────┐     │
│          │ │ Recent Delivers │ │ Staff Duty  │     │
│          │ └─────────────────┘ └─────────────┘     │
└──────────┴──────────────────────────────────────────┘
```

### 4.2 Stat Cards (4 cards)

1. **Today's Sales** — `SUM(total_amount) FROM orders WHERE branch_id=? AND DATE(date_time)=CURDATE()` with % change vs yesterday. If no sales yet, show ₱0 with "No sales today" label.
2. **Products Sold** — `SUM(oi.quantity) FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE o.branch_id=? AND DATE(o.date_time)=CURDATE()` with comparison to daily average. Note: `order_items` has no timestamp — always join through `orders.date_time`.
3. **Avg Delivery Time** — `AVG(DATEDIFF(received_date, order_date)) FROM inventory_deliveries WHERE branch_id=? AND status='completed' AND received_date IS NOT NULL` for last 5 deliveries. Uses new `received_date` and `order_date` columns added in migration. If no completed deliveries, show "No data yet".
4. **Low Stock Items** — `COUNT(*) FROM inventory WHERE branch_id=? AND quantity <= min_stock`

**Empty state handling:** When a branch has no data (newly created), all stat cards show 0 or "No data yet" with a friendly message. Charts display an empty state placeholder ("No sales data to display"). % change calculations guard against division by zero (show "N/A" instead).

### 4.3 Date Filters

Global date filter applied to all stat cards and charts:
- Today
- This Week (Mon–Sun)
- This Month
- Custom Range (date picker)

### 4.4 Branch Tabs

- One tab per assigned branch
- "Compare" tab for side-by-side view
- Active tab highlighted in brand orange (#F37902)
- Clicking a tab reloads dashboard data for that branch via AJAX
- **Single branch case:** If branch owner has only 1 assigned branch, hide the tab bar entirely (no tabs needed, no Compare option)
- **3+ branches in Compare mode:** Show a branch-pair selector — two dropdowns letting the owner pick which two branches to compare side by side

## 5. Charts (Chart.js)

### 5.1 Per-Branch Charts

| Chart | Type | Data Source | Filter |
|-------|------|-------------|--------|
| Daily Sales Trend | Line | orders.total_amount grouped by date_time | 7/14/30 days |
| Top Products by Revenue | Doughnut | order_items JOIN products, top 10 | Date range |
| Monthly Sales | Bar | orders.total_amount grouped by month | Last 6/12 months |
| Products by Category | Horizontal Bar | order_items grouped by category | Date range |
| Delivery Performance | Line | inventory_deliveries avg time by week | Last 8 weeks |

### 5.2 Compare Mode

When "Compare" tab is active:
- Side-by-side bar charts for two selected branches
- Metrics compared: total sales, products sold, avg delivery time
- Same date range applied to both
- Color-coded per branch

### 5.3 Chart Configuration

- Responsive: `responsive: true, maintainAspectRatio: false`
- Brand colors: primary #F37902, secondary #DC6902, accent #FAE51D
- Tooltips with formatted peso amounts (₱)
- Legend positioned bottom on mobile, right on desktop

## 6. Branch Owner Pages

### 6.1 File Structure

```
branch_owner/
├── bootstrap.php          # Auth check, branch scoping
├── dashboard.php          # Main dashboard with stats + charts
├── sales.php              # Detailed sales table with filters
├── products.php           # Products sold breakdown
├── deliveries.php         # Delivery tracking and history
├── inventory.php          # Read-only inventory view
├── staff.php              # Cashier/staff performance
├── includes/
│   ├── header.php         # Top bar with branch dropdown
│   └── sidebar.php        # Navigation sidebar
├── api/
│   ├── dashboard_stats.php    # AJAX: stat card data
│   ├── sales_data.php         # AJAX: sales chart data
│   ├── products_data.php      # AJAX: products chart data
│   ├── delivery_data.php      # AJAX: delivery data
│   ├── inventory_data.php     # AJAX: inventory data
│   ├── staff_data.php         # AJAX: staff performance
│   └── compare_data.php       # AJAX: branch comparison
└── assets/
    ├── css/
    │   └── branch-owner.css   # Dashboard styles
    └── js/
        └── branch-owner.js    # Charts, AJAX, interactions
```

### 6.2 Sales Page

- Filterable table: date range, product, category
- Columns: Date, Order #, Items, Total, Cashier
- Summary row with totals
- **Pagination:** 25 rows per page with page navigation (prev/next + page numbers)
- Export: print-friendly CSS

### 6.3 Products Page

- Product performance table: Name, Category, Qty Sold, Revenue, Avg/Day
- Sortable columns
- Bar chart: top 10 products

### 6.4 Deliveries Page

- Delivery list with status badges: Ordered → In Transit → Received
- Columns: Supplier, Items, Ordered Date, Received Date, Delivery Time
- Status filter (All, Pending, In Transit, Received)
- Average delivery time stat card

### 6.5 Inventory Page (Read-Only)

- Table: Item Name, Category, Current Stock, Min Stock, Status
- Status badges: OK (green), Low (orange), Critical (red)
- No edit/add/delete buttons — view only
- Low stock items highlighted

### 6.6 Staff Page

- Cashier performance table: Name, Shift, Total Sales, Orders Processed, Avg Order Value
- Current shift status: On Duty / Off Duty
- Date range filter

## 7. Admin Branch Management

### 7.1 New Admin Page: `admin/branches.php`

Added to admin sidebar as "Branches" menu item.

**Branch list view:**
- Table: Name, Address, Phone, Status, Owners, Actions
- Create, Edit, Deactivate buttons

**Create/Edit branch form:**
- Fields: Name, Address, Phone, Status
- Save → INSERT/UPDATE branches table

**Branch overview cards:**
- One card per active branch showing quick stats (today's sales, products sold, stock alerts)

### 7.2 User Management Changes

In `admin/users.php` — when creating/editing a user:
- New role option: "Branch Owner" in role dropdown
- When selected, a multi-select dropdown appears for branch assignment
- On save: insert/update `branch_users` records

### 7.3 Auth & Constants Updates

In `includes/constants.php`:
- Add `'branch_owner'` to `USER_ROLES`

In `includes/auth.php`:
- Add `isBranchOwner()` function (matching existing `isAdmin()`, `isManager()`, `isCashier()` pattern)
- Add branch_owner default permissions to `getDefaultPermissions()` function (this is where permissions actually live, not constants.php)

In `admin/users.php`:
- **Fix password hashing** — current code uses `MD5(?)` for new users and password updates. Must change to `password_hash()` (bcrypt) to match the upgrade logic already in `auth.php`. This affects all new users including branch owners.

## 8. Responsive Design

### 8.1 Breakpoints

| Breakpoint | Layout |
|------------|--------|
| 1024px+ (Desktop) | Full sidebar (200px) + content, 4-column stat cards, 2-column charts |
| 768–1024px (Tablet) | Collapsible sidebar (icon-only), 2-column stat cards, charts stack |
| 480–768px (Mobile) | Hamburger menu, single column, full-width charts, swipeable branch tabs |
| <480px (Small) | Compact stat cards (2x2 grid), simplified tables with horizontal scroll |

### 8.2 Implementation

- CSS Grid for stat cards: `grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))`
- Flexbox for sidebar + content layout
- Media queries matching existing project breakpoints
- Touch-friendly: minimum 44px tap targets on mobile
- Charts: `responsive: true` with Chart.js, canvas resizes automatically

## 9. Styling

Follow existing project design system:
- Primary: #F37902 (Harvest Orange)
- Secondary: #DC6902 (Chocolate Brown)
- Accent: #FAE51D (Bright Lemon)
- Sidebar: #2c2c2c dark
- Cards: white with `box-shadow: 0 2px 8px rgba(0,0,0,0.08)`, `border-radius: 10px`
- Icons: Boxicons 2.1.4
- Font: system default (matching existing admin panel)

## 10. Security

- All branch owner queries filtered by assigned `branch_ids` from session
- Branch owner cannot access other branches' data even via direct URL manipulation
- API endpoints validate branch ownership before returning data
- Admin-only operations protected by role check
- SQL injection prevention: all queries use PDO prepared statements
- XSS prevention: all output HTML-escaped with `htmlspecialchars()`
- **CSRF protection:** All POST forms (admin branch create/edit/deactivate, user assignment) include a session-based CSRF token validated on submission
- **Branch deactivation handling:** When a branch is deactivated, branch owners assigned only to that branch see an "inactive branch" message on login. Admin receives a warning before deactivating a branch that has assigned owners.

### 10.1 AJAX Error Handling

All API endpoints return JSON with consistent format:
- Success: `{"status": "success", "data": {...}}`
- Error: `{"status": "error", "message": "..."}`
- HTTP 401 for auth failures (session expired), HTTP 403 for branch access denied
- JS client shows toast notification on errors, redirects to login on 401

## 11. What's NOT In Scope

- Inventory editing by branch owner (read-only as requested)
- Customer delivery tracking (deliveries are supplier → branch only)
- Real-time notifications / WebSocket updates
- Multi-language support
- Mobile app / PWA
