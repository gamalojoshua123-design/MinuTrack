=========================================================
 MinuTrack - DEPLOYMENT INSTRUCTIONS (readme.txt)
=========================================================

PROJECT     : MinuTrack - Minute Burger Point-of-Sale and
              Inventory Management System
GITHUB REPO : https://github.com/gamalojoshua123-design/MinuTrack
TECH STACK  : PHP 8 (no framework), MySQL / MariaDB,
              HTML, CSS, JavaScript
SERVER      : XAMPP (Apache + MySQL)

---------------------------------------------------------
1. REQUIREMENTS
---------------------------------------------------------
- Windows / macOS / Linux
- XAMPP 8.x (includes Apache, PHP 8.0 or higher, MySQL/MariaDB)
  Download: https://www.apachefriends.org/download.html
- Git (to clone the repository)
  Download: https://git-scm.com/downloads
- A web browser (Chrome / Edge / Firefox)
- Internet connection (only required for the AI Assistant feature)

---------------------------------------------------------
2. INSTALL AND START XAMPP
---------------------------------------------------------
1. Install XAMPP using the default settings.
2. Open the XAMPP Control Panel.
3. Click "Start" for both Apache and MySQL.

   If Apache fails to start, another program is using port 80.
   Either stop that program (IIS, Skype, etc.) or change Apache's
   port through Config > httpd.conf.

---------------------------------------------------------
3. GET THE SOURCE CODE
---------------------------------------------------------
Open a terminal / Command Prompt and run:

    cd C:\xampp\htdocs
    git clone https://github.com/gamalojoshua123-design/MinuTrack.git minute1

The project must end up in:   C:\xampp\htdocs\minute1

Without Git: open the GitHub page, click "Code > Download ZIP",
extract it, and rename the extracted folder to "minute1" inside
C:\xampp\htdocs.

IMPORTANT: the folder must be named "minute1" because the
application is served from http://localhost/minute1/

---------------------------------------------------------
4. CREATE AND IMPORT THE DATABASE
---------------------------------------------------------
1. Open http://localhost/phpmyadmin
2. Click "New" and create a database named:   pos_system
   Collation: utf8mb4_general_ci
3. Select the pos_system database, then open the "Import" tab.
4. Click "Choose File" and select this file from the project folder:

        database_pos_system.sql

5. Click "Go" / "Import" and WAIT until the success message appears.

   Do not close the tab or navigate away while it is importing.
   An interrupted import leaves the database incomplete and the
   system will crash with "Table 'pos_system.users' doesn't exist".

6. Verify the import: the pos_system database must contain
   35 tables, including "users", "roles", "role_permissions",
   "branches", "products", "orders", and "inventory".
   If there are fewer tables, drop the database and import again.

NOTE: database_pos_system.sql is the complete and current schema.
No additional migration files need to be run after importing it.
The "migrations" folder is kept only as a development history.

---------------------------------------------------------
5. CONFIGURE THE ENVIRONMENT FILE (.env)
---------------------------------------------------------
1. Inside the project folder, copy ".env.example" to ".env":

        Windows:      copy .env.example .env
        macOS/Linux:  cp .env.example .env

2. Open ".env" in a text editor (Notepad works) and set the
   values below. Remove the "#" at the start of each line:

        DB_HOST=localhost
        DB_NAME=pos_system
        DB_USER=root
        DB_PASS=
        GROQ_API_KEY=your_groq_api_key_here

   On a default XAMPP installation the MySQL user is "root"
   with a blank password, so DB_PASS is left empty.

3. The Groq API key powers the built-in AI Assistant.
   Get a free key at https://console.groq.com/keys
   (Log in > API Keys > Create API Key).

   The system runs fine without a key. Only the AI chatbot
   will be unavailable.

SECURITY: the .env file is git-ignored and is never committed.
Never place real passwords or API keys in any other file.

---------------------------------------------------------
6. RUN THE SYSTEM
---------------------------------------------------------
1. Make sure Apache and MySQL are running in XAMPP.
2. Open a browser and go to:

        http://localhost/minute1/

