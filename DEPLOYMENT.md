# MinuTrack — Deployment Guide

How to run the existing MinuTrack (Minute Burger POS) on a normal PHP + MySQL web host so it can be used from a PC, Android phone, and iPhone. The application code is deployed as-is; there is no build step.

```
GitHub (source) ──► PHP web host (Apache + PHP + MySQL/MariaDB, HTTPS)
                         │
                   MinuTrack POS  ──►  PC / Android / iPhone (any browser)
```

> **GitHub Pages cannot host MinuTrack.** It serves static files only and cannot run PHP or MySQL. GitHub is only where the source code lives. This repo intentionally has no deployment workflow in `.github/workflows/`.

---

## 1. Requirements

| Item | Requirement |
|---|---|
| Hosting type | Shared/cPanel hosting, a VPS, or any Apache host with PHP + MySQL and **HTTPS** (Hostinger, Namecheap, SiteGround, InfinityFree, a DigitalOcean LAMP droplet, etc.) |
| Web server | **Apache 2.4** with `.htaccess` enabled (`AllowOverride All`). No `mod_rewrite` needed. |
| PHP | **8.1 or newer** (developed and tested on **8.2**). PHP 7.x will not work because the code uses `str_starts_with()`/`str_contains()`. |
| Database | **MariaDB 10.4+** (the dumps come from 10.4.32) or **MySQL 8.0+** |
| PHP extensions | `pdo_mysql` (required), `curl` (AI assistant), `json`, `session`, `zlib` (compressed backups). These are enabled by default on almost every host. |
| Cron jobs | None required |

---

## 2. Important: the URL path must be `/minute1/`

The app hardcodes the path `/minute1/` in about 40 places (stylesheets, redirects, logo, sidebar links, the AI chatbot endpoint, product image URLs). **Upload the files into a folder named `minute1`** inside the web root:

```
public_html/
└── minute1/          ← all MinuTrack files go here
    ├── index.php
    ├── config.php
    ├── .htaccess
    ├── .env          ← you create this on the server
    └── ...
```

The app is then served at **`https://your-domain.com/minute1/`**.

If you install it at the domain root (`https://pos.your-domain.com/`) or in a folder with a different name, pages will load **without CSS** and redirects will return 404. Supporting another path means replacing the hardcoded `/minute1/` references with a configurable base path. That is a separate code change and has not been made.

---

## 3. Database

### Which SQL file to import

**Import `database_pos_system.sql`.** Do not import `pos_system (2).sql`.

| | `database_pos_system.sql` | `pos_system (2).sql` |
|---|---|---|
| Exported | Aug 31, 2026 | Jul 16, 2026 (older) |
| Tables | 35 | 32 |
| RBAC tables (`permissions`, `role_permissions`, `audit_logs`) | ✅ | ❌ (login/permission pages fail) |
| `login_rate_limits` (used by login) | ✅ | ❌ |
| `backup_download_tokens` | ✅ | ❌ |
| `users.role_id`, `roles.slug` | ✅ | ❌ |
| Obsolete `inventory_orders` tables (dropped by migration 010) | removed | still present |

`database_pos_system.sql` already includes the changes from migrations **002–012**, so **do not run anything in `migrations/`** after importing it. Neither dump contains triggers, stored procedures, views or `DEFINER` clauses, so it imports cleanly on shared hosting. It also has no `CREATE DATABASE`/`USE` statement, so it goes into whichever database you select.

The dump contains your existing live data: branches, products, inventory, orders/transactions, shifts, X/Z readings, and **6 user accounts** (`Brix`, `Allan`, `wenggams`, `Joshua`, `Merfern`, `Barbe`). Log in with those existing accounts.

> Note: `includes/auth.php` defines `getBranchIds()`, which reads a `branch_users` table that exists in neither dump. Nothing calls that function, so this is harmless.

### Steps (cPanel wording; other panels are similar)

1. **Create a database** under *MySQL® Databases*, e.g. `minutrack`. cPanel adds a prefix, giving something like `cpuser_minutrack`.
2. **Create a database user** with a strong password, e.g. `cpuser_mtuser`.
3. **Add the user to the database** with **ALL PRIVILEGES**. The app needs `SELECT/INSERT/UPDATE/DELETE`, and the in-app backup/restore also needs `CREATE/ALTER/DROP/INDEX/LOCK TABLES`.
4. **Import:** open *phpMyAdmin*, select the new database, go to *Import*, choose `database_pos_system.sql` (≈235 KB, format SQL) and click *Go*. Use an **empty** database. Never import into a database that already holds data you need.
5. Confirm that 35 tables are listed.

---

## 4. Configuration (`.env`)

All configuration comes from a `.env` file in the `minute1/` folder, which `config.php` reads. Real server environment variables take priority over `.env`. **`.env` is git-ignored, so create it on the server** (in the File Manager, copy `.env.example` to `.env`):

```ini
DB_HOST=localhost
DB_NAME=cpuser_minutrack
DB_USER=cpuser_mtuser
DB_PASS=your-strong-db-password
GROQ_API_KEY=your_groq_api_key_here
```

- `DB_HOST` is `localhost` on most shared hosts. Some hosts (e.g. InfinityFree) give a hostname such as `sql123.example.com`; check your panel.
- If a value is left out, it falls back to the XAMPP defaults (`localhost` / `pos_system` / `root` / empty). That is why the same code still works locally.
- `GROQ_API_KEY` is needed only for the AI assistant. Without it the rest of the POS works normally.
- If the file manager hides dotfiles, turn on "Show hidden files".

