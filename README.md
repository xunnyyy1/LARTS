# LARTS – Livelihood Assistance & Resource Tracking System
### Davao Del Norte State College | IT223 Project
**Proponents:** Dela Cruz, Justine Rey C. | Morales, Denniel Josef T. | Saldaña, Lord Blasphemyr L.

---

## 🚀 Setup Instructions (XAMPP)

### Step 1 – Copy Project
Place the `larts` folder inside:
```
C:\xampp\htdocs\larts\
```

### Step 2 – Import Database
1. Open `http://localhost/phpmyadmin`
2. Click **New** → name it `larts_db` → click Create
3. Click the `larts_db` database → go to **Import**
4. Select `database.sql` from the project root
5. Click **Go**

### Step 3 – Configure (if needed)
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Change if your MySQL has a password
define('DB_NAME', 'larts_db');
```

### Step 4 – Run
Visit: `http://localhost/larts/`

---

## 🔑 Demo Login Accounts

| Username   | Password   | Role    |
|------------|------------|---------|
| `admin`    | `password` | Admin   |
| `mstaff`   | `password` | Staff   |
| `jencoder` | `password` | Encoder |

---

## 📁 Folder Structure

```
larts/
├── index.php                   ← Root redirect
├── database.sql                ← SQL schema + seed data
├── README.md
├── includes/
│   ├── config.php              ← DB config & constants
│   ├── auth.php                ← Session, roles, helpers
│   ├── header.php              ← Sidebar + topbar
│   └── footer.php              ← JS includes
├── auth/
│   ├── login.php
│   └── logout.php
├── dashboard/
│   └── index.php               ← Main dashboard with charts
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── modules/
    ├── households/
    │   ├── index.php           ← List + search + filter
    │   ├── view.php            ← Detail view (income/expense/assist tabs)
    │   ├── save.php            ← Add/Edit handler
    │   └── delete.php
    ├── income/
    │   └── index.php
    ├── expenses/
    │   └── index.php
    ├── assistance/
    │   └── index.php
    ├── reports/
    │   └── index.php           ← 4 report types + print layout
    └── users/
        └── index.php           ← Admin only
```

---

## ✅ Features Implemented

- [x] Role-based authentication (Admin / Staff / Encoder)
- [x] Household CRUD with search, filter by barangay, pagination
- [x] Income tracking with auto-status recalculation
- [x] Expense tracking by category
- [x] Assistance distribution recording
- [x] Automated financial status (Stable / At Risk / Vulnerable)
- [x] Dashboard with Chart.js charts (donut, bar, line)
- [x] Alerts for vulnerable households
- [x] 4 printable reports with date & barangay filters
- [x] Responsive Bootstrap 5 layout with sidebar
- [x] Prepared statements (SQL injection protection)
- [x] Password hashing (bcrypt)
- [x] Flash messages

---

## 🛠 Technologies
- **PHP 8+** (PDO, sessions, password hashing)
- **MySQL** (relational DB with FK constraints)
- **Bootstrap 5.3** (responsive layout)
- **Bootstrap Icons 1.11**
- **Chart.js 4.4** (dashboard charts)
- **Plus Jakarta Sans** + **Fira Code** (Google Fonts)

---

## 📋 SDG Alignment
This system directly supports **SDG 1: No Poverty** by:
- Digitizing household poverty monitoring
- Tracking livelihood assistance distribution
- Automatically flagging financially vulnerable families
- Generating actionable barangay-level reports

---
*Submitted to: Eduardo Catoc Jr. | April 2026*