3. Log in with an existing account from the users table.
   The included database has accounts for the System Owner,
   Managers, and Cashiers. Contact the development team for
   the account passwords.

4. To create a new administrator manually, insert a row into
   the "users" table with a password hashed using the PHP
   password_hash() function. Plain-text passwords will not work.

---------------------------------------------------------
7. USER ROLES
---------------------------------------------------------
- admin         - System Owner. Full access to every module and
                  every branch. Can switch between branch views
                  and assign any role.
- manager       - Branch Admin. Manages the operations, staff,
                  and reports of an assigned branch.
- branch_owner  - Views and manages the data of their own branch.
- cashier       - Point-of-Sale terminal, transactions, shift
                  start, and X/Z readings.

Access is enforced by a role-based access control (RBAC) system.
Permissions are stored in the database and checked on every page.

---------------------------------------------------------
8. MAIN MODULES
---------------------------------------------------------
- Point of Sale     - Order taking, cart, payment, receipt printing
- Inventory         - Stock levels, deliveries, suppliers, counts,
                      low-stock alerts, stock movement history
- Products          - Products, categories, prices, and recipes
                      (automatic ingredient deduction per sale)
- Reports           - Sales and inventory reports with Excel export
- User Management   - Accounts, roles, and permissions
- Multi-Branch      - Per-branch data separation and comparison
- AI Assistant      - Chatbot for business questions (Groq API)
- Backup            - Database backup and restore utility

---------------------------------------------------------
9. FOLDER STRUCTURE
---------------------------------------------------------
  admin/       Owner and Admin dashboard, branches, roles, reports
  ai/          AI assistant helper and chat endpoint
  api/         Internal AJAX / JSON endpoints
  auth/        Login, logout, welcome, and unauthorized pages
  cashier/     POS terminal, receipts, shifts, X and Z readings
  inventory/   Stock, suppliers, deliveries, inventory counts
  products/    Product and recipe management
  reports/     Sales and inventory reports
  users/       User account management
  includes/    Shared core files (database, auth, RBAC, header,
               sidebar, and helper functions)
  assets/      CSS, JavaScript, and images
  migrations/  Development history of database changes
  docs/        Project documentation
  tools/       Backup and archive utilities

  bootstrap.php   Loaded by every page; wires up the application
  config.php      Loads .env, defines constants, security headers

---------------------------------------------------------
10. TROUBLESHOOTING
---------------------------------------------------------
PROBLEM : "Table 'pos_system.users' doesn't exist"
CAUSE   : The database import did not finish.
FIX     : In phpMyAdmin, drop the pos_system database, create it
          again, and re-import database_pos_system.sql completely.
          Confirm all 35 tables are present afterwards.

PROBLEM : "Database connection failed"
CAUSE   : MySQL is not running, or .env has wrong credentials.
FIX     : Start MySQL in XAMPP and check DB_NAME, DB_USER, and
          DB_PASS inside the .env file.

PROBLEM : Blank white page
FIX     : Open C:\xampp\php\php.ini, set display_errors = On,
          then restart Apache to see the actual error message.

PROBLEM : "Object not found" or 404 error
FIX     : Make sure the folder is named exactly "minute1" and is
          located inside C:\xampp\htdocs.

PROBLEM : The AI Assistant does not reply
FIX     : Set a valid GROQ_API_KEY in .env and make sure the
          computer has an internet connection.

PROBLEM : Logged in but every page says access denied
CAUSE   : The roles and permissions tables are empty.
FIX     : Re-import database_pos_system.sql. It already contains
          the roles, permissions, and role_permissions data.

---------------------------------------------------------
11. BACKUP AND MAINTENANCE
---------------------------------------------------------
- Export the database regularly from phpMyAdmin:
  select pos_system > Export > Go.
- The system also provides a backup page for the System Owner at
  http://localhost/minute1/tools/backup.php
- Keep the .env file out of version control at all times.

=========================================================
 END OF FILE
=========================================================
