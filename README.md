<div align="center">

# 🚌 Local Transport Fare Guide (LTFG)

**A PHP & MySQL web application for browsing local transport routes and fares, submitting service reports, and managing everything from a full-featured admin dashboard.**

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=flat&logo=xampp&logoColor=white)](https://www.apachefriends.org/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](#license)

</div>

---

## 📖 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
- [Default Admin Login](#-default-admin-login)
- [Database Schema](#-database-schema)
- [Roadmap](#-roadmap)
- [License](#-license)

---

## ✨ Features

| | |
|---|---|
| 👤 **User Accounts** | Registration, login/logout, and profile management |
| 🔍 **Route & Fare Lookup** | Search routes, view fares, and save favorites |
| 🕘 **Fare History** | Track previously viewed and searched fares |
| 📝 **Reports** | Submit reports by category (fare, route, service, app, safety, other) with severity levels |
| 🛠️ **Admin Dashboard** | Manage users, locations, routes, transports, fares, and reports |
| 🔐 **Role-Based Access** | Separate `user` and `admin` areas, guarded by session authentication |

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP (PDO for MySQL) |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML, CSS, Vanilla JS |
| **Environment** | XAMPP (Apache + MySQL) |

---

## 📁 Project Structure

```
LTFG/
├── admin/            # Admin dashboard (fares, locations, reports, routes, transports, users)
├── auth/             # Login, register, logout
├── user/             # User dashboard, search, fare history, favorites, reports, profile
├── includes/         # Shared layout, session/auth helpers, and utility functions
├── config/           # App configuration and database connection
├── database/         # SQL schema (transport.sql)
├── assets/           # CSS, JS, icons, images
└── index.php         # Landing page
```

---

## 🚀 Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- PHP with the `pdo_mysql` extension enabled

### Installation

1. **Clone or copy** this project into your server's document root:
   ```
   C:\xampp\htdocs\LTFG
   ```

2. **Start** Apache and MySQL from the XAMPP control panel.

3. **Import the database schema** — either:
   - Open phpMyAdmin and import `database/transport.sql`, **or**
   - Run it via the CLI:
     ```bash
     mysql -u root -p < database/transport.sql
     ```

4. **Configure credentials** in `config/config.php` if they differ from the defaults:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'local_transport_fair_guide');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

5. **Launch the app** in your browser:
   ```
   http://localhost/LTFG
   ```

> A default admin account is created automatically on first load.

---

## 🔑 Default Admin Login

| Field    | Value             |
|----------|-------------------|
| Email    | `admin@gmai.com`  |
| Password | `admin@gmai.com`  |

> ⚠️ **Security Notice:** Change this password immediately before deploying anywhere beyond local development.

---

## 🗄️ Database Schema

The schema (`database/transport.sql`) defines these core tables:

| Table | Description |
|---|---|
| `users` | Accounts with `user` / `admin` roles |
| `locations` | Named locations/regions |
| `routes` | Routes between locations, including fares |
| `favorites` | A user's saved/favorited routes |
| `reports` | User-submitted issues and feedback with category, severity, and status |

---

## 🗺️ Roadmap

- [ ] Enforce strong, unique credentials before production deployment
- [ ] Add CSRF protection and rate limiting on auth forms
- [ ] Implement the scaffolded `api/` endpoints

---

## 📄 License

This project is licensed under the [MIT License](LICENSE) — feel free to use, modify, and distribute it.

---

<div align="center">

Made with ❤️ for smarter, more transparent local transport.

</div>
