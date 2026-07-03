# Barangay Management Information System (BMIS)

A full-stack Barangay MIS built with **PHP 8.2 + MySQL/MariaDB**, designed to run on
**XAMPP 8.2.12-0-VS16**. Vanilla PHP (no framework), PDO + prepared statements
throughout, Bootstrap 5 UI, Chart.js dashboard, and zero Composer dependencies —
everything works right after importing the database.

---

## 1. Requirements

- **XAMPP 8.2.12-0-VS16** (Apache + MySQL + PHP 8.2) — [apachefriends.org](https://www.apachefriends.org/)
- A modern browser (Chrome, Edge, Firefox) for the admin UI and for printing certificates to PDF

No Composer, no `composer install`, no PECL extensions beyond what ships with
stock XAMPP (`pdo_mysql`, `mbstring`, `fileinfo` — all enabled by default).

---

## 2. Installation

1. **Install & start XAMPP 8.2.12-0-VS16.** Open the XAMPP Control Panel and click
   **Start** next to both **Apache** and **MySQL**.
2. **Copy this whole folder** into your XAMPP `htdocs` directory, e.g.:
   ```
   C:\xampp\htdocs\barangay-mis
   ```
   You can rename the folder to whatever you like — the app auto-detects its own
   URL path, so it doesn't need to be named `barangay-mis`.
3. **Import the database:**
   - Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Click **Import** → **Choose File** → select `database.sql` from this folder → **Go**
   - This creates the `barangay_mis` database, all tables, and sample/seed data automatically.
4. **Open the app:** go to `http://localhost/barangay-mis/` (or whatever you
   named the folder). You'll land on the login screen.

That's it — no further configuration is required for a default XAMPP setup
(MySQL user `root`, no password).

### If your MySQL has a root password or a dedicated DB user

Edit `config.php` and update:
```php
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

## 3. Demo Login Accounts

All seeded accounts use the password **`admin123`**. Change these before any
real deployment (Settings are per-user under **User Accounts**, admin only).

| Username    | Role         | Access                                          |
|-------------|--------------|--------------------------------------------------|
| `admin`     | Super Admin  | Everything, including Users & Settings           |
| `secretary` | Secretary    | All data modules; not Users/Settings             |
| `tanod1`    | Tanod        | Dashboard, Dispatch Board, Tanod Roster, Blotter (view only) |

See [Section 6](#6-roles--permissions) for the full permission matrix.

---

## 4. What's Included

13 integrated modules, matching the original project brief:

| # | Module | Notes |
|---|--------|-------|
| 1 | Authentication & Users | Role-based, bcrypt-hashed passwords, audit log |
| 2 | Residents | Full CRUD, photo upload, voter/PWD/senior/4Ps flags |
| 3 | Households | Linked to residents + puroks |
| 4 | Certificates & Documents | 5 document types, browser print-to-PDF |
| 5 | Blotter / Incident Reports | Case numbers auto-generated (`BLT-2026-001`) |
| 6 | Dispatch / Emergency Response | Live board, tanod roster, auto-timestamped response times, one-click escalation to Blotter |
| 7 | Business Permits | Expiry tracking, printable permit slip |
| 8 | Officials Directory | Public-style directory |
| 9 | Announcements | Bulletin board with categories |
| 10 | Events | Monthly calendar, venue conflict warnings, attendance tracking, printable attendance sheet |
| 11 | Requests / Complaints | Helpdesk-style status tracking |
| 12 | Dashboard & Reports | 4 Chart.js charts + CSV exports |
| 13 | Settings | Barangay profile, document fees, puroks |

### Design decisions worth knowing about

- **Certificates print via the browser ("Print → Save as PDF"), not TCPDF/mPDF.**
  This means zero Composer dependencies and it works the moment you import the
  database. If you later want server-side PDF generation, install TCPDF or
  mPDF via Composer and swap out `modules/documents/print.php` /
  `modules/business/print.php`.
- **Reports export as CSV, not native `.xlsx`.** CSV opens directly in Excel
  and Google Sheets and needs no PHP library (e.g. PhpSpreadsheet). See
  `modules/reports/export.php`.
- **`BASE_URL` is auto-detected** in `config.php` by comparing the project's
  filesystem path to Apache's document root — so the app works regardless of
  what you name the folder inside `htdocs`.

---

## 5. Folder Structure

```
barangay-mis/
├── config.php              # DB connection + BASE_URL auto-detection
├── database.sql             # Full schema + seed data
├── index.php / login.php / logout.php / dashboard.php
├── includes/                 # auth.php, functions.php, header/sidebar/footer
├── assets/
│   ├── css/style.css         # Design system (navy/gold civic theme)
│   └── js/main.js            # Sidebar toggle, delete confirms, quick filter
├── modules/
│   ├── residents/  households/  documents/
│   ├── blotter/    dispatch/    business/
│   ├── officials/  announcements/  events/
│   ├── complaints/ users/  reports/  settings/
└── uploads/
    ├── photos/                # resident/official/announcement photos
    └── attachments/           # reserved for future use
```

---

## 6. Roles & Permissions

| Area | Super Admin | Captain | Secretary | Staff | Tanod |
|---|:---:|:---:|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Residents / Households / Documents | ✅ | ✅ | ✅ | ✅ | — |
| Blotter (view) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Blotter (add/edit/delete) | ✅ | ✅ | ✅ | ✅ | — |
| Dispatch Board & Tanod Roster (view/use) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tanod Roster (add/edit/remove personnel) | ✅ | ✅ | ✅ | ✅ | — |
| Announcements / Events (view) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Announcements / Events (manage) | ✅ | ✅ | ✅ | ✅ | — |
| Business Permits / Requests | ✅ | ✅ | ✅ | ✅ | — |
| Officials (view) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Reports | ✅ | ✅ | ✅ | ✅ | — |
| User Accounts / Settings | ✅ | ✅ | — | — | — |

Permissions are enforced server-side in every file via `require_role()`
(see `includes/auth.php`) — hiding a link in the sidebar is a UX nicety, not
the actual security boundary.

---

## 7. Troubleshooting

**"Database connection failed"** — Make sure MySQL is running in the XAMPP
Control Panel and that you've imported `database.sql` in phpMyAdmin.

**Blank page / 500 error** — Check `Apache error log` in the XAMPP Control
Panel (Logs button). Almost always a typo'd file path if you moved files
around manually.

**Photo uploads fail** — Confirm the `uploads/photos/` folder is writable by
Apache (on Windows/XAMPP this is rarely an issue, but check folder
permissions if you moved the project from another OS).

**Styling / icons missing** — The UI loads Bootstrap, Bootstrap Icons,
Google Fonts, and Chart.js from CDNs, so the machine running the browser
needs internet access. The PHP/MySQL backend itself works fully offline.

---

## 8. Suggested Next Steps

- Swap the seed passwords for real ones (User Accounts, admin only).
- Update the barangay name, address, officials, and fee schedule under **Settings**.
- Add pagination to `modules/residents/list.php` if your barangay has a very
  large resident count (the current build lists all records with a
  client-side quick filter, which is fine into the hundreds of rows).
- Consider TCPDF/mPDF via Composer if you need certificates to save as PDF
  without relying on the browser's print dialog.