**Application URL:** nothing needs to be configured. The app uses path-only URLs (`/minute1/...`), so any domain works as long as the folder is `minute1`.

**Timezone:** the app runs on Asia/Manila time. `includes/db_connect.php` sets the MySQL session to `+08:00`, so `NOW()` in sales, shifts and reports matches PHP even when the host runs on UTC or US time.

---

## 5. Files & permissions

| Path | Needs to be writable by PHP | Purpose |
|---|---|---|
| `assets/images/products/` | **Yes** | Product image uploads (admin → Products). Holds the existing 104 product images; upload them, do not delete them. |
| `tools/backups/` | **Yes** | Automatic backup `.sql` files created on logout/backup. Created automatically if missing. |
| Everything else | No | Read-only is fine |

Typical permissions on shared hosting are folders `755` and files `644`. If uploads fail, set the two folders above to `775`. Avoid `777`.

Images are stored as **file names only** in the database and served from `/minute1/assets/images/products/` and `/minute1/img/`. There are no Windows `C:\xampp` paths, so all existing images work on the host once the folders are uploaded.

---

## 6. Security (`.htaccess`)

The included `.htaccess` does **no URL rewriting**; all `.php` URLs work exactly as before. It only:

- turns off directory listings;
- blocks direct download of `.env`, `*.sql`, `*.gz`, `*.log`, `*.md`, `readme.txt` and other dotfiles;
- blocks the `migrations/`, `tools/backups/`, `backups/`, `docs/` and `.git/` folders. Backups can still be downloaded through the app's own `tools/download_auto_backup.php`.

Without it, anyone could download `.env` (DB password and API key) and the SQL dumps (user password hashes). **Make sure `.htaccess` is uploaded; it is a hidden file.** If your host is **Nginx** (not Apache), `.htaccess` is ignored, so ask the host to apply equivalent deny rules.

Other security points:

- **HTTPS is required.** Enable the free SSL certificate (Let's Encrypt / AutoSSL) in your panel. Over HTTPS the session cookie is sent `Secure`-only automatically (`config.php`).
- **Public repo data:** `database_pos_system.sql` and `pos_system (2).sql` are committed to the **public** GitHub repo and contain real names, emails and bcrypt password hashes of the 6 accounts. Bcrypt hashes are slow to crack but not impossible for weak passwords, so **change every user's password after going live**. Consider making the repo private or removing the dumps from it.
- **Groq API key:** the key currently in the local `.env` was once hardcoded in `config.php` in commits `5bb5353` and `a061bea` on the **local `main` branch**. Those commits are **not** on the public `MinuTrack` repo. Never push local `main` to a public remote. If that history was ever shared, rotate the key at https://console.groq.com/keys.
- Do not upload `_tmp_schema.php`. It is a local debug script with hardcoded root credentials and is blocked by `.htaccess` anyway.

---

## 7. Upload procedure

1. Download the repo from GitHub (*Code → Download ZIP*) or `git clone` it on a VPS.
2. Upload its contents into `public_html/minute1/` (File Manager → upload the ZIP → Extract). Do **not** upload `.claude/`, `.codegraph/`, `.superpowers/`, `.opencode/` or `_tmp_schema.php`.
3. Create `.env` (section 4).
4. Import the database (section 3).
5. Set writable folders (section 5).
6. Enable SSL, then open `https://your-domain.com/minute1/`.

To update later, upload the changed files over the old ones. Never overwrite `.env`, `assets/images/products/` or `tools/backups/` on the server, because they hold live data.

---

## 8. Verification checklist

Test on a PC **and** on an Android phone **and** an iPhone (Chrome/Safari):

```text
[ ] Homepage  https://your-domain.com/minute1/  redirects to the login page, page is styled (CSS loads)
[ ] Padlock shown (HTTPS), no mixed-content warnings
[ ] Login works with an existing account; wrong password is rejected
[ ] Welcome page shows the Minute Burger logo, then redirects by role
[ ] Products: list shows existing product images; add/edit a product with an image upload
[ ] Inventory: stock levels shown per branch; update stock; item history loads
[ ] POS: add items, cart totals correct, complete a sale
[ ] Transactions: new sale appears with the CORRECT local (Manila) time
[ ] Receipt: opens and prints/displays correctly
[ ] Reports: today's sales include the test sale; Excel export downloads
[ ] Start shift / X-reading / Z-reading work
[ ] Owner: branch switch works; Users and Roles pages load
[ ] AI assistant widget answers (only if GROQ_API_KEY is set)
[ ] Backup: create and download a backup from the app
[ ] Logout, then back button does not reveal protected pages
[ ] These return 403 Forbidden:
      /minute1/.env
      /minute1/database_pos_system.sql
      /minute1/tools/backups/
      /minute1/migrations/005_rbac.php
[ ] Change all user passwords
```

Troubleshooting:

- **"Database connection failed"**: check `.env` values and `DB_HOST`, and that the user is added to the database.
- **Unstyled pages / 404 after login**: the folder is not named `minute1` (section 2).
- **500 error on every page**: the host does not allow `Options` in `.htaccess`. Remove the `Options -Indexes` line.
- **Image upload fails**: set `assets/images/products/` to be writable.
