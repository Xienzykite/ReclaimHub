# PIT Finds (PHP + MySQL), campus lost and found

Two separate portals:
- Campus members (students, faculty, staff): `index.php`. Sign up with school ID, report lost/found items, claim items.
- Office staff: `admin/login.php`. Separate login and separate admin table. Reviews reports, decides claims, manages members and admins.

Setup
1. PHP 8.1+ with PDO MySQL and mbstring and MySQL/MariaDB (XAMPP, Laragon, MAMP all work). `uploads/` must be writable.
2. Edit DB_* at the top of `config.php`. Also set PICKUP_POINT and, if wanted, ALLOWED_EMAIL_DOMAIN (e.g. `pit.edu.ph`) so only campus emails can sign up.
3. Optional: import `pitfinds.sql` yourself (phpMyAdmin > Import). Otherwise the first page load creates everything.
4. Open http://localhost/pitfinds/ . Staff portal: http://localhost/pitfinds/admin/login.php

First admin: admin / admin123. Add your own admin in Dashboard > Admins, then remove the default.
Edit the campus building list ($LOCS) and categories ($CATS) in `config.php`. Delete `.installed` to re-run setup.

## How users and the office talk to each other
- Users report items and send claims; both go to the office.
- Staff (admin/index.php): Reports tab approves/rejects reports and marks items returned; Claims tab shows the claimant, their proof and the item description side by side, then approves or rejects. Approving a claim marks the item returned and rejects other pending claims.
- Every decision can carry a short note. Users see it under My reports and My claims.
- Browse has filters for type, category, campus location, a lost/found date range and keywords.
- Existing installs are upgraded automatically (a `note` column is added on first load).
- Do not upload the `.installed` marker; setup re-runs safely if it is missing.
