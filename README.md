# Local Transport Fare Guide (LTFG)

A PHP & MySQL web application for browsing local transport routes and fares, submitting service reports, and managing everything from an admin dashboard.

## Features

- **User accounts** — registration, login/logout, and profile management
- **Route & fare lookup** — search routes, view fares, and save favorites
- **Fare history** — track previously viewed/searched fares
- **Reports** — users can submit reports (fare, route, service, app, safety, other) with severity levels; admins can review and update status
- **Admin dashboard** — manage users, locations, routes, transports, fares, and reports
- **Role-based access** — separate `user` and `admin` areas, guarded by session auth

## Tech Stack

- **Backend:** PHP (PDO for MySQL)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, vanilla JS
- **Environment:** Designed to run on XAMPP (Apache + MySQL)

## Project Structure

```
LTFG/
├── admin/            # Admin dashboard (dashboard, fares, locations, reports, routes, transports, users)
├── auth/             # Login, register, logout
├── user/             # User dashboard, search, fare history, favorites, reports, profile
├── includes/         # Shared layout, session/auth helpers, and utility functions
├── config/           # App configuration and database connection
├── database/         # SQL schema (transport.sql)
├── assets/           # CSS, JS, icons, images
└── index.php         # Landing page
```

## Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- PHP with the `pdo_mysql` extension enabled

### Installation

1. Clone or copy this project into your server's document root, e.g. `C:\xampp\htdocs\LTFG`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Import the database schema:
   - Open phpMyAdmin and import `database/transport.sql`, **or**
   - Run it via the CLI:
     ```bash
     mysql -u root -p < database/transport.sql
     ```
4. Update database credentials in `config/config.php` if they differ from the defaults:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'local_transport_fair_guide');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
5. Open the app in your browser:
   ```
   http://localhost/LTFG
   ```

A default admin account is created automatically on first load.

## Default Admin Login

| Field    | Value             |
|----------|-------------------|
| Email    | `admin@gmai.com`  |
| Password | `admin@gmai.com`  |

> ⚠️ **Change this password immediately** if you deploy this project anywhere beyond local development.

## Database

The schema (`database/transport.sql`) includes the following core tables:

- `users` — accounts with `user` / `admin` roles
- `locations` — named locations/regions
- `routes` — routes between locations with fares
- `favorites` — a user's saved routes
- `reports` — user-submitted issues/feedback with category, severity, and status

## Roadmap / Notes

- Use strong, unique credentials before deploying to production
- Consider adding CSRF protection and rate limiting on auth forms
- API endpoints (`api/`) are scaffolded but not yet implemented

## License

Add a license of your choice (e.g. MIT) here.
